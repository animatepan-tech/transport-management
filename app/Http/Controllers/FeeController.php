<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Services\FeeGenerationService;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    /**
     * Display all generated fees.
     */
    public function index()
    {
        $fees = Fee::query()
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
        return view('fees.generate');
    }

    /**
     * Generate fees for all active students.
     *
     * FeeGenerationService handles:
     *
     * - billing period calculation
     * - monthly / quarterly / half-yearly / yearly billing
     * - student start-date protection
     * - overlapping fee protection
     * - fee creation
     * - existing advance payment allocation
     * - fee paid amount updates
     * - fee status updates
     */
    public function generate(
        Request $request,
        FeeGenerationService $feeGenerationService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Generate fees
        |--------------------------------------------------------------------------
        |
        | The service performs the actual generation and returns
        | generation statistics.
        |
        */

        $result = $feeGenerationService->generate(
            $data['month'],
            $data['billing_type']
        );

        /*
        |--------------------------------------------------------------------------
        | Read result counters safely
        |--------------------------------------------------------------------------
        */

        $created = (int) (
            $result['created'] ?? 0
        );

        $skipped = (int) (
            $result['skipped'] ?? 0
        );

        $skippedBeforeStartDate = (int) (
            $result['skipped_before_start_date'] ?? 0
        );

        $skippedZeroAmount = (int) (
            $result['skipped_zero_amount'] ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Build success message
        |--------------------------------------------------------------------------
        */

        $message =
            'Fee generation completed. '
            . $created
            . ' new fee record(s) created.';

        /*
        |--------------------------------------------------------------------------
        | Overlapping fees
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
        | Zero monthly fee
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
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('fees.index')
            ->with(
                'success',
                $message
            );
    }
}