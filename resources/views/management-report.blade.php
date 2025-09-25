@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Purchase Letters - Management Report</h2>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color: #28a745; color: white;">
                        <th rowspan="3" class="align-middle text-center" style="width: 150px;">PAYMENT SYSTEM</th>
                        <th colspan="4" class="text-center">MONTHLY (MAR 2025)</th>
                        <th colspan="4" class="text-center">YEAR TO DATE</th>
                    </tr>
                    <tr style="background-color: #32cd32; color: white;">
                        <th colspan="2" class="text-center">TARGET & ACTUAL</th>
                        <th colspan="2" class="text-center">ACHIEVEMENT</th>
                        <th colspan="2" class="text-center">TARGET & ACTUAL</th>
                        <th colspan="2" class="text-center">ACHIEVEMENT</th>
                    </tr>
                    <tr style="background-color: #90ee90; color: black;">
                        <th class="text-center">TARGET<br>(Piutang)</th>
                        <th class="text-center">ACTUAL<br>(Payment)</th>
                        <th class="text-center">%</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">TARGET<br>(YTD sd Mar)</th>
                        <th class="text-center">ACTUAL<br>(YTD bayar Mar)</th>
                        <th class="text-center">%</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $type => $data)
                        @php
                            // Safety check - ensure data is array and has required keys
                            if (!is_array($data)) {
                                continue;
                            }
                            
                            $monthlyTarget = isset($data['monthly_target']) ? (float)$data['monthly_target'] : 0;
                            $monthlyActual = isset($data['monthly_actual']) ? (float)$data['monthly_actual'] : 0;
                            $ytdTarget = isset($data['ytd_target']) ? (float)$data['ytd_target'] : 0;
                            $ytdActual = isset($data['ytd_actual']) ? (float)$data['ytd_actual'] : 0;
                            
                            $monthlyPercent = $monthlyTarget > 0 ? ($monthlyActual / $monthlyTarget) * 100 : 0;
                            $ytdPercent = $ytdTarget > 0 ? ($ytdActual / $ytdTarget) * 100 : 0;
                            
                            $monthlyStatus = $monthlyPercent >= 100 ? 'ACHIEVED' : ($monthlyPercent >= 80 ? 'ON TRACK' : 'BELOW TARGET');
                            $ytdStatus = $ytdPercent >= 100 ? 'ACHIEVED' : ($ytdPercent >= 80 ? 'ON TRACK' : 'BELOW TARGET');
                            
                            $monthlyStatusColor = $monthlyPercent >= 100 ? 'text-success' : ($monthlyPercent >= 80 ? 'text-warning' : 'text-danger');
                            $ytdStatusColor = $ytdPercent >= 100 ? 'text-success' : ($ytdPercent >= 80 ? 'text-warning' : 'text-danger');
                        @endphp
                        <tr class="{{ $type === 'TOTAL' ? 'table-warning fw-bold' : '' }}">
                            <td class="fw-bold">{{ $type }}</td>
                            
                            {{-- Monthly Data --}}
                            <td class="text-end">{{ number_format($monthlyTarget, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($monthlyActual, 0, ',', '.') }}</td>
                            <td class="text-center {{ $monthlyStatusColor }}">{{ number_format($monthlyPercent, 1) }}%</td>
                            <td class="text-center {{ $monthlyStatusColor }}">{{ $monthlyStatus }}</td>
                            
                            {{-- YTD Data --}}
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

<style>
    .table th {
        vertical-align: middle;
        text-align: center;
        font-weight: bold;
    }
    .table td {
        vertical-align: middle;
    }
</style>
@endsection