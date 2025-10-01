@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Purchase Payments</h4>
                    <div>
                        <a href="{{ route('payments.upload') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </a>
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
                    <form method="GET" action="{{ route('payments.view') }}" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="customer" value="{{ request('customer') }}" class="form-control" placeholder="Customer Name">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="cluster" value="{{ request('cluster') }}" class="form-control" placeholder="Cluster">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="TypePembelian" value="{{ request('TypePembelian') }}" class="form-control" placeholder="Type Pembelian">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>

                    {{-- Summary --}}
                    <div class="mb-3">
                        <strong>Total Records:</strong> {{ $payments->total() }}
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Letter ID</th>
                                    <th>Cluster</th>
                                    <th>Block</th>
                                    <th>Unit</th>
                                    <th>Customer</th>
                                    <th>Purchase Date</th>
                                    <th>Type Pembelian</th>
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
                                    <td>{{ $payment->Cluster }}</td>
                                    <td>{{ $payment->Block }}</td>
                                    <td>{{ $payment->Unit }}</td>
                                    <td>{{ $payment->CustomerName }}</td>
                                    <td>{{ $payment->PurchaseDate ? \Carbon\Carbon::parse($payment->PurchaseDate)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $payment->TypePembelian }}</td>
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
                                    <td colspan="12" class="text-center py-4">
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
                        {{ $payments->links('pagination::bootstrap-5') }}
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
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
