<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Throwable;

class WhatsAppFeeNotificationService
{
    public function __construct(
        private readonly WhatsAppManager $whatsapp
    ) {
    }

    /**
     * Send WhatsApp notification for a newly generated fee.
     *
     * Existing Meta template:
     *
     * transport_fee_due
     *
     * {{1}} Parent name
     * {{2}} Student name
     * {{3}} Fee period
     * {{4}} Outstanding amount
     */
    public function sendNewFeeNotification(
        Fee $fee
    ): array {
        $fee->loadMissing('student');

        $student = $fee->student;

        if (!$student) {
            return $this->failure(
                'Fee student relationship is missing.'
            );
        }

        if (!$student->active) {
            return $this->notRequired(
                'Student is inactive.'
            );
        }

        $phone = $this->normalizePhone(
            $student->whatsapp_number
        );

        if (!$phone) {
            return $this->failure(
                'Student does not have a valid WhatsApp number.'
            );
        }

        $fee->refresh();

        $totalRequired = round(
            (float) $fee->amount
            + (float) $fee->late_fee,
            2
        );

        $allocated = round(
            (float) $fee->allocations()
                ->sum('amount'),
            2
        );

        $outstanding = max(
            0,
            round(
                $totalRequired - $allocated,
                2
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Existing advance completely covers new fee
        |--------------------------------------------------------------------------
        */

        if ($outstanding <= 0.01) {
            return $this->notRequired(
                'The newly generated fee is already fully covered.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate notification
        |--------------------------------------------------------------------------
        */

        $alreadySent = WhatsAppLog::query()
            ->where('fee_id', $fee->id)
            ->where(
                'message_type',
                'fee_created'
            )
            ->whereIn(
                'status',
                [
                    'accepted',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->exists();

        if ($alreadySent) {
            return [
                'success' => true,
                'status' => 'already_sent',
                'message' =>
                    'New-fee WhatsApp notification has already been sent.',
                'template' =>
                    config(
                        'whatsapp.template',
                        'transport_fee_due'
                    ),
                'message_id' => null,
            ];
        }

        return $this->sendUsingFeeTemplate(
            student: $student,
            fee: $fee,
            phone: $phone,
            periodStart: $fee->period_start,
            periodEnd: $fee->period_end,
            outstanding: $outstanding,
            messageType: 'fee_created'
        );
    }

    /**
     * Send a three-month advance request after the latest
     * completed billing period has been fully paid.
     *
     * The same Meta template is used:
     *
     * transport_fee_due
     *
     * For this event:
     *
     * {{1}} Parent name
     * {{2}} Student name
     * {{3}} Next 3-month period
     * {{4}} Amount still required
     */
    public function sendThreeMonthAdvanceRequest(
        Student $student,
        Fee $completedFee
    ): array {
        if (!$student->active) {
            return $this->notRequired(
                'Student is inactive.'
            );
        }

        $phone = $this->normalizePhone(
            $student->whatsapp_number
        );

        if (!$phone) {
            return $this->failure(
                'Student does not have a valid WhatsApp number.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify completed fee is actually fully paid
        |--------------------------------------------------------------------------
        */

        $completedRequired = round(
            (float) $completedFee->amount
            + (float) $completedFee->late_fee,
            2
        );

        $completedAllocated = round(
            (float) $completedFee
                ->allocations()
                ->sum('amount'),
            2
        );

        $completedOutstanding = max(
            0,
            round(
                $completedRequired
                - $completedAllocated,
                2
            )
        );

        if ($completedOutstanding > 0.01) {
            return $this->notRequired(
                'The completed fee still has an outstanding balance.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Next three calendar months
        |--------------------------------------------------------------------------
        */

        $nextStart = $completedFee->period_end
            ->copy()
            ->startOfMonth()
            ->addMonth();

        $nextEnd = $nextStart
            ->copy()
            ->addMonths(2)
            ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Calculate actual requirement for next three months
        |--------------------------------------------------------------------------
        |
        | If future fee records already exist, use their outstanding
        | amounts for the relevant months.
        |
        | If a month has no generated fee, use the student's current
        | monthly fee for that month.
        |
        */

        $threeMonthRequired =
            $this->calculateNextThreeMonthRequirement(
                $student,
                $nextStart,
                $nextEnd
            );

        if ($threeMonthRequired <= 0.01) {
            return $this->notRequired(
                'The next three months are already fully covered.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing unallocated advance
        |--------------------------------------------------------------------------
        */

        $advanceAmount = round(
            (float) $student->advance_amount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Amount still required
        |--------------------------------------------------------------------------
        */

        $amountToPay = max(
            0,
            round(
                $threeMonthRequired
                - $advanceAmount,
                2
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Existing advance already covers the amount
        |--------------------------------------------------------------------------
        */

        if ($amountToPay <= 0.01) {
            return $this->notRequired(
                'Existing advance already covers the next three months.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate reminder for this completed period
        |--------------------------------------------------------------------------
        */

        $alreadySent = WhatsAppLog::query()
            ->where(
                'student_id',
                $student->id
            )
            ->where(
                'fee_id',
                $completedFee->id
            )
            ->where(
                'message_type',
                'three_month_advance'
            )
            ->whereIn(
                'status',
                [
                    'accepted',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->exists();

        if ($alreadySent) {
            return [
                'success' => true,
                'status' => 'already_sent',
                'message' =>
                    'Three-month advance reminder has already been sent.',
                'template' =>
                    config(
                        'whatsapp.template',
                        'transport_fee_due'
                    ),
                'message_id' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Send same approved template
        |--------------------------------------------------------------------------
        */

        return $this->sendUsingFeeTemplate(
            student: $student,
            fee: $completedFee,
            phone: $phone,
            periodStart: $nextStart,
            periodEnd: $nextEnd,
            outstanding: $amountToPay,
            messageType: 'three_month_advance'
        );
    }

    /**
     * Calculate the actual amount required for the next three
     * calendar months.
     *
     * Existing future fee records are respected.
     *
     * For each month:
     *
     * - If a generated fee covers that month, its outstanding
     *   amount is spread proportionally across its covered months.
     * - If no fee covers the month, student's current monthly fee
     *   is used.
     */
    private function calculateNextThreeMonthRequirement(
        Student $student,
        Carbon $nextStart,
        Carbon $nextEnd
    ): float {
        $futureFees = Fee::query()
            ->where(
                'student_id',
                $student->id
            )
            ->whereDate(
                'period_start',
                '<=',
                $nextEnd->toDateString()
            )
            ->whereDate(
                'period_end',
                '>=',
                $nextStart->toDateString()
            )
            ->withSum(
                'allocations',
                'amount'
            )
            ->orderBy('period_start')
            ->orderBy('id')
            ->get();

        $required = 0.00;

        $currentMonth = $nextStart->copy();

        while (
            $currentMonth->lte(
                $nextEnd
            )
        ) {

            $monthStart =
                $currentMonth
                    ->copy()
                    ->startOfMonth();

            $monthEnd =
                $currentMonth
                    ->copy()
                    ->endOfMonth();

            /*
            |--------------------------------------------------------------------------
            | Find fee covering this calendar month
            |--------------------------------------------------------------------------
            */

            $coveringFee = $futureFees->first(
                function (Fee $fee) use (
                    $monthStart,
                    $monthEnd
                ) {

                    return (
                        $fee->period_start
                            ->lte($monthStart)
                        &&
                        $fee->period_end
                            ->gte($monthEnd)
                    );
                }
            );

            /*
            |--------------------------------------------------------------------------
            | No fee generated for this month
            |--------------------------------------------------------------------------
            */

            if (!$coveringFee) {

                $required = round(
                    $required
                    + (float) $student->monthly_fee,
                    2
                );

                $currentMonth->addMonth();

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Existing fee
            |--------------------------------------------------------------------------
            */

            $feeRequired = round(
                (float) $coveringFee->amount
                + (float) $coveringFee->late_fee,
                2
            );

            $feeAllocated = round(
                (float) (
                    $coveringFee
                        ->allocations_sum_amount
                    ?? 0
                ),
                2
            );

            $feeOutstanding = max(
                0,
                round(
                    $feeRequired
                    - $feeAllocated,
                    2
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Number of months covered by this fee
            |--------------------------------------------------------------------------
            */

            $feeMonthCount =
                (
                    (
                        $coveringFee
                            ->period_end
                            ->year
                        -
                        $coveringFee
                            ->period_start
                            ->year
                    ) * 12
                )
                +
                $coveringFee
                    ->period_end
                    ->month
                -
                $coveringFee
                    ->period_start
                    ->month
                + 1;

            $feeMonthCount = max(
                1,
                $feeMonthCount
            );

            /*
            |--------------------------------------------------------------------------
            | Allocate this fee's outstanding balance
            | proportionally to each covered month.
            |--------------------------------------------------------------------------
            */

            $monthlyOutstanding =
                round(
                    $feeOutstanding
                    / $feeMonthCount,
                    2
                );

            $required = round(
                $required
                + $monthlyOutstanding,
                2
            );

            $currentMonth->addMonth();
        }

        return round(
            $required,
            2
        );
    }

    /**
     * Send using the existing Meta template.
     */
    private function sendUsingFeeTemplate(
        Student $student,
        Fee $fee,
        string $phone,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $outstanding,
        string $messageType
    ): array {

        $templateName = config(
            'whatsapp.template',
            'transport_fee_due'
        );

        $language = config(
            'whatsapp.template_language',
            'en'
        );

        $parentName = trim(
            (string) (
                $student->parent_name
                ?: 'Parent'
            )
        );

        $studentName = trim(
            (string) $student->student_name
        );

        $period = sprintf(
            '%s - %s',
            $periodStart->format('d M Y'),
            $periodEnd->format('d M Y')
        );

        $amountText =
            '₹'
            . number_format(
                $outstanding,
                2
            );

        try {

            $result =
                $this->whatsapp
                    ->driver()
                    ->send(
                        $phone,
                        '',
                        [
                            'type' =>
                                'template',

                            'template' =>
                                $templateName,

                            'language' =>
                                $language,

                            'parameters' =>
                                [
                                    $parentName,
                                    $studentName,
                                    $period,
                                    $amountText,
                                ],
                        ]
                    );

            if (
                !($result['success'] ?? false)
            ) {

                $this->createLog(
                    $student,
                    $fee,
                    $phone,
                    $templateName,
                    $messageType,
                    $outstanding,
                    'failed',
                    $result['message_id']
                        ?? null,
                    $result['error']
                        ?? (
                            $result['message']
                            ?? 'WhatsApp sending failed.'
                        )
                );

                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' =>
                        $result['error']
                        ?? (
                            $result['message']
                            ?? 'WhatsApp sending failed.'
                        ),
                    'template' =>
                        $templateName,
                    'message_id' =>
                        $result['message_id']
                        ?? null,
                ];
            }

            $messageId =
                $result['message_id']
                ?? null;

            $this->createLog(
                $student,
                $fee,
                $phone,
                $templateName,
                $messageType,
                $outstanding,
                'accepted',
                $messageId,
                null
            );

            return [
                'success' => true,
                'status' => 'accepted',
                'message' =>
                    'WhatsApp message accepted by Meta.',
                'template' =>
                    $templateName,
                'message_id' =>
                    $messageId,
            ];

        } catch (Throwable $e) {

            $this->createLog(
                $student,
                $fee,
                $phone,
                $templateName,
                $messageType,
                $outstanding,
                'failed',
                null,
                $e->getMessage()
            );

            return [
                'success' => false,
                'status' => 'failed',
                'message' =>
                    $e->getMessage(),
                'template' =>
                    $templateName,
                'message_id' => null,
            ];
        }
    }

    /**
     * Create WhatsApp log.
     */
    private function createLog(
        Student $student,
        Fee $fee,
        string $phone,
        string $templateName,
        string $messageType,
        float $balance,
        string $status,
        ?string $messageId,
        ?string $error
    ): void {

        WhatsAppLog::create([
            'student_id' =>
                $student->id,

            'fee_id' =>
                $fee->id,

            'phone' =>
                $phone,

            'template_name' =>
                $templateName,

            'message_type' =>
                $messageType,

            'balance_at_send' =>
                $balance,

            'status' =>
                $status,

            'provider_message_id' =>
                $messageId,

            'error_message' =>
                $error,

            'sent_at' =>
                $status === 'accepted'
                    ? now()
                    : null,
        ]);
    }

    /**
     * Standard failure.
     */
    private function failure(
        string $message
    ): array {
        return [
            'success' => false,
            'status' => 'failed',
            'message' => $message,
            'template' => null,
            'message_id' => null,
        ];
    }

    /**
     * Nothing needs to be sent.
     */
    private function notRequired(
        string $message
    ): array {
        return [
            'success' => true,
            'status' => 'not_required',
            'message' => $message,
            'template' => null,
            'message_id' => null,
        ];
    }

    /**
     * Normalize Indian WhatsApp number.
     */
    private function normalizePhone(
        ?string $phone
    ): ?string {

        if (!$phone) {
            return null;
        }

        $phone = preg_replace(
            '/[^0-9]/',
            '',
            $phone
        );

        if (!$phone) {
            return null;
        }

        if (
            strlen($phone) === 11
            && str_starts_with(
                $phone,
                '0'
            )
        ) {
            $phone = substr(
                $phone,
                1
            );
        }

        if (
            strlen($phone) === 10
            && preg_match(
                '/^[6-9][0-9]{9}$/',
                $phone
            )
        ) {
            return '91' . $phone;
        }

        if (
            strlen($phone) === 12
            && preg_match(
                '/^91[6-9][0-9]{9}$/',
                $phone
            )
        ) {
            return $phone;
        }

        return null;
    }
}