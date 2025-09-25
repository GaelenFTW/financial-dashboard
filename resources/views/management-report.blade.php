@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Purchase Letters - Management Report</h2>

    @if(isset($error))
    <div class="alert alert-danger">{{ $error }}</div>
    @else
        {{-- Monthly Table --}}
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color: #28a745; color: white;">
                        <th rowspan="2" class="align-middle text-center" style="width: 150px;">PAYMENT</th>
                    </tr>
                    <form method="GET" action="{{ url()->current() }}" class="mb-3">
                        <label for="month">Select Month:</label>
                        <select name="month" id="month" onchange="this.form.submit()" class="form-select" style="width: 200px; display:inline-block;">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    <tr style="background-color: #90ee90; color: black;">
                        <th class="text-center">TARGET(Based On Meeting)</th>
                        <th class="text-center">TARGET (Based on Sales)</th>
                        <th class="text-center">ACTUAL</th>
                        <th class="text-center">%</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $type => $data)
                        @php
                            $martarget = isset($data['mar_target']) ? (float)$data['mar_target'] : 0;
                            $maractual = isset($data['mar_actual']) ? (float)$data['mar_actual'] : 0;
                            $monthlyPercent = $martarget > 0 ? ($maractual / $martarget) * 100 : 0;
                            $monthlyStatus = $monthlyPercent >= 100 ? 'ACHIEVED' : ($monthlyPercent >= 80 ? 'ON TRACK' : 'BELOW TARGET');
                            $monthlyStatusColor = $monthlyPercent >= 100 ? 'text-success' : ($monthlyPercent >= 80 ? 'text-warning' : 'text-danger');
                            $targets = $collectionTargets[$currentMonth] ?? ['cash' => 0, 'inhouse' => 0, 'kpr' => 0];
                        @endphp
                        <tr class="{{ $type === 'TOTAL' ? 'table-warning fw-bold' : '' }}">
                            <td class="fw-bold">{{ $type }}</td>
                            <td class="text-end">upcoming</td>
                            <td class="text-end">{{ number_format($martarget, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($maractual, 0, ',', '.') }}</td>
                            <td class="text-center {{ $monthlyStatusColor }}">{{ number_format($monthlyPercent, 1) }}%</td>
                            <td class="text-center {{ $monthlyStatusColor }}">{{ $monthlyStatus }}</td>
                        </tr>
                        
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Year to Date Table --}}
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color: #28a745; color: white;">
                        <th rowspan="2" class="align-middle text-center" style="width: 150px;">PAYMENT SYSTEM</th>
                        <th colspan="5" class="text-center">YEAR TO DATE</th>
                    </tr>
                    <tr style="background-color: #90ee90; color: black;">
                        <th class="text-center">TARGET(Based On Meeting)</th>
                        <th class="text-center">TARGET(Based On Sales)</th>
                        <th class="text-center">ACTUAL</th>
                        <th class="text-center">%</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $type => $data)
                        @php
                            $ytdTarget = isset($data['ytd_target']) ? (float)$data['ytd_target'] : 0;
                            $ytdActual = isset($data['ytd_actual']) ? (float)$data['ytd_actual'] : 0;
                            $ytdPercent = $ytdTarget > 0 ? ($ytdActual / $ytdTarget) * 100 : 0;
                            $ytdStatus = $ytdPercent >= 100 ? 'ACHIEVED' : ($ytdPercent >= 80 ? 'ON TRACK' : 'BELOW TARGET');
                            $ytdStatusColor = $ytdPercent >= 100 ? 'text-success' : ($ytdPercent >= 80 ? 'text-warning' : 'text-danger');
                        @endphp
                        <tr class="{{ $type === 'TOTAL' ? 'table-warning fw-bold' : '' }}">
                            <td class="fw-bold">{{ $type }}</td>
                            <td class="text-end">upcoming</td>
                            <td class="text-end">{{ number_format($ytdTarget, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($ytdActual, 0, ',', '.') }}</td>
                            <td class="text-center {{ $ytdStatusColor }}">{{ number_format($ytdPercent, 1) }}%</td>
                            <td class="text-center {{ $ytdStatusColor }}">{{ $ytdStatus }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Aging Table --}}
@if(isset($summary))
<div class="mt-5">
    <h3>AGING</h3>
    <table class="table table-bordered text-end">
        <thead class="table-warning text-center">
            <tr>
                <th class="text-start">PAYMENT SYSTEM</th>
                <th>&lt; 30 DAYS</th>
                <th>30 - 60 DAYS</th>
                <th>60 - 90 DAYS</th>
                <th>&gt; 90 DAYS</th>
                <th>LEBIH BAYAR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $type => $data)
                @if($type !== 'TOTAL')
                <tr>
                    <td class="text-start">{{ $type }}</td>
                    <td>{{ number_format($data['less30days'], 0, ',', '.') }}</td>
                    <td>{{ number_format($data['more31days'], 0, ',', '.') }}</td>
                    <td>{{ number_format($data['more61days'], 0, ',', '.') }}</td>
                    <td>{{ number_format($data['more90days'], 0, ',', '.') }}</td>
                    <td>{{ number_format($data['lebihbayar'], 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach

            {{-- Totals --}}
            @php $total = $summary['TOTAL']; @endphp
            <tr class="fw-bold table-warning">
                <td class="text-start">TOTAL</td>
                <td>{{ number_format($total['less30days'], 0, ',', '.') }}</td>
                <td>{{ number_format($total['more31days'], 0, ',', '.') }}</td>
                <td>{{ number_format($total['more61days'], 0, ',', '.') }}</td>
                <td>{{ number_format($total['more90days'], 0, ',', '.') }}</td>
                <td>{{ number_format($total['lebihbayar'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- OUTSTANDING A/R --}}
@if(isset($outstanding) && count($outstanding) > 0)
<div class="mt-5">
    <h3>A/R OUTSTANDING</h3>
    <table class="table table-bordered text-end align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-start">A/R OUTSTANDING</th>
                <th>Sudah jatuh tempo</th>
                <th>Belum jatuh tempo</th>
                <th>Total</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotal = $outstanding['TOTAL']['total'] ?? 0;
            @endphp

            @foreach($outstanding as $key => $row)
                @if($key !== 'TOTAL')
                    @php
                        $jatuh = $row['jatuh_tempo'] ?? 0;
                        $belum = $row['belum_jatuh_tempo'] ?? 0;
                        $sum   = $row['total'] ?? 0;
                        $pct   = $grandTotal > 0 ? round(($sum / $grandTotal) * 100) : 0;
                    @endphp
                    <tr>
                        <td class="text-start fw-bold">{{ $key }}</td>
                        <td>{{ number_format($jatuh, 0, ',', '.') }}</td>
                        <td>{{ number_format($belum, 0, ',', '.') }}</td>
                        <td>{{ number_format($sum, 0, ',', '.') }}</td>
                        <td>{{ $pct }}%</td>
                    </tr>
                @endif
            @endforeach

            {{-- TOTAL row --}}
            @php
                $jatuh = $outstanding['TOTAL']['jatuh_tempo'] ?? 0;
                $belum = $outstanding['TOTAL']['belum_jatuh_tempo'] ?? 0;
                $sum   = $outstanding['TOTAL']['total'] ?? 0;
            @endphp
            <tr class="table-secondary fw-bold">
                <td class="text-start">TOTAL</td>
                <td>{{ number_format($jatuh, 0, ',', '.') }}</td>
                <td>{{ number_format($belum, 0, ',', '.') }}</td>
                <td>{{ number_format($sum, 0, ',', '.') }}</td>
                <td>100%</td>
            </tr>
        </tbody>
    </table>
</div>
@endif


@endsection