<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\WhatsAppFeeNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWhatsAppReminders extends Command
{
    protected $signature =
        'fees:send-whatsapp-reminders';

    protected $description =
        'Send three-month advance WhatsApp reminders after the latest completed billing period is fully paid';

    public function handle(
        WhatsAppFeeNotificationService $notificationService
    ): int {

        $today = Carbon::today();

        $processed = 0;
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        /*
        |--------------------------------------------------------------------------
        | Process active students
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We process the LATEST completed fee for each student only.
        |
        */

        Student::query()
            ->where('active', true)
            ->orderBy('id')
            ->chunkById(
                100,
                function ($students) use (
                    $notificationService,
                    $today,
                    &$processed,
                    &$sent,
                    &$skipped,
                    &$failed
                ) {

                    foreach ($students as $student) {

                        /*
                        |--------------------------------------------------------------------------
                        | Find latest completed fee
                        |--------------------------------------------------------------------------
                        */

                        $completedFee =
                            $student->fees()
                                ->whereDate(
                                    'period_end',
                                    '<',
                                    $today
                                )
                                ->orderByDesc(
                                    'period_end'
                                )
                                ->orderByDesc(
                                    'id'
                                )
                                ->first();

                        /*
                        |--------------------------------------------------------------------------
                        | No completed fee
                        |--------------------------------------------------------------------------
                        */

                        if (!$completedFee) {

                            $skipped++;

                            continue;
                        }

                        $processed++;

                        /*
                        |--------------------------------------------------------------------------
                        | Verify the latest completed fee is fully paid
                        |--------------------------------------------------------------------------
                        */

                        $totalRequired = round(
                            (float) $completedFee->amount
                            + (float) $completedFee->late_fee,
                            2
                        );

                        $allocated = round(
                            (float) $completedFee
                                ->allocations()
                                ->sum('amount'),
                            2
                        );

                        $outstanding = max(
                            0,
                            round(
                                $totalRequired
                                - $allocated,
                                2
                            )
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Latest completed period is not fully paid
                        |--------------------------------------------------------------------------
                        |
                        | Do NOT send the 3-month advance request.
                        |
                        */

                        if ($outstanding > 0.01) {

                            $skipped++;

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Send three-month request
                        |--------------------------------------------------------------------------
                        */

                        $result =
                            $notificationService
                                ->sendThreeMonthAdvanceRequest(
                                    $student,
                                    $completedFee
                                );

                        $status =
                            $result['status']
                            ?? 'failed';

                        if (
                            $status === 'accepted'
                            || $status === 'sent'
                        ) {

                            $sent++;

                        } elseif (
                            $status === 'not_required'
                            || $status === 'already_sent'
                        ) {

                            $skipped++;

                        } else {

                            $failed++;
                        }
                    }
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Console summary
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Three-month WhatsApp reminder scan completed.'
        );

        $this->line(
            'Students processed: '
            . $processed
        );

        $this->line(
            'Messages accepted: '
            . $sent
        );

        $this->line(
            'Skipped: '
            . $skipped
        );

        $this->line(
            'Failed: '
            . $failed
        );

        return self::SUCCESS;
    }
}