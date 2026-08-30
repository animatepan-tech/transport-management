@extends('layouts.app')
@section('content')
<h1>Buses <a class="btn" href="{{ route('buses.create') }}">+ Add Bus</a></h1>
<table><tr><th>Bus</th><th>Registration</th><th>Students</th><th>Driver</th><th>License</th><th>PUC</th><th>Action</th></tr>
@foreach($buses as $b)<tr><td>{{ $b->bus_number }}</td><td>{{ $b->registration_number ?: '-' }}</td><td>{{ $b->students_count }}</td><td>{{ $b->driver_name ?: '-' }}</td><td>{{ optional($b->license_expiry)->format('d-m-Y') ?: '-' }}</td><td>{{ optional($b->puc_expiry)->format('d-m-Y') ?: '-' }}</td><td><a class="btn" href="{{ route('buses.edit',$b) }}">Edit</a></td></tr>@endforeach
</table>
@endsection
