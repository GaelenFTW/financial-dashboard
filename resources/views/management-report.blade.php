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
                            $projects = \App\Models\PurchasePayment::select('project_id')
                                ->distinct()
                                ->whereNotNull('project_id')
                                ->orderBy('project_id')
                                ->pluck('project_id')
                                ->toArray();
                        @endphp
                        @foreach($projects as $proj)
                            <option value="{{ $proj }}" {{ request('project_id') == $proj ? 'selected' : '' }}>
                                Project {{ $proj }}
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