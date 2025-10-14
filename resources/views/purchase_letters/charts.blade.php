@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4 text-center">Purchase Letters Chart</h2>

    <div style="width: 100%; height: 500px;">
        <canvas id="purchaseChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('purchaseChart').getContext('2d');

    const purchaseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [
                {
                    label: 'Open',
                    data: {!! json_encode($open) !!},
                    backgroundColor: '#00BFFF'
                },
                {
                    label: 'Overdue',
                    data: {!! json_encode($overdue) !!},
                    backgroundColor: '#FFA500'
                },
                {
                    label: 'Paid',
                    data: {!! json_encode($paid) !!},
                    backgroundColor: '#800080'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('en-US').format(value);
                        },
                        font: {
                            size: 12
                        }
                    },
                    title: {
                        display: true,
                        text: 'Amount (IDR)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
</script>



@endsection
