@extends('layouts.app')
@section('content')
<h1>{{ $bus->exists ? 'Edit Bus' : 'Add Bus' }}</h1>
<form method="post" action="{{ $bus->exists ? route('buses.update',$bus) : route('buses.store') }}" class="card">
@csrf @if($bus->exists) @method('PUT') @endif
<div class="grid">
<div><label>Bus Number *</label><input name="bus_number" value="{{ old('bus_number',$bus->bus_number) }}" required></div>
<div><label>Registration</label><input name="registration_number" value="{{ old('registration_number',$bus->registration_number) }}"></div>
<div><label>Capacity</label><input type="number" name="capacity" value="{{ old('capacity',$bus->capacity ?: 40) }}"></div>
<div><label>Driver Name</label><input name="driver_name" value="{{ old('driver_name',$bus->driver_name) }}"></div>
<div><label>Driver Phone</label><input name="driver_phone" value="{{ old('driver_phone',$bus->driver_phone) }}"></div>
<div><label>License Expiry</label><input type="date" name="license_expiry" value="{{ old('license_expiry',optional($bus->license_expiry)->format('Y-m-d')) }}"></div>
<div><label>PUC Expiry</label><input type="date" name="puc_expiry" value="{{ old('puc_expiry',optional($bus->puc_expiry)->format('Y-m-d')) }}"></div>
<div><label>Insurance Expiry</label><input type="date" name="insurance_expiry" value="{{ old('insurance_expiry',optional($bus->insurance_expiry)->format('Y-m-d')) }}"></div>
</div><label>Notes</label><textarea name="notes">{{ old('notes',$bus->notes) }}</textarea><br><button>Save Bus</button>
</form>
@endsection
