@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="container py-5 text-center">
    <h1 class="display-4 text-danger">403</h1>
    <h3 class="mb-3">Access Denied</h3>
    <p class="text-muted">You don’t have permission to access this page.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">Back to Dashboard</a>
</div>
@endsection
