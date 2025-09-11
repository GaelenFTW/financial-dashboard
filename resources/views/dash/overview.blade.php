@extends('layouts.app')

@section('content')
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Total Customers</div>
      <div class="h4">{{ number_format($totalCustomers) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Total Invoices</div>
      <div class="h4">{{ number_format($totalInvoices) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Total Paid</div>
      <div class="h4">Rp {{ number_format($totalPaid,0) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Outstanding</div>
      <div class="h4">Rp {{ number_format($totalOutstanding,0) }}</div>
    </div>
  </div>
</div>

<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <canvas id="monthlyChart" height="120"></canvas>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header">Latest Invoices</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="latestTable" class="table table-striped table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Doc No</th><th>Customer</th><th>Date</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Balance</th>
          </tr>
        </thead>
        <tbody>
          @foreach($latestInvoices as $inv)
          <tr>
            <td>{{ $inv->doc_no }}</td>
            <td>{{ $inv->customer->name ?? '-' }}</td>
            <td>{{ optional($inv->invoice_date)->format('Y-m-d') }}</td>
            <td>{{ optional($inv->due_date)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format($inv->amount,2) }}</td>
            <td class="text-end">{{ number_format($inv->balance,2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const months = @json($monthly->pluck('month'));
const paid = @json($monthly->pluck('paid'));
const openAmt = @json($monthly->pluck('open_amount'));

new Chart(document.getElementById('monthlyChart'), {
  type:'bar',
  data:{ labels: months, datasets:[
    {label:'Paid', data: paid, backgroundColor:'rgba(54,162,235,0.6)'},
    {label:'Outstanding', data: openAmt, backgroundColor:'rgba(255,99,132,0.6)'}
  ]},
  options:{responsive:true, plugins:{legend:{position:'top'}}}
});

$(function(){ $('#latestTable').DataTable({ "order": [[2,'desc']], "pageLength": 10 }); });
</script>
@endpush
