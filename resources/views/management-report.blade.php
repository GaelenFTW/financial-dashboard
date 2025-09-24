@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Purchase Letters - Management Report</h2>
    <h4 class="mb-4">Monthly & Year To Date Performance Report</h4>

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

        {{-- Summary Cards --}}
        @if(isset($summary['TOTAL']) && is_array($summary['TOTAL']))
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Monthly Performance (Mar 2025)</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $monthlyTotal = $summary['TOTAL'];
                            $monthlyTotalTarget = (float)($monthlyTotal['monthly_target'] ?? 0);
                            $monthlyTotalActual = (float)($monthlyTotal['monthly_actual'] ?? 0);
                            $monthlyOverallPercent = $monthlyTotalTarget > 0 ? ($monthlyTotalActual / $monthlyTotalTarget) * 100 : 0;
                        @endphp
                        <p><strong>Target:</strong> {{ number_format($monthlyTotalTarget, 0, ',', '.') }}</p>
                        <p><strong>Actual:</strong> {{ number_format($monthlyTotalActual, 0, ',', '.') }}</p>
                        <p><strong>Achievement:</strong> <span class="badge {{ $monthlyOverallPercent >= 100 ? 'bg-success' : ($monthlyOverallPercent >= 80 ? 'bg-warning' : 'bg-danger') }}">{{ number_format($monthlyOverallPercent, 1) }}%</span></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Year To Date Performance</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $ytdTotal = $summary['TOTAL'];
                            $ytdTotalTarget = (float)($ytdTotal['ytd_target'] ?? 0);
                            $ytdTotalActual = (float)($ytdTotal['ytd_actual'] ?? 0);
                            $ytdOverallPercent = $ytdTotalTarget > 0 ? ($ytdTotalActual / $ytdTotalTarget) * 100 : 0;
                        @endphp
                        <p><strong>Target:</strong> {{ number_format($ytdTotalTarget, 0, ',', '.') }}</p>
                        <p><strong>Actual:</strong> {{ number_format($ytdTotalActual, 0, ',', '.') }}</p>
                        <p><strong>Achievement:</strong> <span class="badge {{ $ytdOverallPercent >= 100 ? 'bg-success' : ($ytdOverallPercent >= 80 ? 'bg-warning' : 'bg-danger') }}">{{ number_format($ytdOverallPercent, 1) }}%</span></p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Debug Info --}}
        <div class="mt-4">
            <small class="text-muted">Total records processed: {{ count($rows ?? []) }}</small>
        </div>
        
        {{-- Debug: Show data structure (remove this in production) --}}
        {{-- 
        <div class="mt-4">
            <details>
                <summary>Debug: Data Structure</summary>
                <pre>{{ print_r($summary, true) }}</pre>
            </details>
        </div>
        --}}
    @endif
</div>

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