@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">Revenue Dashboard</h2>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body text-center">
                    <h5>Num of Customers</h5>
                    <p class="h3">24</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body text-center">
                    <h5>Total Revenue</h5>
                    <p class="h3">206K</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body text-center">
                    <h5>Avg Revenue / Customer</h5>
                    <p class="h3">8.98K</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body text-center">
                    <h5>Products Sold</h5>
                    <p class="h3">145</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-danger shadow-sm">
                <div class="card-body text-center">
                    <h5>Avg Price / Product</h5>
                    <p class="h3">2.15K</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tables & Charts --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Customers by Revenue</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $c)
                            <tr>
                                <td>{{ $c['name'] }}</td>
                                <td>${{ number_format($c['revenue'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top 10 Customers (Chart)</h5>
                </div>
                <div class="card-body">
                    <canvas id="customersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Products --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Products</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Avg Price</th>
                                <th>Qty</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $p)
                            <tr>
                                <td>{{ $p['name'] }}</td>
                                <td>${{ number_format($p['avg_price'], 2) }}</td>
                                <td>{{ $p['qty'] }}</td>
                                <td>${{ number_format($p['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top Products (Chart)</h5>
                </div>
                <div class="card-body">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const customerLabels = @json(array_column($customers, 'name'));
    const customerRevenue = @json(array_column($customers, 'revenue'));

    const productLabels = @json(array_column($products, 'name'));
    const productAmounts = @json(array_column($products, 'amount'));

    new Chart(document.getElementById('customersChart'), {
        type: 'bar',
        data: {
            labels: customerLabels,
            datasets: [{
                label: 'Revenue',
                data: customerRevenue,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
            }]
        }
    });

    new Chart(document.getElementById('productsChart'), {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Amount',
                data: productAmounts,
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
            }]
        }
    });
</script>
@endsection
