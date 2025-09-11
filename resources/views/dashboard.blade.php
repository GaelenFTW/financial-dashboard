@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">

    {{-- Top Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-gradient-primary shadow-sm rounded-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Num of Customers</h5>
                    <h2>{{ $numCustomers }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-gradient-info shadow-sm rounded-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2>{{ number_format($totalRevenue/1000, 0) }}K</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-gradient-success shadow-sm rounded-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Avg Revenue</h5>
                    <h2>{{ number_format($avgRevenue, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-gradient-danger shadow-sm rounded-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Products Sold</h5>
                    <h2>{{ $productsSold }}</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm rounded-3">
                <div class="card-header">Top 10 Customers by Revenue</div>
                <div class="card-body">
                    <canvas id="topCustomersChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm rounded-3">
                <div class="card-header">Top 10 Products by Revenue</div>
                <div class="card-body">
                    <canvas id="topProductsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Tables --}}
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm rounded-3">
                <div class="card-header">Customer Revenue</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $c)
                                <tr>
                                    <td>{{ $c->customer_name }}</td>
                                    <td>{{ number_format($c->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ number_format($totalRevenue) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm rounded-3">
                <div class="card-header">Product Sales</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $p)
                                <tr>
                                    <td>{{ $p->ProductName }}</td>
                                    <td>{{ number_format($p->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ number_format($totalRevenue) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Top Customers Chart
    new Chart(document.getElementById('topCustomersChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($customerNames) !!},
            datasets: [{
                label: 'Revenue',
                data: {!! json_encode($customerRevenue) !!},
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                y: { beginAtZero: true }
            }
        }
    });

    // Top Products Chart
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($productNames) !!},
            datasets: [{
                label: 'Revenue',
                data: {!! json_encode($productRevenue) !!},
                backgroundColor: '#6f42c1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endsection
