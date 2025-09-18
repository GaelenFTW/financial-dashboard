@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Purchase Letters</h2>
<form method="GET" action="{{ url()->current() }}" class="mb-3">
    <input type="text" name="search" value="{{ $search ?? '' }}" 
           class="form-control" placeholder="Search customer, cluster, or unit...">
</form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Cluster</th>
                <th>Unit</th>
                <th>Purchase Date</th>
                <th>Lunas Date</th>
                <th>Harga Netto</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($letters as $letter)
            <tr>
                <td>{{ $letter['CustomerName'] ?? '' }}</td>
                <td>{{ $letter['Cluster'] ?? '' }}</td>
                <td>{{ $letter['Unit'] ?? '' }}</td>
                <td>{{ $letter['PurchaseDate'] ?? '' }}</td>
                <td>{{ $letter['LunasDate'] ?? '' }}</td>
                <td>Rp {{ number_format((float) ($letter['harga_netto'] ?? 0), 0) }}</td>
                <td>Rp {{ number_format((float) ($letter['HrgJualTotal'] ?? 0), 0) }}</td>
            </tr>
            @endforeach

            </tr>
        </tbody>
    </table>


{{ $letters->links('pagination::bootstrap-5') }}
</div>
<div class="mb-4 d-flex">
    <a href="{{ url('/') }}" class="btn btn-outline-primary">
        Dashboard
    </a>
    <a href="{{ url('/purchase-letters/charts') }}" class="btn btn-outline-primary ms-auto">
        Charts
    </a>
</div>


@endsection
