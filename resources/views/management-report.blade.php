@extends('layouts.app')

@section('styles')
<style>
    /* Table styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    th, td {
        padding: 8px 12px;
        border: 1px solid #ddd;
    }
    th {
        background-color: #f7f7f7;
        text-align: center;
    }
    td {
        text-align: right;
    }
    td:first-child, th:first-child {
        text-align: left;
    }
    tr.total-row {
        font-weight: bold;
        background-color: #f2f2f2;
    }

    /* Summary cards */
    .summary-cards {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }
    .card {
        flex: 1;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        background-color: #fff;
        text-align: center;
    }
    .card h4 {
        margin-bottom: 10px;
        color: #555;
    }
    .card p {
        font-size: 1.5em;
        font-weight: bold;
        margin: 0;
    }
</style>
@endsection

@section('content')
<div class="container">
    <h2>Management Report - Collection Performance</h2>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }} Upload Data</div>
    @endif

    {{-- Month & Year Selector --}}
    <form method="GET" action="{{ route('management.report') }}" class="mb-4">
        <label>Year:</label>
        <select name="year">
            @for($y = date('Y')-5; $y <= date('Y'); $y++)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>

        <label>Month:</label>
        <select name="month">
            @foreach($monthNames as $num => $name)
                <option value="{{ $num }}" {{ $num == $month ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>

        <button type="submit">View Report</button>
    </form>

    {{-- SUMMARY CARDS --}}
    <div class="summary-cards">
        <div class="card">
            <h4>Monthly Achievement</h4>
            <p>{{ number_format($monthlyTotals['percentage'], 1) }}%</p>
            <small>{{ $monthlyTotals['status'] }}</small>
        </div>
        <div class="card">
            <h4>YTD Achievement</h4>
            <p>{{ number_format($ytdTotals['percentage'], 1) }}%</p>
            <small>{{ $ytdTotals['status'] }}</small>
        </div>
        <div class="card">
            <h4>Total Outstanding</h4>
            <p>Rp {{ number_format($outstandingTotals['total']/1000000, 0, ',', '.') }}M</p>
            <small>Million Rupiah</small>
        </div>
        <div class="card">
            <h4>Overdue Amount</h4>
            <p>Rp {{ number_format($outstandingTotals['jatuh_tempo']/1000000, 0, ',', '.') }}M</p>
            <small>Million Rupiah</small>
        </div>
    </div>

    {{-- MONTHLY PERFORMANCE --}}
    <h3>Monthly Performance - {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</h3>
    <table>
        <thead>
            <tr>
                <th>Payment System</th>
                <th>Target</th>
                <th>Actual</th>
                <th>Achievement (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthlyPerformance as $row)
                <tr>
                    <td>{{ $row->TypePembelian }}</td>
                    <td>{{ number_format($row->target, 0, ',', '.') }}</td>
                    <td>{{ number_format($row->actual, 0, ',', '.') }}</td>
                    <td>{{ $row->achievement }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- YTD PERFORMANCE --}}
    <h3>Year to Date - January to {{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}</h3>
    <table>
        <thead>
            <tr>
                <th>Payment System</th>
                <th>Target</th>
                <th>Actual</th>
                <th>Achievement (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ytdPerformance as $row)
                <tr>
                    <td>{{ $row->TypePembelian }}</td>
                    <td>{{ number_format($row->target, 0, ',', '.') }}</td>
                    <td>{{ number_format($row->actual, 0, ',', '.') }}</td>
                    <td>{{ $row->achievement }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- AGING SCHEDULE Placeholder --}}
    <h3>Aging Schedule</h3>
    <table>
        <thead>
            <tr>
                <th>Payment System</th>
                <th>< 30 Days</th>
                <th>30-60 Days</th>
                <th>60-90 Days</th>
                <th>> 90 Days</th>
                <th>Overpaid</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($aging as $row)
                <tr>
                    <td>{{ $row['payment'] }}</td>
                    <td>{{ number_format($row['lt30'],0,',','.') }}</td>
                    <td>{{ number_format($row['d30_60'],0,',','.') }}</td>
                    <td>{{ number_format($row['d60_90'],0,',','.') }}</td>
                    <td>{{ number_format($row['gt90'],0,',','.') }}</td>
                    <td>{{ $row['lebih_bayar'] > 0 ? '('.number_format($row['lebih_bayar'],0,',','.').')' : '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td>{{ number_format($agingTotals['lt30'],0,',','.') }}</td>
                <td>{{ number_format($agingTotals['d30_60'],0,',','.') }}</td>
                <td>{{ number_format($agingTotals['d60_90'],0,',','.') }}</td>
                <td>{{ number_format($agingTotals['gt90'],0,',','.') }}</td>
                <td>{{ $agingTotals['lebih_bayar'] > 0 ? '('.number_format($agingTotals['lebih_bayar'],0,',','.').')' : '-' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- A/R OUTSTANDING Placeholder --}}
    <h3>A/R Outstanding</h3>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Due</th>
                <th>Not Due</th>
                <th>Total</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($outstanding as $row)
                <tr>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ number_format($row['jatuh_tempo'],0,',','.') }}</td>
                    <td>{{ number_format($row['belum_jatuh_tempo'],0,',','.') }}</td>
                    <td>{{ number_format($row['total'],0,',','.') }}</td>
                    <td>{{ number_format($row['percentage'],1) }}%</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td>{{ number_format($outstandingTotals['jatuh_tempo'],0,',','.') }}</td>
                <td>{{ number_format($outstandingTotals['belum_jatuh_tempo'],0,',','.') }}</td>
                <td>{{ number_format($outstandingTotals['total'],0,',','.') }}</td>
                <td>{{ number_format($outstandingTotals['percentage'],0) }}%</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
