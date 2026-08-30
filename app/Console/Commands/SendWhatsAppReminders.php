<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fee;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class SendWhatsAppReminders extends Command
{
    protected $signature = 'fees:send-whatsapp-reminders';
    protected $description = 'Send safe WhatsApp fee reminders after the fee period has ended';

    public function handle(WhatsAppService $whatsapp): int
    {
        $today = Carbon::today();

        Fee::with('student.bus')
            ->whereDate('period_end', '<', $today)
            ->whereColumn('paid_amount', '<', 'amount')
            ->chunkById(100, function ($fees) use ($whatsapp, $today) {
                foreach ($fees as $fee) {
                    $balance = round((float)$fee->amount + (float)$fee->late_fee - (float)$fee->paid_amount, 2);

                    // Final safety check: never send if there is no due.
                    if ($balance <= 0 || !$fee->student?->whatsapp_number) {
                        continue;
                    }

                    // One due reminder per fee per day.
                    $already = WhatsAppLog::where('fee_id', $fee->id)
                        ->where('message_type', 'due')
                        ->whereDate('created_at', $today)
                        ->exists();

                    if ($already) continue;

                    $result = $whatsapp->sendDueReminder($fee, $balance);

                    WhatsAppLog::create([
                        'student_id' => $fee->student_id,
                        'fee_id' => $fee->id,
                        'phone' => $fee->student->whatsapp_number,
                        'template_name' => $result['template'] ?? null,
                        'message_type' => 'due',
                        'balance_at_send' => $balance,
                        'status' => $result['status'],
                        'provider_message_id' => $result['message_id'] ?? null,
                        'error_message' => $result['error'] ?? null,
                        'sent_at' => $result['status'] === 'sent' ? now() : null,
                    ]);

                    $fee->update([
                        'last_reminder_at' => now(),
                        'reminder_count' => $fee->reminder_count + 1,
                    ]);
                }
            });

        $this->info('Reminder scan completed.');
        return self::SUCCESS;
    }
}
