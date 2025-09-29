@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payments Data</h2>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Amount Before</th>
                <th>Piutang Before</th>
                <th>Payment Before</th>
                <th>01 Tahun Piutang</th>
                <th>01 Tahun Payment</th>
                <th>02 Tahun Piutang</th>
                <th>02 Tahun Payment</th>
                <th>05 Tahun Payment</th>
                <th>YTD Bayar</th>
                <th>Lebih Bayar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->Amount_Before_01_tahun }}</td>
                <td>{{ $p->Piutang_Before_01_tahun }}</td>
                <td>{{ $p->Payment_Before_01_tahun }}</td>
                <td>{{ $p->tahun01_Piutang }}</td>
                <td>{{ $p->tahun01_Payment }}</td>
                <td>{{ $p->tahun02_Piutang }}</td>
                <td>{{ $p->tahun02_Payment }}</td>
                <td>{{ $p->tahun05_Payment }}</td>
                <td>{{ $p->YTD_bayar_05_tahun }}</td>
                <td>{{ $p->lebih_bayar }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $payments->links() }}
</div>
@endsection
