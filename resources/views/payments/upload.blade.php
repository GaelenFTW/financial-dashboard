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
                            <label for="end_date" class="form-label">Target Date (Year & Month) <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control @error('end_date') is-invalid @enderror" 
                                   id="end_date" 
                                   name="end_date" 
                                   value="{{ old('end_date', date('Y-m-t')) }}"
                                   required>
                            <div class="form-text">
                                Select the last month you want to include in the import. 
                                For example, selecting January 30, 2025 will generate columns for month 01 only.
                            </div>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

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
                            <strong>Note:</strong> The system will dynamically generate columns based on your selected date:
                            <ul class="mb-0 mt-2">
                                <li>Columns like <code>01_tahun_DueDate</code>, <code>01_tahun_Type</code>, <code>01_tahun_Piutang</code>, etc.</li>
                                <li>YTD columns will only be created for the last month: <code>YTD_sd_01_tahun</code>, <code>YTD_bayar_01_tahun</code></li>
                            </ul>
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