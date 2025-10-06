@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Upload Purchase Payments</h5>
                </div>

                <div class="card-body">

                    {{-- Success Message --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- Validation Errors --}}
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

                        {{-- Data Year --}}
                        <div class="mb-3">
                            <label for="year" class="form-label">Data Year <span class="text-danger">*</span></label>
                            <select name="year" id="year" class="form-select @error('year') is-invalid @enderror" required>
                                <option value="">-- Select Year --</option>
                                @for($y = 2020; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ old('year') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                            @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Data Month --}}
                        <div class="mb-3">
                            <label for="month" class="form-label">Data Month <span class="text-danger">*</span></label>
                            <select name="month" id="month" class="form-select @error('month') is-invalid @enderror" required>
                                <option value="">-- Select Month --</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ old('month') == $y ? 'selected' : '' }}>
                                        {{ $m }}
                                    </option>
                                @endfor
                            </select>
                            @error('month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Project ID --}}
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Project ID <span class="text-danger">*</span></label>
                            <input type="number" name="project_id" id="project_id"
                                   class="form-control @error('project_id') is-invalid @enderror"
                                   value="{{ old('project_id') }}" required placeholder="Enter Project ID">
                            <div class="form-text">Enter the integer Project ID for this data. It will be saved in the database.</div>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Excel File --}}
                        <div class="mb-3">
                            <label for="file" class="form-label">Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Accepted formats: .xlsx, .xls, .csv</div>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Smart Import Info --}}
                        <div class="alert alert-info">
                            <strong>Smart Import:</strong> The system automatically detects columns based on Excel headers.
                            <ul class="mb-0 mt-2">
                                <li><strong>Month columns:</strong> Jan_2025_Piutang, Feb_2025_Payment, etc.</li>
                                <li><strong>Before columns:</strong> Amount_Before_Jan_2025, etc.</li>
                                <li><strong>After columns:</strong> Piutang_After_Jun_2025, Payment_After_Jun_2025, etc.</li>
                                <li><strong>YTD columns:</strong> YTD_sd_Jun_2025, YTD_bayar_Jun_2025, etc.</li>
                            </ul>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
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
