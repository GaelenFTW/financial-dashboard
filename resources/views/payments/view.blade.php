{{-- resources/views/payments/view.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Purchase Payments</h4>
                        <div>
                            @if(auth()->user()->canUpload())
                                <a href="{{ route('payments.upload') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-upload"></i> Upload
                                </a>
                            @endif
                            @if(auth()->user()->canExport())
                                <a href="{{ route('payments.export', request()->all()) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-file-excel"></i> Export
                                </a>
                            @endif
                        </div>
                </div>

                <div class="card-body">

                    {{-- Success / Error Messages --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
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

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('payments.view') }}" class="mb-4">
                        <div class="row g-3">
                            {{-- Year Filter --}}
                            <div class="col-md-2">
                                <label class="form-label">Year</label>
                                <select name="year" class="form-select">
                                    @for($y = date('Y') + 1; $y >= 2020; $y--)
                                        <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            {{-- Month Filter --}}
                            <div class="col-md-2">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-select">
                                    <option value="">All Months</option> 
                                    @php
                                        $months = [
                                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                        ];
                                    @endphp
                                    @foreach($months as $num => $name)
                                        <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Project Filter --}}
                            <div class="col-md-3">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select">
                                    <option value="">All Projects</option>
                                    @foreach(($projects ?? []) as $id => $name)
                                        <option value="{{ $id }}" {{ (string)request('project_id') === (string)$id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(empty($projects))
                                    <small class="text-muted">No projects loaded.</small>
                                @endif
                            </div>

                            {{-- Customer Name Filter --}}
                            <div class="col-md-2">
                                <label class="form-label">Customer</label>
                                <input type="text" name="customer" value="{{ request('customer') }}" class="form-control" placeholder="Customer Name">
                            </div>

                            {{-- Cluster Filter --}}
                            <div class="col-md-2">
                                <label class="form-label">Cluster</label>
                                <input type="text" name="cluster" value="{{ request('cluster') }}" class="form-control" placeholder="Cluster">
                            </div>

                            {{-- Type Pembelian Filter --}}
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select name="TypePembelian" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="CASH" {{ request('TypePembelian') == 'CASH' ? 'selected' : '' }}>CASH</option>
                                    <option value="KPR" {{ request('TypePembelian') == 'KPR' ? 'selected' : '' }}>KPR</option>
                                    <option value="INHOUSE" {{ request('TypePembelian') == 'INHOUSE' ? 'selected' : '' }}>INHOUSE</option>
                                </select>
                            </div>

                            {{-- Filter Button --}}
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>

                            {{-- Reset Button --}}
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="{{ route('payments.view') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Summary --}}
                    <div class="mb-3">
                        <strong>Total Records:</strong> {{ $payments->total() }}
                        @if(request('year') || request('month') || request('project_id'))
                            <span class="text-muted">
                                | Filtered by: 
                                @if(request('year'))
                                    <span class="badge bg-info">Year: {{ request('year') }}</span>
                                @endif
                                @if(request('month'))
                                    <span class="badge bg-info">Month: {{ $months[request('month')] ?? request('month') }}</span>
                                @endif
                                @if(request('project_id'))
                                    <span class="badge bg-info">Project: {{ $projects[request('project_id')] ?? request('project_id') }}</span>
                                @endif
                            </span>
                        @endif
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Letter ID</th>
                                    <th>Year</th>
                                    <th>Month</th>
                                    <th>Project</th>
                                    <th>Cluster</th>
                                    <th>Block</th>
                                    <th>Unit</th>
                                    <th>Customer</th>
                                    <th>Purchase Date</th>
                                    <th>Type</th>
                                    <th>Unit Type</th>
                                    <th>Salesman</th>
                                    <th class="text-end">Harga Jual Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $index => $payment)
                                <tr>
                                    <td>{{ $payments->firstItem() + $index }}</td>
                                    <td>{{ $payment->purchaseletter_id }}</td>
                                    <td>{{ $payment->data_year }}</td>
                                    <td>{{ $months[$payment->data_month] ?? $payment->data_month }}</td>
                                    <td>
                                        <small>{{ $projects[$payment->project_id] ?? $payment->project_id }}</small>
                                    </td>
                                    <td>{{ $payment->Cluster }}</td>
                                    <td>{{ $payment->Block }}</td>
                                    <td>{{ $payment->Unit }}</td>
                                    <td>{{ $payment->CustomerName }}</td>
                                    <td>{{ $payment->PurchaseDate ? \Carbon\Carbon::parse($payment->PurchaseDate)->format('d-m-Y') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $payment->TypePembelian == 'CASH' ? 'success' : ($payment->TypePembelian == 'KPR' ? 'primary' : 'warning') }}">
                                            {{ $payment->TypePembelian }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->type_unit }}</td>
                                    <td>{{ $payment->Salesman }}</td>
                                    <td class="text-end">Rp {{ number_format($payment->HrgJualTotal ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal{{ $payment->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="15" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No payments found. Try adjusting your filters or upload new data.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end">
                        {{ $payments->appends(request()->all())->links('pagination::bootstrap-5') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Detail Modals --}}
@foreach($payments as $payment)
<div class="modal fade" id="detailModal{{ $payment->id }}" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Detail - {{ $payment->purchaseletter_id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- Left Column --}}
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2">Basic Information</h6>
                        <table class="table table-sm">
                            <tr><th>Customer:</th><td>{{ $payment->CustomerName }}</td></tr>
                            <tr><th>Cluster:</th><td>{{ $payment->Cluster }}</td></tr>
                            <tr><th>Block/Unit:</th><td>{{ $payment->Block }}/{{ $payment->Unit }}</td></tr>
                            <tr><th>Type Unit:</th><td>{{ $payment->type_unit }}</td></tr>
                            <tr><th>Purchase Date:</th><td>{{ $payment->PurchaseDate ? \Carbon\Carbon::parse($payment->PurchaseDate)->format('d-m-Y') : '-' }}</td></tr>
                            <tr><th>Lunas Date:</th><td>{{ $payment->LunasDate ? \Carbon\Carbon::parse($payment->LunasDate)->format('d-m-Y') : '-' }}</td></tr>
                            <tr><th>Data Year:</th><td>{{ $payment->data_year }}</td></tr>
                            <tr><th>Data Month:</th><td>{{ $months[$payment->data_month] ?? $payment->data_month }}</td></tr>
                            <tr><th>Created By:</th><td>{{ $payment->created_by ?? '-' }}</td></tr>
                            <tr><th>Updated By:</th><td>{{ $payment->updated_by ?? '-' }}</td></tr>
                        </table>
                    </div>

                    {{-- Right Column --}}
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2">Financial Information</h6>
                        <table class="table table-sm">
                            <tr><th>Harga Netto:</th><td class="text-end">Rp {{ number_format($payment->harga_netto ?? 0, 0, ',', '.') }}</td></tr>
                            <tr><th>Total PPN:</th><td class="text-end">Rp {{ number_format($payment->TotalPPN ?? 0, 0, ',', '.') }}</td></tr>
                            <tr><th>Harga Jual Total:</th><td class="text-end"><strong>Rp {{ number_format($payment->HrgJualTotal ?? 0, 0, ',', '.') }}</strong></td></tr>
                            <tr><th>Disc Collection:</th><td class="text-end">Rp {{ number_format($payment->disc_collection ?? 0, 0, ',', '.') }}</td></tr>
                            <tr><th>Type Pembelian:</th><td>{{ $payment->TypePembelian }}</td></tr>
                            <tr><th>Bank Induk:</th><td>{{ $payment->bank_induk }}</td></tr>
                            <tr><th>Salesman:</th><td>{{ $payment->Salesman }}</td></tr>
                            <tr><th>Member:</th><td>{{ $payment->Member }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection