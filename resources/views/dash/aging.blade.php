@extends('layouts.app')
@section('content')
<div class="row">
  <div class="col-md-6">
    <div class="card p-3 mb-4">
      <h5>Overdue Buckets</h5>
      <ul class="list-unstyled">
        <li>1-30 days: Rp {{ number_format($aging->d1_30 ?? 0) }}</li>
        <li>31-60 days: Rp {{ number_format($aging->d31_60 ?? 0) }}</li>
        <li>61-90 days: Rp {{ number_format($aging->d61_90 ?? 0) }}</li>
        <li>90+ days: Rp {{ number_format($aging->d90p ?? 0) }}</li>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3 mb-4">
      <h5>Overdue List</h5>
      <div class="table-responsive">
        <table id="overdueTable" class="table table-striped table-bordered mb-0">
          <thead class="table-light"><tr><th>Customer</th><th>Due</th><th class="text-end">Balance</th></tr></thead>
          <tbody>
            @foreach($overdueList as $inv)
            <tr>
              <td>{{ $inv->customer->name ?? '-' }}</td>
              <td>{{ optional($inv->due_date)->format('Y-m-d') }}</td>
              <td class="text-end">{{ number_format($inv->balance,2) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>$(function(){ $('#overdueTable').DataTable({ "order":[[2,'desc']] }); });</script>
@endpush
