<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppManager $whatsapp
    ) {
    }

    /**
     * Display WhatsApp communication history.
     */
    public function index(): View
    {
        $logs = WhatsAppLog::query()
            ->with([
                'student',
                'fee',
            ])
            ->orderByDesc('created_at')
            ->paginate(25);

        $totalLogs = WhatsAppLog::count();

        $sentLogs = WhatsAppLog::query()
            ->where('status', 'sent')
            ->count();

        $queuedLogs = WhatsAppLog::query()
            ->where('status', 'queued')
            ->count();

        $failedLogs = WhatsAppLog::query()
            ->where('status', 'failed')
            ->count();

        return view(
            'whatsapp.index',
            compact(
                'logs',
                'totalLogs',
                'sentLogs',
                'queuedLogs',
                'failedLogs'
            )
        );
    }

    /**
     * Display message creation form.
     */
    public function create(
        Request $request
    ): View {
        $students = Student::query()
            ->with('bus')
            ->where('active', true)
            ->orderBy('student_name')
            ->get();

        $selectedStudent = null;

        if ($request->filled('student_id')) {
            $selectedStudent = $students->firstWhere(
                'id',
                (int) $request->student_id
            );
        }

        return view(
            'whatsapp.create',
            compact(
                'students',
                'selectedStudent'
            )
        );
    }

    /**
     * Send WhatsApp template from the main WhatsApp form.
     *
     * This uses the Meta Cloud API template:
     *
     * transport_fee_due
     *
     * Language:
     * en
     *
     * Parameters:
     * {{1}} Parent name
     * {{2}} Student name
     * {{3}} Fee period
     * {{4}} Outstanding amount
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'whatsapp_number' => [
                'required',
                'string',
                'max:30',
            ],

            'message_type' => [
                'required',
                'string',
                'in:fee_reminder,payment_confirmation,general,notice',
            ],

            /*
             * The old click-to-chat system required a message.
             * Meta template sending does not use this field.
             */
            'message' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Load student
        |--------------------------------------------------------------------------
        */

        $student = Student::query()
            ->with([
                'bus',
                'fees',
                'payments',
            ])
            ->findOrFail(
                $data['student_id']
            );


        /*
        |--------------------------------------------------------------------------
        | Normalize phone number
        |--------------------------------------------------------------------------
        */

        $phone = $this->normalizePhone(
            $data['whatsapp_number']
        );

        if (!$phone) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Invalid Indian WhatsApp number. '
                    . 'Please enter a valid 10-digit mobile number.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | This application currently has an active Meta template only
        | for transport fee reminders.
        |--------------------------------------------------------------------------
        */

        if ($data['message_type'] !== 'fee_reminder') {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Only the transport fee reminder template is currently available.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Send the fee reminder
        |--------------------------------------------------------------------------
        */

        return $this->sendFeeReminder(
            $student,
            $phone
        );
    }

    /**
     * Compatibility route:
     *
     * POST /whatsapp/send/{student}
     *
     * This route is also converted to the same Meta template sending
     * logic so old buttons/forms in the application continue to work.
     */
    public function send(
        Request $request,
        Student $student
    ): RedirectResponse {
        $data = $request->validate([
            'message_type' => [
                'required',
                'string',
                'in:due,reminder,general',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Load relationships
        |--------------------------------------------------------------------------
        */

        $student->load([
            'bus',
            'fees',
            'payments',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Validate phone
        |--------------------------------------------------------------------------
        */

        if (empty($student->whatsapp_number)) {
            return back()->with(
                'error',
                'This student does not have a WhatsApp number.'
            );
        }


        $phone = $this->normalizePhone(
            $student->whatsapp_number
        );

        if (!$phone) {
            return back()->with(
                'error',
                'The WhatsApp number for this student is invalid.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Current active Meta template is a fee reminder.
        |
        | Treat the existing "due" / "reminder" actions as the same
        | transport fee reminder operation.
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $data['message_type'],
                ['due', 'reminder'],
                true
            )
        ) {
            return back()->with(
                'error',
                'Only transport fee reminders can currently be sent.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Send the actual Meta template
        |--------------------------------------------------------------------------
        */

        return $this->sendFeeReminder(
            $student,
            $phone
        );
    }

    /**
     * Actually send the transport fee reminder through Meta.
     */
    private function sendFeeReminder(
        Student $student,
        string $phone
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Current balance
        |--------------------------------------------------------------------------
        |
        | Negative balance = amount due.
        | Positive balance = advance.
        |
        | Student::due_amount is used for the actual outstanding amount.
        |--------------------------------------------------------------------------
        */

        $dueAmount = round(
            (float) $student->due_amount,
            2
        );

        $balance = round(
            (float) $student->current_balance,
            2
        );


        /*
        |--------------------------------------------------------------------------
        | No outstanding amount = do not send reminder
        |--------------------------------------------------------------------------
        |
        | This is important to prevent unnecessary reminders after payment.
        |--------------------------------------------------------------------------
        */

        if ($dueAmount <= 0.01) {
            return back()->with(
                'warning',
                'No outstanding transport fee exists for this student. '
                . 'WhatsApp reminder was not sent.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Find oldest outstanding fee
        |--------------------------------------------------------------------------
        */

        $oldestFee = $this->findOldestOutstandingFee(
            $student
        );

        if (!$oldestFee) {
            return back()->with(
                'warning',
                'No outstanding fee record was found. '
                . 'WhatsApp reminder was not sent.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Parent name
        |--------------------------------------------------------------------------
        */

        $parentName = trim(
            (string) $student->parent_name
        );

        if ($parentName === '') {
            $parentName = 'Parent';
        }


        /*
        |--------------------------------------------------------------------------
        | Student name
        |--------------------------------------------------------------------------
        */

        $studentName = trim(
            (string) $student->student_name
        );

        if ($studentName === '') {
            $studentName = 'Student';
        }


        /*
        |--------------------------------------------------------------------------
        | Fee period
        |--------------------------------------------------------------------------
        */

        $periodStart = '';

        $periodEnd = '';

        if ($oldestFee->period_start) {
            $periodStart =
                $oldestFee->period_start
                    ->format('d M Y');
        }

        if ($oldestFee->period_end) {
            $periodEnd =
                $oldestFee->period_end
                    ->format('d M Y');
        }


        $feePeriod =
            trim(
                $periodStart
                . ' - '
                . $periodEnd
            );


        if ($feePeriod === '') {
            $feePeriod = 'Current fee period';
        }


        /*
        |--------------------------------------------------------------------------
        | Outstanding amount
        |--------------------------------------------------------------------------
        */

        $outstandingAmount =
            '₹'
            . number_format(
                $dueAmount,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Get configured provider
        |--------------------------------------------------------------------------
        */

        $provider =
            $this->whatsapp->driver();


        /*
        |--------------------------------------------------------------------------
        | Send Meta WhatsApp template
        |--------------------------------------------------------------------------
        |
        | This exactly matches the successful Tinker test:
        |
        | type       = template
        | template   = transport_fee_due
        | language   = en
        | parameters = 4 body parameters
        |--------------------------------------------------------------------------
        */

        $result = $provider->send(
            $phone,
            '',
            [
                'type' =>
                    'template',

                'template' =>
                    config(
                        'whatsapp.template',
                        'transport_fee_due'
                    ),

                'language' =>
                    config(
                        'whatsapp.template_language',
                        'en'
                    ),

                'parameters' => [
                    $parentName,
                    $studentName,
                    $feePeriod,
                    $outstandingAmount,
                ],
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Make sure provider returned an array
        |--------------------------------------------------------------------------
        */

        if (!is_array($result)) {

            WhatsAppLog::create([
                'student_id' =>
                    $student->id,

                'fee_id' =>
                    $oldestFee->id,

                'phone' =>
                    $phone,

                'template_name' =>
                    config(
                        'whatsapp.template',
                        'transport_fee_due'
                    ),

                'message_type' =>
                    'fee_reminder',

                'balance_at_send' =>
                    $balance,

                'status' =>
                    'failed',

                'provider_message_id' =>
                    null,

                'error_message' =>
                    'WhatsApp provider returned an invalid response.',

                'sent_at' =>
                    null,
            ]);

            return back()->with(
                'error',
                'WhatsApp provider returned an invalid response.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Provider failure
        |--------------------------------------------------------------------------
        */

        if (!($result['success'] ?? false)) {

            $errorMessage =
                $result['error']
                ?? $result['message']
                ?? 'WhatsApp sending failed.';


            WhatsAppLog::create([
                'student_id' =>
                    $student->id,

                'fee_id' =>
                    $oldestFee->id,

                'phone' =>
                    $phone,

                'template_name' =>
                    config(
                        'whatsapp.template',
                        'transport_fee_due'
                    ),

                'message_type' =>
                    'fee_reminder',

                'balance_at_send' =>
                    $balance,

                'status' =>
                    'failed',

                'provider_message_id' =>
                    $result['message_id']
                    ?? null,

                'error_message' =>
                    $errorMessage,

                'sent_at' =>
                    null,
            ]);


            return back()
                ->withInput()
                ->with(
                    'error',
                    $errorMessage
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Successful Meta submission
        |--------------------------------------------------------------------------
        */

        $providerName =
            $result['provider']
            ?? 'meta';


        $messageId =
            $result['message_id']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Save WhatsApp history
        |--------------------------------------------------------------------------
        */

        WhatsAppLog::create([
            'student_id' =>
                $student->id,

            'fee_id' =>
                $oldestFee->id,

            'phone' =>
                $phone,

            'template_name' =>
                config(
                    'whatsapp.template',
                    'transport_fee_due'
                ),

            'message_type' =>
                'fee_reminder',

            'balance_at_send' =>
                $balance,

            /*
             * API providers mean the message was accepted/submitted
             * to the provider.
             */
            'status' =>
                $providerName === 'local'
                    ? 'queued'
                    : 'sent',

            'provider_message_id' =>
                $messageId,

            'error_message' =>
                null,

            'sent_at' =>
                $providerName === 'local'
                    ? null
                    : now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Local provider
        |--------------------------------------------------------------------------
        */

        if (!empty($result['url'])) {
            return redirect()->away(
                $result['url']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Meta / API provider
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('whatsapp.index')
            ->with(
                'success',
                'WhatsApp payment reminder sent successfully.'
            );
    }

    /**
     * Show a WhatsApp log.
     */
    public function show(
        WhatsAppLog $whatsappLog
    ): View {
        $whatsappLog->load([
            'student.bus',
            'fee',
        ]);

        return view(
            'whatsapp.log',
            compact('whatsappLog')
        );
    }

    /**
     * Delete a WhatsApp log.
     */
    public function destroy(
        WhatsAppLog $whatsappLog
    ): RedirectResponse {
        $whatsappLog->delete();

        return redirect()
            ->route('whatsapp.index')
            ->with(
                'success',
                'WhatsApp log deleted successfully.'
            );
    }

    /**
     * Find oldest outstanding fee.
     *
     * Payment allocations are used as the accounting source of truth.
     */
    private function findOldestOutstandingFee(
        Student $student
    ): ?Fee {
        $fees = Fee::query()
            ->where(
                'student_id',
                $student->id
            )
            ->withSum(
                'allocations',
                'amount'
            )
            ->orderBy('period_start')
            ->orderBy('id')
            ->get();

        foreach ($fees as $fee) {

            /*
            |--------------------------------------------------------------------------
            | Required amount = fee + late fee
            |--------------------------------------------------------------------------
            */

            $required = round(
                (float) $fee->amount
                + (float) $fee->late_fee,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Allocated payments
            |--------------------------------------------------------------------------
            */

            $allocated = round(
                (float) (
                    $fee->allocations_sum_amount
                    ?? 0
                ),
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Outstanding
            |--------------------------------------------------------------------------
            */

            $outstanding = max(
                0,
                round(
                    $required
                    - $allocated,
                    2
                )
            );


            /*
            |--------------------------------------------------------------------------
            | First outstanding fee
            |--------------------------------------------------------------------------
            */

            if ($outstanding > 0.01) {
                return $fee;
            }
        }

        return null;
    }

    /**
     * Normalize Indian WhatsApp phone number.
     *
     * Supported:
     *
     * 9876543210
     * 09876543210
     * 919876543210
     * +919876543210
     * +91 98765 43210
     */
    private function normalizePhone(
        ?string $phone
    ): ?string {
        if (!$phone) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Remove everything except digits
        |--------------------------------------------------------------------------
        */

        $phone = preg_replace(
            '/[^0-9]/',
            '',
            $phone
        );


        if (!$phone) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | 11-digit Indian number beginning with 0
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | 10-digit Indian mobile number
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) === 10
            && preg_match(
                '/^[6-9][0-9]{9}$/',
                $phone
            )
        ) {
            return '91' . $phone;
        }


        /*
        |--------------------------------------------------------------------------
        | Already normalized 12-digit Indian number
        |--------------------------------------------------------------------------
        */

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