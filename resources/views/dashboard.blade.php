@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Financial Dashboard</h1>

    {{-- Summary Cards --}}
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow">
                <div class="card-body">
                    <h6 class="card-title">Num of Customers</h6>
                    <h2>{{ $numCustomers }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow">
                <div class="card-body">
                    <h6 class="card-title">Total Revenue</h6>
                    <h2>{{ number_format($totalRevenue / 1000, 0) }}K</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow">
                <div class="card-body">
                    <h6 class="card-title">Avg Revenue</h6>
                    <h2>{{ number_format($avgRevenue, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger shadow">
                <div class="card-body">
                    <h6 class="card-title">Products Sold</h6>
                    <h2>{{ $productsSold }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
    <h1 class="mb-4">Financial Dashboard</h1>

    {{-- Filters --}}
    <form method="GET" action="{{ route('dashboard') }}" class="row g-3 mb-4">
        <div class="col-md-3">
            <input type="text" name="cluster" class="form-control" placeholder="Cluster" value="{{ $filters['cluster'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="salesman" class="form-control" placeholder="Salesman" value="{{ $filters['salesman'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="customername" class="form-control" placeholder="Customer Name" value="{{ $filters['customername'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="type_unit" class="form-control" placeholder="Type Unit" value="{{ $filters['type_unit'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <input type="date" name="startdate" class="form-control" value="{{ $filters['startdate'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <input type="date" name="enddate" class="form-control" value="{{ $filters['enddate'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
        <div class="col-md-3">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary w-100">Reset</a>
        </div>
    </form>


    {{-- Top 10 Customers --}}
    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top 10 Customers by Revenue</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Customer</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($customers as $name => $revenue)
                            <tr>
                                <td>{{ $name }}</td>
                                <td>{{ number_format($revenue, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center">No data</td>
                            </tr>
                        @endforelse
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th>{{ number_format($customers->sum(), 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top 10 Products --}}
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top 10 Products by Revenue</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($products as $product => $revenue)
                            <tr>
                                <td>{{ $product }}</td>
                                <td>{{ number_format($revenue, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center">No data</td>
                            </tr>
                        @endforelse
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th>{{ number_format($products->sum(), 0) }}</th>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
