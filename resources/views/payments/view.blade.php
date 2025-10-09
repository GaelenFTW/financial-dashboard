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
                        <a href="{{ route('payments.upload') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </a>
                        <a href="{{ route('payments.export', request()->all()) }}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-excel"></i> Export
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
                                    @php
                                        $projects = [
                                            2 => 'CitraGarden City Jakarta (2)', 3 => 'CitraRaya Tangerang (3)', 5 => 'CitraIndah City Jonggol (5)',
                                            9 => 'CitraGran Cibubur (9)', 30 => 'CitraLand Gama City Medan 1 (30)', 31 => 'CitraLand Pekanbaru (31)',
                                            32 => 'CitraGarden Pekanbaru (32)', 35 => 'BizPark Bandung (35)', 36 => 'CitraSun Garden Semarang (36)',
                                            37 => 'CitraSun Garden Yogyakarta (37)', 38 => 'CitraGrand Semarang (38)', 39 => 'CitraLand Surabaya (39)',
                                            41 => 'CitraHarmoni Sidoarjo (41)', 42 => 'CitraGarden Sidoarjo (42)', 43 => 'CitraIndah Sidoarjo (43)',
                                            44 => 'The Taman Dayu (44)', 46 => 'CitraLand Denpasar (46)', 47 => 'CitraLand Kendari (47)',
                                            48 => 'CitraLand Palu (48)', 50 => 'CitraLand Ambon (50)', 51 => 'Ciputra World Surabaya (51)',
                                            54 => 'CitraLand Utara Surabaya (54)', 61 => 'CitraGrand Mutiara Yogyakarta (61)', 62 => 'Vida View Apartemen Makassar (62)',
                                            67 => 'CitraGrand City Palembang (Partner) (67)', 69 => 'CitraLand BSB City (69)', 75 => 'CitraLand Botanical City Pangkal Pinang (75)',
                                            76 => 'Citra BukitIndah Balikpapan (76)', 79 => 'CitraGarden Pekalongan (79)', 80 => 'CitraLand Celebes Makassar (80)',
                                            82 => 'CitraLand NGK Jambi (82)', 83 => 'CitraLand Tegal (83)', 84 => 'CitraRaya Jambi (84)',
                                            87 => 'CitraGarden Gowa (87)', 105 => 'CitraLand The GreenLake (105)', 108 => 'CitraGarden BMW Cilegon (108)',
                                            112 => 'BizPark Pulogadung 2 (112)', 2004 => 'Ciputra World Jakarta 2 - Orchard Satrio (2004)', 2005 => 'Ciputra World Jakarta 2 - Office (2005)',
                                            2006 => 'Ciputra World Jakarta 2 - Residence (2006)', 2013 => 'CitraGarden Lampung (2013)', 2014 => 'Citra Towers Kemayoran Jakarta (2014)',
                                            2015 => 'Citra Living City Jakarta (2015)', 2017 => 'Citra Lake Sawangan Depok (2017)', 2019 => 'BizPark CE Bekasi (2019)',
                                            2020 => 'Ciputra World Jakarta 1 - Residence (2020)', 2021 => 'Ciputra World Jakarta 1 - Raffles (2021)', 2022 => 'Ciputra World Jakarta 1 - Office T1 (2022)',
                                            2023 => 'Ciputra World Jakarta 1 - Office T2 (2023)', 2026 => 'CitraLand Gresik Kota (2026)', 2052 => 'CitraGrand Galesong City Gowa I (2052)',
                                            2053 => 'CitraLand Cirebon (2053)', 2054 => 'CitraLand Megah Batam (2054)', 2055 => 'CitraMitra City Banjarbaru (2055)',
                                            2058 => 'CitraLand Puri Serang I (2058)', 2060 => 'CitraLand Puri Serang II (2060)', 2061 => 'CitraGrand Galesong City Gowa II (2061)',
                                            2069 => 'CitraLand Bandar Lampung (2069)', 2074 => 'The Newton (Project) (2074)', 2075 => 'CitraGrand Cibubur CBD (2075)',
                                            2076 => 'CitraLand Cibubur (2076)', 2077 => 'CitraLand Kairagi Manado (2077)', 2079 => 'CitraLand Palembang (2079)',
                                            2086 => 'Mal Ciputra Tangerang (2086)', 2092 => 'CitraLand Helvetia (2092)', 2093 => 'CitraLand Tanjung Morawa (2093)',
                                            2094 => 'CitraLand City Sampali (2094)', 3020 => 'BizPark Banjarmasin (3020)', 3028 => 'CitraLand City CPI Makassar (3028)',
                                            3031 => 'CitraLand Tallasa City Makassar (3031)', 3032 => 'CitraLand Winangun Manado (3032)', 4029 => 'CitraGarden Aneka Pontianak (4029)',
                                            4030 => 'Citra Aerolink Batam (4030)', 4031 => 'CitraGarden City Samarinda (4031)', 4033 => 'CitraLake Suites Jakarta (4033)',
                                            4034 => 'Citra Maja City (4034)', 4036 => 'CitraGarden City Malang (4036)', 4046 => 'The Newton 2 (Project) (4046)',
                                            4048 => 'Ciputra Beach Resort (4048)', 4056 => 'Ciputra International (Project) (4056)', 4059 => 'CitraLand Banjarmasin (4059)',
                                            4060 => 'CitraPlaza Nagoya Batam (4060)', 4063 => 'Barsa City Yogyakarta (4063)', 4068 => 'Citra Landmark (4068)',
                                            5101 => 'CitraLand Vittorio Wiyung Surabaya (5101)', 5102 => 'CitraGarden Puri Jakarta (5102)', 5103 => 'CitraLand Gama City Medan 2 (5103)',
                                            5104 => 'Citra Sentul Raya (5104)', 5105 => 'Citra City Sentul (5105)', 7105 => 'CitraLand Driyorejo CBD (7105)',
                                            11109 => 'CitraLand Puncak Tidar Malang (11109)', 11124 => 'CitraGrand City Palembang (11124)', 11132 => 'CitraLand City Kedamean (11132)',
                                            11154 => 'CitraLake Villa Jakarta (11154)', 11156 => 'CitraGarden Serpong Tangerang (11156)', 11225 => 'Ciputra World Jakarta 1 - Land (11225)',
                                            11226 => 'Satrio - Land (11226)', 11231 => 'CitraGarden Bekasi (11231)', 11232 => 'CitraLand City CPI Selatan (11232)',
                                            11235 => 'Citra Homes Halim Jakarta (11235)', 11237 => 'Citra Bukit Golf Sentul JO (11237)'
                                        ];

                                    @endphp
                                    @foreach($projects as $id => $name)
                                        <option value="{{ $id }}" {{ request('project_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
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