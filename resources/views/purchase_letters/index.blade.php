@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Purchase Letters</h2>
            <div>
                <a href="{{ route('purchase_letters.chart') }}" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i> View Charts
                </a>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Search --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('purchase_letters.index') }}" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control"
                               placeholder="Search by customer name, cluster, unit, type pembelian, or purchase date...">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    @if($search)
                        <a href="{{ route('purchase_letters.index') }}" class="btn btn-secondary w-100 mt-2">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <h6 class="text-muted">Total Records</h6>
                    <h4>{{ number_format($letters->total()) }}</h4>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Current Page</h6>
                    <h4>{{ $letters->currentPage() }} of {{ $letters->lastPage() }}</h4>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Showing</h6>
                    <h4>{{ $letters->firstItem() ?? 0 }} - {{ $letters->lastItem() ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Purchase Letter ID</th>
                            <th>Customer Name</th>
                            <th>Cluster</th>
                            <th>Block</th>
                            <th>Unit</th>
                            <th>Purchase Date</th>
                            <th>Lunas Date</th>
                            <th>Type Pembelian</th>
                            <th>Harga Jual Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($letters as $index => $letter)
                        <tr>
                            <td>{{ $letters->firstItem() + $index }}</td>
                            <td><strong>{{ $letter->purchaseletter_id }}</strong></td>
                            <td>{{ $letter->CustomerName }}</td>
                            <td>{{ $letter->Cluster }}</td>
                            <td>{{ $letter->Block }}</td>
                            <td>{{ $letter->Unit }}</td>
                            <td>{{ $letter->PurchaseDate ? \Carbon\Carbon::parse($letter->PurchaseDate)->format('d-m-Y') : '-' }}</td>
                            <td>
                                @if($letter->LunasDate)
                                    <span class="badge bg-success">{{ \Carbon\Carbon::parse($letter->LunasDate)->format('d-m-Y') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">Not Paid</span>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $letter->TypePembelian }}</span></td>
                            <td class="text-end"><strong>Rp {{ number_format($letter->HrgJualTotal ?? 0, 0, ',', '.') }}</strong></td>
                            <td>
                                @if($letter->LunasDate)
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span>
                                @else
                                    @php
                                        $isOverdue = $letter->PurchaseDate 
                                            ? \Carbon\Carbon::parse($letter->PurchaseDate)->lt(\Carbon\Carbon::now()) 
                                            : false;
                                    @endphp
                                    @if($isOverdue)
                                        <span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> Overdue</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Open</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal{{ $letter->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No purchase letters found.</p>
                                @if($search)
                                    <a href="{{ route('purchase_letters.index') }}" class="btn btn-primary">Clear Search</a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing {{ $letters->firstItem() ?? 0 }} to {{ $letters->lastItem() ?? 0 }} of {{ $letters->total() }} entries
                    @if($search)
                        <span class="text-muted">(filtered from search: "{{ $search }}")</span>
                    @endif
                </div>
                <div>{{ $letters->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Detail Modals (outside table) --}}
@foreach($letters as $letter)
<div class="modal fade" id="detailModal{{ $letter->id }}" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-alt"></i> Purchase Letter Detail - {{ $letter->purchaseletter_id }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- Customer Info --}}
                    <div class="col-md-6 mb-4">
                        <h6 class="border-bottom pb-2"><i class="fas fa-user"></i> Customer Info</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Customer Name:</th><td>{{ $letter->CustomerName }}</td></tr>
                            <tr><th>Salesman:</th><td>{{ $letter->Salesman ?? '-' }}</td></tr>
                            <tr><th>Member:</th><td>{{ $letter->Member ?? '-' }}</td></tr>
                        </table>
                    </div>

                    {{-- Property Info --}}
                    <div class="col-md-6 mb-4">
                        <h6 class="border-bottom pb-2"><i class="fas fa-home"></i> Property Info</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Cluster:</th><td>{{ $letter->Cluster }}</td></tr>
                            <tr><th>Block:</th><td>{{ $letter->Block }}</td></tr>
                            <tr><th>Unit:</th><td>{{ $letter->Unit }}</td></tr>
                            <tr><th>Type Unit:</th><td>{{ $letter->type_unit ?? '-' }}</td></tr>
                        </table>
                    </div>

                    {{-- Purchase Info --}}
                    <div class="col-md-6 mb-4">
                        <h6 class="border-bottom pb-2"><i class="fas fa-calendar"></i> Purchase Info</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Purchase Date:</th><td>{{ $letter->PurchaseDate ? \Carbon\Carbon::parse($letter->PurchaseDate)->format('d M Y') : '-' }}</td></tr>
                            <tr><th>Lunas Date:</th><td>{{ $letter->LunasDate ? \Carbon\Carbon::parse($letter->LunasDate)->format('d M Y') : 'Not Paid' }}</td></tr>
                            <tr><th>Tanggal Akad:</th><td>{{ $letter->tanggal_akad ? \Carbon\Carbon::parse($letter->tanggal_akad)->format('d M Y') : '-' }}</td></tr>
                            <tr><th>Type Pembelian:</th><td><span class="badge bg-info">{{ $letter->TypePembelian }}</span></td></tr>
                        </table>
                    </div>

                    {{-- Financial Info --}}
                    <div class="col-md-6 mb-4">
                        <h6 class="border-bottom pb-2"><i class="fas fa-dollar-sign"></i> Financial Info</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Harga Netto:</th><td class="text-end">Rp {{ number_format($letter->harga_netto ?? 0, 0, ',', '.') }}</td></tr>
                            <tr><th>Total PPN:</th><td class="text-end">Rp {{ number_format($letter->TotalPPN ?? 0, 0, ',', '.') }}</td></tr>
                            <tr><th>Disc Collection:</th><td class="text-end">Rp {{ number_format($letter->disc_collection ?? 0, 0, ',', '.') }}</td></tr>
                            <tr class="border-top"><th>Harga Jual Total:</th><td class="text-end"><strong class="text-primary fs-5">Rp {{ number_format($letter->HrgJualTotal ?? 0, 0, ',', '.') }}</strong></td></tr>
                        </table>
                    </div>

                    {{-- Additional Info --}}
                    <div class="col-md-12">
                        <h6 class="border-bottom pb-2"><i class="fas fa-info-circle"></i> Additional Info</h6>
                        <div class="row">
                            <div class="col-md-4"><small class="text-muted">Bank Induk:</small><p>{{ $letter->bank_induk ?? '-' }}</p></div>
                            <div class="col-md-4"><small class="text-muted">Jenis KPR:</small><p>{{ $letter->JenisKPR ?? '-' }}</p></div>
                            <div class="col-md-4"><small class="text-muted">Progress Bangun:</small><p>{{ $letter->persen_progress_bangun ?? 0 }}%</p></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
@endforeach
@endsection
