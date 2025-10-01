@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Purchase Letters - Management Report</h2>

    {{-- Month Selector --}}
    <form method="GET" action="{{ route('management.report') }}" class="mb-3">
        <label for="month">Select Month:</label>
        <select name="month" id="month" onchange="this.form.submit()">
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
    </form>

    {{-- MONTHLY PERFORMANCE --}}
    <table class="table table-bordered text-center align-middle">
        <thead class="table-warning">
            <tr>
                <th>PAYMENT</th>
                <th>TARGET (Based On Meeting)</th>
                <th>TARGET (Based on Sales)</th>
                <th>ACTUAL</th>
                <th>%</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthlyPerformance as $row)
                <tr style="{{ $loop->last ? 'font-weight: bold;' : '' }}">
                    <td>{{ trim($row['payment']) }}</td>
                    <td>{{ number_format($row['meeting_target'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['sales_target'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['actual'], 0, ',', '.') }}</td>
                    <td>{{ $row['percentage'] }}%</td>
                    <td class="{{ $row['status'] == 'ACHIEVED' ? 'text-success' : 'text-danger' }}">
                        {{ $row['status'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- YEAR TO DATE --}}
    @php
        // cari nama kolom YTD yang terpakai
        $ytdTargetCol = collect(array_keys($ytdPerformance[0] ?? []))
            ->first(fn($c) => str_starts_with($c, 'YTD_sd_'));
        $ytdActualCol = collect(array_keys($ytdPerformance[0] ?? []))
            ->first(fn($c) => str_starts_with($c, 'YTD_bayar_'));

    @endphp

    <table class="table table-bordered text-center align-middle mt-4">
        <thead class="table-warning">
            <tr>
                <th>PAYMENT</th>
                <th>TARGET (Based On Meeting)</th>
                <th>TARGET (Based on Sales)</th>
                <th>ACTUAL</th>
                <th>%</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ytdPerformance as $row)
                <tr style="{{ $loop->last ? 'font-weight: bold;' : '' }}">
                    <td>{{ $row['payment'] }}</td>
                    <td>{{ number_format($row['meeting_target'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['sales_target'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['actual'], 0, ',', '.') }}</td>
                    <td>{{ $row['percentage'] }}%</td>
                    <td class="{{ $row['status'] == 'ACHIEVED' ? 'text-success' : 'text-danger' }}">
                        {{ $row['status'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    {{-- AGING --}}
    <h4 class="mt-4">AGING</h4>
    <table class="table table-bordered text-center align-middle">
        <thead class="table-warning">
            <tr>
                <th>PAYMENT</th>
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
                    <td>{{ $row['payment'] }}</td>
                    <td>{{ number_format($row['lt30'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['d30_60'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['d60_90'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['gt90'], 0, ',', '.') }}</td>
                    <td>({{ number_format($row['lebih_bayar'], 0, ',', '.') }})</td>
                </tr>
            @endforeach
            <tr class="fw-bold">
                <td>TOTAL</td>
                <td>{{ number_format($agingTotals['lt30'], 0, ',', '.') }}</td>
                <td>{{ number_format($agingTotals['d30_60'], 0, ',', '.') }}</td>
                <td>{{ number_format($agingTotals['d60_90'], 0, ',', '.') }}</td>
                <td>{{ number_format($agingTotals['gt90'], 0, ',', '.') }}</td>
                <td>({{ number_format($agingTotals['lebih_bayar'], 0, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    {{-- A/R OUTSTANDING --}}
    <h4 class="mt-4">A/R OUTSTANDING</h4>
    <table class="table table-bordered text-center align-middle">
        <thead class="table-warning">
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
                    <td>{{ $row['type'] }}</td>
                    <td>{{ number_format($row['jatuh_tempo'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['belum_jatuh_tempo'], 0, ',', '.') }}</td>
                    <td>{{ number_format($row['total'], 0, ',', '.') }}</td>
                    <td>{{ $row['percentage'] }}%</td>
                </tr>
            @endforeach
            <tr class="fw-bold">
                <td>TOTAL</td>
                <td>{{ number_format($outstandingTotals['jatuh_tempo'], 0, ',', '.') }}</td>
                <td>{{ number_format($outstandingTotals['belum_jatuh_tempo'], 0, ',', '.') }}</td>
                <td>{{ number_format($outstandingTotals['total'], 0, ',', '.') }}</td>
                <td>{{ $outstandingTotals['percentage'] }}%</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
