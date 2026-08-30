<?php

namespace App\Http\Controllers;

use App\Services\FeeGenerationService;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    /**
     * Display all generated fees.
     */
    public function index()
    {
        $fees = \App\Models\Fee::query()
            ->with([
                'student.bus',
            ])
            ->orderByDesc('period_start')
            ->orderBy('student_id')
            ->orderByDesc('id')
            ->paginate(25);

        return view(
            'fees.index',
            compact('fees')
        );
    }

    /**
     * Show fee generation page.
     */
    public function showGenerate()
    {
        return view(
            'fees.generate'
        );
    }

    /**
     * Generate fees for active students.
     *
     * After successful fee creation/allocation, the generation
     * service sends the new-fee WhatsApp notification.
     */
    public function generate(
        Request $request,
        FeeGenerationService $feeGenerationService
    ) {
        $data = $request->validate([
            'month' => [
                'required',
                'date_format:Y-m',
            ],

            'billing_type' => [
                'required',
                'in:monthly,quarterly,half_yearly,yearly',
            ],
        ]);

        $result =
            $feeGenerationService->generate(
                $data['month'],
                $data['billing_type']
            );

        $created = (int) (
            $result['created']
            ?? 0
        );

        $skipped = (int) (
            $result['skipped']
            ?? 0
        );

        $skippedBeforeStartDate = (int) (
            $result['skipped_before_start_date']
            ?? 0
        );

        $skippedZeroAmount = (int) (
            $result['skipped_zero_amount']
            ?? 0
        );

        $whatsappSent = (int) (
            $result['whatsapp_sent']
            ?? 0
        );

        $whatsappSkipped = (int) (
            $result['whatsapp_skipped']
            ?? 0
        );

        $whatsappFailed = (int) (
            $result['whatsapp_failed']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Main generation message
        |--------------------------------------------------------------------------
        */

        $message =
            'Fee generation completed. '
            . $created
            . ' new fee record(s) created.';

        /*
        |--------------------------------------------------------------------------
        | Overlapping
        |--------------------------------------------------------------------------
        */

        if ($skipped > 0) {

            $message .=
                ' '
                . $skipped
                . ' student(s) skipped because an overlapping fee already exists.';
        }

        /*
        |--------------------------------------------------------------------------
        | Student start date
        |--------------------------------------------------------------------------
        */

        if ($skippedBeforeStartDate > 0) {

            $message .=
                ' '
                . $skippedBeforeStartDate
                . ' student(s) skipped because the billing period is before their start date.';
        }

        /*
        |--------------------------------------------------------------------------
        | Zero amount
        |--------------------------------------------------------------------------
        */

        if ($skippedZeroAmount > 0) {

            $message .=
                ' '
                . $skippedZeroAmount
                . ' student(s) skipped because their calculated fee amount is zero.';
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp
        |--------------------------------------------------------------------------
        */

        if ($whatsappSent > 0) {

            $message .=
                ' '
                . $whatsappSent
                . ' WhatsApp fee notification(s) accepted by Meta.';
        }

        if ($whatsappSkipped > 0) {

            $message .=
                ' '
                . $whatsappSkipped
                . ' WhatsApp notification(s) were not required or were already sent.';
        }

        if ($whatsappFailed > 0) {

            $message .=
                ' '
                . $whatsappFailed
                . ' WhatsApp notification(s) failed.';
        }

        return redirect()
            ->route(
                'fees.index'
            )
            ->with(
                'success',
                $message
            );
    }
}