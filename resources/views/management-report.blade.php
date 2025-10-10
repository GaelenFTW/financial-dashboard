@php
    $currentYear = $currentYear ?? date('Y');
    $currentMonth = $currentMonth ?? date('n');
@endphp


@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Management Report - Collection Performance</h2>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
            <a href="{{ route('payments.upload.form') }}" class="btn btn-sm btn-primary ms-3">
                <i class="fas fa-upload"></i> Upload Data
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Month & Year Selector --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('management.report') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="year" class="form-label">Year:</label>
                    <select name="year" id="year" class="form-select">
                        @php
                            $availableYears = \App\Models\PurchasePayment::select('data_year')
                                ->distinct()
                                ->orderBy('data_year', 'desc')
                                ->pluck('data_year')
                                ->toArray();
                            
                            if (empty($availableYears)) {
                                $availableYears = [date('Y')];
                            }
                        @endphp
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label">Month:</label>
                    <select name="month" id="month" class="form-select">
                        @foreach ([
                            1 => 'January', 2 => 'February', 3 => 'March',
                            4 => 'April', 5 => 'May', 6 => 'June',
                            7 => 'July', 8 => 'August', 9 => 'September',
                            10 => 'October', 11 => 'November', 12 => 'December'
                        ] as $num => $label)
                            <option value="{{ $num }}" {{ $currentMonth == $num ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                    <div class="col-md-3">
                        <label for="project_id" class="form-label">Project (Optional):</label>
                        <select name="project_id" id="project_id" class="form-select">
                            <option value="">-- All Projects --</option>
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
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> View Report
                        </button>
                        <a href="{{ route('management.report.export', request()->query()) }}" class="btn btn-success w-100">
                            <i class="fas fa-file-export"></i> Export
                        </a>
                    </div>

            </form>
        </div>
    </div>

    @if(!isset($error))
    {{-- MONTHLY PERFORMANCE --}}
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt"></i> 
                MONTHLY PERFORMANCE - {{ date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-warning">
                        <tr>
                            <th rowspan="2">PAYMENT SYSTEM</th>
                            <th rowspan="2">TARGET (Based On Meeting)</th>
                            <th rowspan="2">TARGET (Based on Sales)</th>
                            <th rowspan="2">ACTUAL</th>
                            <th colspan="2" class="text-center">ACHIEVEMENT</th>
                            <th rowspan="2">STATUS</th>
                        </tr>
                        <tr>
                            <th>% (Meeting)</th>
                            <th>% (Sales)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthlyPerformance as $row)
                            <tr class="{{ $row['payment'] === 'TOTAL' ? 'table-warning fw-bold' : '' }}">
                                <td class="text-start">
                                    @if($row['payment'] === 'TOTAL')
                                        <strong>{{ $row['payment'] }}</strong>
                                    @else
                                        {{ $row['payment'] }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($row['meeting_target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['sales_target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['actual'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @php
                                        $pctMeeting = $row['meeting_target'] > 0 ? round(($row['actual'] / $row['meeting_target']) * 100, 1) : 0;
                                    @endphp
                                    <span class="badge {{ $pctMeeting >= 100 ? 'bg-success' : ($pctMeeting >= 80 ? 'bg-info' : 'bg-danger') }}">
                                        {{ number_format($pctMeeting, 1) }}%
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $row['percentage'] >= 100 ? 'bg-success' : ($row['percentage'] >= 80 ? 'bg-info' : 'bg-danger') }}">
                                        {{ number_format($row['percentage'], 1) }}%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $row['status'] == 'ACHIEVED' ? 'bg-success' : ($row['status'] == 'ON TRACK' ? 'bg-info' : 'bg-danger') }}">
                                        @if($row['status'] == 'ACHIEVED')
                                            <i class="fas fa-check-circle"></i>
                                        @elseif($row['status'] == 'ON TRACK')
                                            <i class="fas fa-arrow-up"></i>
                                        @else
                                            <i class="fas fa-exclamation-triangle"></i>
                                        @endif
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- YEAR TO DATE --}}
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line"></i> 
                YEAR TO DATE - January to {{ date('F', mktime(0, 0, 0, $currentMonth, 1)) }} {{ $currentYear }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-success">
                        <tr>
                            <th rowspan="2">PAYMENT SYSTEM</th>
                            <th rowspan="2">TARGET (Based On Meeting)</th>
                            <th rowspan="2">TARGET (Based on Sales)</th>
                            <th rowspan="2">ACTUAL</th>
                            <th colspan="2" class="text-center">ACHIEVEMENT</th>
                            <th rowspan="2">STATUS</th>
                        </tr>
                        <tr>
                            <th>% (Meeting)</th>
                            <th>% (Sales)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ytdPerformance as $row)
                            <tr class="{{ $row['payment'] === 'TOTAL' ? 'table-success fw-bold' : '' }}">
                                <td class="text-start">
                                    @if($row['payment'] === 'TOTAL')
                                        <strong>{{ $row['payment'] }}</strong>
                                    @else
                                        {{ $row['payment'] }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($row['meeting_target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['sales_target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['actual'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @php
                                        $pctMeeting = $row['meeting_target'] > 0 ? round(($row['actual'] / $row['meeting_target']) * 100, 1) : 0;
                                    @endphp
                                    <span class="badge {{ $pctMeeting >= 100 ? 'bg-success' : ($pctMeeting >= 80 ? 'bg-info' : 'bg-danger') }}">
                                        {{ number_format($pctMeeting, 1) }}%
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $row['percentage'] >= 100 ? 'bg-success' : ($row['percentage'] >= 80 ? 'bg-info' : 'bg-danger') }}">
                                        {{ number_format($row['percentage'], 1) }}%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $row['status'] == 'ACHIEVED' ? 'bg-success' : ($row['status'] == 'ON TRACK' ? 'bg-info' : 'bg-danger') }}">
                                        @if($row['status'] == 'ACHIEVED')
                                            <i class="fas fa-check-circle"></i>
                                        @elseif($row['status'] == 'ON TRACK')
                                            <i class="fas fa-arrow-up"></i>
                                        @else
                                            <i class="fas fa-exclamation-triangle"></i>
                                        @endif
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- AGING SCHEDULE --}}
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i> 
                AGING SCHEDULE
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-info">
                        <tr>
                            <th>PAYMENT SYSTEM</th>
                            <th>&lt; 30 DAYS</th>
                            <th>30 - 60 DAYS</th>
                            <th>60 - 90 DAYS</th>
                            <th>&gt; 90 DAYS</th>
                            <th>LEBIH BAYAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($aging as $row)
                            <tr>
                                <td class="text-start">{{ $row['payment'] }}</td>
                                <td class="text-end">{{ number_format($row['lt30'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['d30_60'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['d60_90'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['gt90'], 0, ',', '.') }}</td>
                                <td class="text-end text-danger">
                                    @if($row['lebih_bayar'] > 0)
                                        ({{ number_format($row['lebih_bayar'], 0, ',', '.') }})
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="table-info fw-bold">
                            <td class="text-start"><strong>TOTAL</strong></td>
                            <td class="text-end">{{ number_format($agingTotals['lt30'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($agingTotals['d30_60'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($agingTotals['d60_90'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($agingTotals['gt90'], 0, ',', '.') }}</td>
                            <td class="text-end text-danger">
                                @if($agingTotals['lebih_bayar'] > 0)
                                    ({{ number_format($agingTotals['lebih_bayar'], 0, ',', '.') }})
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- A/R OUTSTANDING --}}
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice-dollar"></i> 
                A/R OUTSTANDING
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>A/R OUTSTANDING</th>
                            <th>Sudah jatuh tempo</th>
                            <th>Belum jatuh tempo</th>
                            <th>Total</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($outstanding as $row)
                            <tr>
                                <td class="text-start"><strong>{{ $row['type'] }}</strong></td>
                                <td class="text-end">{{ number_format($row['jatuh_tempo'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['belum_jatuh_tempo'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-primary">
                                        {{ number_format($row['percentage'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="table-primary fw-bold">
                            <td class="text-start"><strong>TOTAL</strong></td>
                            <td class="text-end">{{ number_format($outstandingTotals['jatuh_tempo'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($outstandingTotals['belum_jatuh_tempo'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($outstandingTotals['total'], 0, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="badge bg-primary">
                                    {{ number_format($outstandingTotals['percentage'], 0) }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="text-uppercase">Monthly Achievement</h6>
                    <h3>{{ number_format($monthlyTotals['percentage'], 1) }}%</h3>
                    <small>{{ $monthlyTotals['status'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6 class="text-uppercase">YTD Achievement</h6>
                    <h3>{{ number_format($ytdTotals['percentage'], 1) }}%</h3>
                    <small>{{ $ytdTotals['status'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h6 class="text-uppercase">Total Outstanding</h6>
                    <h3>Rp {{ number_format($outstandingTotals['total'] / 1000000, 0) }}M</h3>
                    <small>Million Rupiah</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6 class="text-uppercase">Overdue Amount</h6>
                    <h3>Rp {{ number_format($outstandingTotals['jatuh_tempo'] / 1000000, 0) }}M</h3>
                    <small>Million Rupiah</small>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@section('styles')
<style>
    .table th {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .badge {
        font-size: 0.85rem;
        padding: 0.4em 0.65em;
    }
    
    .card-header h5 {
        font-weight: 600;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }
</style>
@endsection