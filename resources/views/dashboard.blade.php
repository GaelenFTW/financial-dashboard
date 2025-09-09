@extends('layouts.app')

@section('content')
<!-- KPI Cards -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card text-white bg-success mb-3">
      <div class="card-body">
        <h6 class="card-title">Paid invoices (this month)</h6>
        <h3>$12,500</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-white bg-danger mb-3">
      <div class="card-body">
        <h6 class="card-title">Overdue invoices (this month)</h6>
        <h3>$3,200</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-white bg-info mb-3">
      <div class="card-body">
        <h6 class="card-title">Open invoices (this month)</h6>
        <h3>$5,700</h3>
      </div>
    </div>
  </div>
</div>

<!-- Invoices Table -->
<div class="card mb-4">
  <div class="card-header">Invoices</div>
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>Doc #</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Due</th>
          <th>Currency</th>
          <th>Amount</th>
          <th>Balance</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1056</td>
          <td>Elite Systems</td>
          <td>2025-06-12</td>
          <td>2025-06-27</td>
          <td>USD</td>
          <td class="text-end">500.00</td>
          <td class="text-end">0.00</td>
        </tr>
        <tr>
          <td>1079</td>
          <td>Quantum Services</td>
          <td>2025-06-15</td>
          <td>2025-06-30</td>
          <td>USD</td>
          <td class="text-end">8440.00</td>
          <td class="text-end">0.00</td>
        </tr>
        <tr>
          <td>1082</td>
          <td>Elite Systems</td>
          <td>2025-06-15</td>
          <td>2025-06-30</td>
          <td>USD</td>
          <td class="text-end">500.00</td>
          <td class="text-end">500.00</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Chart -->
<div class="card">
  <div class="card-header">Monthly Summary</div>
  <div class="card-body">
    <canvas id="invoicesChart" height="120"></canvas>
  </div>
</div>
@endsection

@push('scripts')
<script>
const labels = ["Mar 2025","Apr 2025","May 2025","Jun 2025"];
const dataOpen = [2000, 1500, 3000, 5700];
const dataOverdue = [1000, 800, 1200, 3200];
const dataPaid = [5000, 7500, 10000, 12500];

new Chart(document.getElementById('invoicesChart'), {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [
      { label: 'Open', data: dataOpen, backgroundColor: 'blue' },
      { label: 'Overdue', data: dataOverdue, backgroundColor: 'red' },
      { label: 'Paid', data: dataPaid, backgroundColor: 'green' }
    ]
  },
  options: { responsive: true, plugins:{ legend:{ position:'top' } } }
});
</script>
@endpush
