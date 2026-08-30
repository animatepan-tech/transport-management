<?php

namespace App\Http\Controllers;

use App\Models\Student;

class StudentAccountController extends Controller
{
    public function show(Student $student)
    {
        $student->load([
            'bus',
            'fees' => function ($query) {
                $query->orderByDesc('period_start');
            },
            'payments' => function ($query) {
                $query->with('allocations.fee')
                    ->orderByDesc('payment_date')
                    ->orderByDesc('id');
            },
        ]);

        return view(
            'students.account',
            compact('student')
        );
    }
}