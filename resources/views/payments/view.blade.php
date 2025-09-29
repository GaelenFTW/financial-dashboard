@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payments Data</h2>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
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

                {{-- new columns --}}
                <th>Piutang After</th>
                <th>Payment After</th>
                <th>YTD sd</th>
                <th>YTD Bayar (After)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->CustomerName }}</td>
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

                {{-- new fields --}}
                <td>{{ $p->Piutang_After_Jun_2025 ?? '-' }}</td>
                <td>{{ $p->Payment_After_Jun_2025 ?? '-' }}</td>
                <td>{{ $p->YTD_sd_Jun_2025 ?? '-' }}</td>
                <td>{{ $p->YTD_bayar_Jun_2025 ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $payments->links('pagination::bootstrap-5') }}
</div>
@endsection
