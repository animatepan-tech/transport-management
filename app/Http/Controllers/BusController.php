<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use Illuminate\Http\Request;

class BusController extends Controller
{
    /**
     * Display all buses.
     */
    public function index()
    {
        $buses = Bus::query()
            ->withCount('students')
            ->orderBy('bus_number')
            ->get();

        return view(
            'buses.index',
            compact('buses')
        );
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view(
            'buses.form',
            [
                'bus' => new Bus(),
            ]
        );
    }

    /**
     * Store a new bus.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'bus_number' => [
                'required',
                'string',
                'max:50',
                'unique:buses,bus_number',
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'capacity' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'driver_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'driver_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'license_expiry' => [
                'nullable',
                'date',
            ],

            'puc_expiry' => [
                'nullable',
                'date',
            ],

            'insurance_expiry' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Default capacity
        |--------------------------------------------------------------------------
        */

        $data['capacity'] = $data['capacity'] ?? 40;

        /*
        |--------------------------------------------------------------------------
        | Create bus
        |--------------------------------------------------------------------------
        */

        Bus::create($data);

        return redirect()
            ->route('buses.index')
            ->with(
                'success',
                'Bus added successfully.'
            );
    }

    /**
     * Show edit form.
     */
    public function edit(Bus $bus)
    {
        return view(
            'buses.form',
            compact('bus')
        );
    }

    /**
     * Update an existing bus.
     */
    public function update(
        Request $request,
        Bus $bus
    ) {
        $data = $request->validate([
            'bus_number' => [
                'required',
                'string',
                'max:50',
                'unique:buses,bus_number,' . $bus->id,
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'capacity' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'driver_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'driver_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'license_expiry' => [
                'nullable',
                'date',
            ],

            'puc_expiry' => [
                'nullable',
                'date',
            ],

            'insurance_expiry' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Keep existing capacity if no new value is supplied.
        |--------------------------------------------------------------------------
        */

        if (
            !array_key_exists('capacity', $data)
            || $data['capacity'] === null
        ) {
            $data['capacity'] = $bus->capacity ?? 40;
        }

        /*
        |--------------------------------------------------------------------------
        | Update bus
        |--------------------------------------------------------------------------
        */

        $bus->update($data);

        return redirect()
            ->route('buses.index')
            ->with(
                'success',
                'Bus updated successfully.'
            );
    }

    /**
     * Delete a bus.
     */
    public function destroy(Bus $bus)
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent deleting a bus that still has students assigned.
        |--------------------------------------------------------------------------
        |
        | This is safer than allowing a bus to disappear while students
        | are still assigned to it.
        |
        */

        if ($bus->students()->exists()) {
            return back()
                ->with(
                    'error',
                    'This bus cannot be deleted because students are currently assigned to it.'
                );
        }

        $bus->delete();

        return back()
            ->with(
                'success',
                'Bus deleted successfully.'
            );
    }
}