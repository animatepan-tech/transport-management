<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'bus_id' => [
                'nullable',
                'integer',
                'exists:buses,id',
            ],

            'status' => [
                'nullable',
                'in:all,active,inactive',
            ],
        ]);

        $search = $data['search'] ?? null;
        $busId = $data['bus_id'] ?? null;
        $status = $data['status'] ?? 'all';


        /*
        |--------------------------------------------------------------------------
        | Student query
        |--------------------------------------------------------------------------
        */

        $students = Student::query()
            ->with('bus')

            ->when(
                $search,
                function ($query) use ($search) {

                    $query->where(function ($q) use ($search) {

                        $q->where(
                            'student_name',
                            'like',
                            '%' . $search . '%'
                        )

                        ->orWhere(
                            'parent_name',
                            'like',
                            '%' . $search . '%'
                        )

                        ->orWhere(
                            'whatsapp_number',
                            'like',
                            '%' . $search . '%'
                        )

                        ->orWhere(
                            'pickup_stop',
                            'like',
                            '%' . $search . '%'
                        );
                    });
                }
            )

            ->when(
                $busId,
                fn ($query) =>
                    $query->where('bus_id', $busId)
            )

            ->when(
                $status === 'active',
                fn ($query) =>
                    $query->where('active', true)
            )

            ->when(
                $status === 'inactive',
                fn ($query) =>
                    $query->where('active', false)
            )

            ->orderBy('student_name')

            ->paginate(25)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Summary statistics
        |--------------------------------------------------------------------------
        */

        $totalStudents = Student::query()
            ->count();


        $activeStudents = Student::query()
            ->where('active', true)
            ->count();


        $inactiveStudents = Student::query()
            ->where('active', false)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Students with due / advance
        |--------------------------------------------------------------------------
        |
        | The Student model calculates these values using:
        |
        | total required
        | total allocated
        | advance
        |
        | These calculations are intentionally based on the complete
        | financial history.
        |
        */

        $accountStudents = Student::query()
            ->where('active', true)
            ->get();


        $studentsWithDue = $accountStudents
            ->filter(
                fn ($student) =>
                    $student->due_amount > 0.01
            )
            ->count();


        $studentsWithAdvance = $accountStudents
            ->filter(
                fn ($student) =>
                    $student->advance_amount > 0.01
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Buses
        |--------------------------------------------------------------------------
        */

        $buses = Bus::query()
            ->orderBy('bus_number')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Return view
        |--------------------------------------------------------------------------
        */

        return view(
            'students.index',
            compact(
                'students',
                'buses',
                'search',
                'busId',
                'status',
                'totalStudents',
                'activeStudents',
                'inactiveStudents',
                'studentsWithDue',
                'studentsWithAdvance'
            )
        );
    }


    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $buses = Bus::query()
            ->where('active', true)
            ->orderBy('bus_number')
            ->get();


        return view(
            'students.form',
            compact('buses')
        );
    }


    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'bus_id' => [
                'nullable',
                'integer',
                'exists:buses,id',
            ],

            'student_name' => [
                'required',
                'string',
                'max:100',
            ],

            'parent_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'pickup_stop' => [
                'nullable',
                'string',
                'max:150',
            ],

            'monthly_fee' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Checkbox handling
        |--------------------------------------------------------------------------
        */

        $data['active'] = $request->boolean('active');


        /*
        |--------------------------------------------------------------------------
        | Create student
        |--------------------------------------------------------------------------
        */

        Student::create($data);


        return redirect()
            ->route('students.index')
            ->with(
                'success',
                'Student added successfully.'
            );
    }


    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student)
    {
        $buses = Bus::query()
            ->where('active', true)
            ->orderBy('bus_number')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Include student's currently assigned bus
        |--------------------------------------------------------------------------
        |
        | If a bus has since become inactive, it should still appear in
        | the edit dropdown so the existing assignment is not lost.
        |
        */

        if (
            $student->bus_id &&
            !$buses->contains('id', $student->bus_id)
        ) {

            $currentBus = Bus::find($student->bus_id);

            if ($currentBus) {

                $buses->push($currentBus);

                $buses = $buses
                    ->sortBy('bus_number')
                    ->values();
            }
        }


        return view(
            'students.form',
            compact(
                'student',
                'buses'
            )
        );
    }


    /**
     * Update the specified student.
     */
    public function update(
        Request $request,
        Student $student
    ) {

        $data = $request->validate([

            'bus_id' => [
                'nullable',
                'integer',
                'exists:buses,id',
            ],

            'student_name' => [
                'required',
                'string',
                'max:100',
            ],

            'parent_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'pickup_stop' => [
                'nullable',
                'string',
                'max:150',
            ],

            'monthly_fee' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Checkbox handling
        |--------------------------------------------------------------------------
        */

        $data['active'] = $request->boolean('active');


        /*
        |--------------------------------------------------------------------------
        | Update student
        |--------------------------------------------------------------------------
        */

        $student->update($data);


        return redirect()
            ->route('students.index')
            ->with(
                'success',
                'Student updated successfully.'
            );
    }


    /**
     * Remove the specified student.
     */
    public function destroy(Student $student)
    {
        /*
        |--------------------------------------------------------------------------
        | Protect financial history
        |--------------------------------------------------------------------------
        */

        if ($student->fees()->exists()) {

            return back()->with(
                'error',
                'This student cannot be deleted because fee records already exist. Mark the student as inactive instead.'
            );
        }


        if ($student->payments()->exists()) {

            return back()->with(
                'error',
                'This student cannot be deleted because payment records already exist. Mark the student as inactive instead.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $student->delete();


        return redirect()
            ->route('students.index')
            ->with(
                'success',
                'Student deleted successfully.'
            );
    }
}

