@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload Excel File</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('excel.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="excel_file" class="form-label">Choose Excel File</label>
            <input type="file" class="form-control" name="excel_file" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Import</button>
    </form>
</div>
@endsection
