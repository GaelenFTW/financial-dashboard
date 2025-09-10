<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: #f5f6fa;
        }
        h1 {
            margin-bottom: 20px;
        }
        .cards {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }
        .card-summary {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }
        .card-summary h3 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #555;
        }
        .card-summary p {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }
        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
    </style>
</head>
<!-- jQuery + DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#invoiceTable').DataTable({
            "order": [[3, "asc"]], // default sort by Due Date (kolom ke-4, index mulai dari 0)
            "columnDefs": [
                { "orderable": true, "targets": [1,2,3,4,5] }, // Due Date & Amount sortable
                // { "orderable": false, "targets": [] } // kolom lain tetap
            ]
        });
    });
</script>

<body>
    <h1>Financial Dashboard</h1>

<div class="cards">
    <div class="card-summary">
        <h3>Total Paid</h3>
        <p>{{ number_format($totalPaid, 0) }}</p>
    </div>
    <div class="card-summary">
        <h3>Total Overdue</h3>
        <p>{{ number_format($totalOverdue, 0) }}</p>
    </div>
    <div class="card-summary">
        <h3>Total Open</h3>
        <p>{{ number_format($totalOpen, 0) }}</p>
    </div>
</div>

<table id="invoiceTable" class="table table-hover table-bordered mb-0">
    <thead class="table-light">
        <tr>
            <th>Doc Number</th>
            <th>Customer</th>
            <th>Invoice Date</th>
            <th>Due Date</th>
            <th class="text-end">Amount</th>
            <th class="text-end">Balance</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $inv)
            <tr>
                <td>{{ $inv->doc_no }}</td>
                <td>{{ $inv->customer }}</td>
                <td>{{ \Carbon\Carbon::parse($inv->date)->format('M d, Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($inv->due_date)->format('M d, Y') }}</td>
                <td class="text-end">${{ number_format($inv->amount, 2) }}</td>
                <td class="text-end">${{ number_format($inv->balance, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted">No invoices found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

    {{-- Monthly Trend Chart --}}
    <div class="chart-container">
        <canvas id="invoiceChart"></canvas>
    </div>

    <script>
        const labels = @json(array_column($monthly, 'month'));
        const paidData = @json(array_column($monthly, 'paid'));
        const openData = @json(array_column($monthly, 'open_amount'));

        const ctx = document.getElementById('invoiceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Paid',
                        data: paidData,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderRadius: 6
                    },
                    {
                        label: 'Open',
                        data: openData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: {
                        display: true,
                        text: 'Monthly Invoice Summary'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'USD ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>
