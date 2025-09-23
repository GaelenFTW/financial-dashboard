@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Purchase Letters - Management Report</h2>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Cluster</th>
                    <th>Type Pembelian</th>
                    <th>Mar 2025 Piutang</th>
                    <th>Mar 2025 Payment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['CustomerName'] }}</td>
                        <td>{{ $row['Cluster'] }}</td>
                        <td>{{ $row['TypePembelian'] }}</td>
                        <td>{{ number_format((float)$row['Mar_2025_Piutang'], 0) }}</td>
                        <td>{{ number_format((float)$row['Mar_2025_Payment'], 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
