@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Upload Purchase Payments</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('payments.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="file" class="form-label">Excel File <span class="text-danger">*</span></label>
                            <input type="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   id="file" 
                                   name="file" 
                                   accept=".xlsx,.xls,.csv"
                                   required>
                            <div class="form-text">
                                Accepted formats: .xlsx, .xls, .csv
                            </div>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <strong>Smart Import:</strong> The system will automatically detect and import columns based on your Excel headers.
                            <ul class="mb-0 mt-2">
                                <li><strong>Month columns:</strong> Jan_2025_DueDate, Feb_2025_Type, Mar_2025_Piutang, etc.</li>
                                <li><strong>Before columns:</strong> Amount_Before_Jan_2025, Piutang_Before_Jan_2025, etc.</li>
                                <li><strong>After columns:</strong> Piutang_After_Jun_2025, Payment_After_Jun_2025, etc.</li>
                                <li><strong>YTD columns:</strong> YTD_sd_Jun_2025, YTD_bayar_Jun_2025, etc.</li>
                            </ul>
                            <p class="mt-2 mb-0">The system supports any month/year combination in your headers!</p>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload File
                            </button>
                            <a href="{{ route('payments.view') }}" class="btn btn-secondary">
                                <i class="fas fa-list"></i> View Payments
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection