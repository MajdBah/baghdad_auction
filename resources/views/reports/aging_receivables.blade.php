@extends('layouts.app')

@section('title', 'Aging Receivables Report')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">تقرير أعمار الديون</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
        <li class="breadcrumb-item active">أعمار الديون</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-hourglass-half me-1"></i>
            أعمار الديون حسب العميل
        </div>
        <div class="card-body">
            @if(count($agingData) > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="bg-light">
                            <th>العميل</th>
                            <th>حالي</th>
                            <th>1-30 يوم</th>
                            <th>31-60 يوم</th>
                            <th>61-90 يوم</th>
                            <th>أكثر من 90 يوم</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agingData as $data)
                        <tr>
                            <td>
                                <a href="{{ route('accounts.show', $data['account']) }}">
                                    {{ $data['account']->name }}
                                </a>
                            </td>
                            <td class="text-end">{{ number_format($data['current'], 2) }}</td>
                            <td class="text-end">{{ number_format($data['days30'], 2) }}</td>
                            <td class="text-end">{{ number_format($data['days60'], 2) }}</td>
                            <td class="text-end">{{ number_format($data['days90'], 2) }}</td>
                            <td class="text-end">{{ number_format($data['days90Plus'], 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($data['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <th>الإجمالي</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($agingData, 'current')), 2) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($agingData, 'days30')), 2) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($agingData, 'days60')), 2) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($agingData, 'days90')), 2) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($agingData, 'days90Plus')), 2) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($agingData, 'total')), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 mx-auto">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-pie me-1"></i>
                            توزيع أعمار الديون
                        </div>
                        <div class="card-body">
                            <canvas id="agingChart" width="100%" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i> لا توجد ديون مستحقة حالياً
            </div>
            @endif

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-success" onclick="printReport()">
                    <i class="fas fa-print me-1"></i> طباعة التقرير
                </button>
            </div>
        </div>
    </div>

    @if(count($agingData) > 0)
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exclamation-triangle me-1"></i>
            الفواتير المتأخرة عن السداد (أكثر من 30 يوماً)
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped border">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>رقم الفاتورة</th>
                            <th>تاريخ الإصدار</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>المبلغ المتبقي</th>
                            <th>التأخير (أيام)</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $overdueInvoices = [];
                            $today = \Carbon\Carbon::now();

                            foreach($agingData as $data) {
                                foreach($data['account']->invoices as $invoice) {
                                    if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled' && $today->diffInDays($invoice->due_date, false) > 30) {
                                        $overdueInvoices[] = $invoice;
                                    }
                                }
                            }
                        @endphp

                        @forelse($overdueInvoices as $invoice)
                        <tr>
                            <td>{{ $invoice->account->name }}</td>
                            <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                            <td>{{ $invoice->due_date->format('Y-m-d') }}</td>
                            <td>{{ number_format($invoice->balance, 2) }}</td>
                            <td class="text-danger fw-bold">{{ $today->diffInDays($invoice->due_date, false) }}</td>
                            <td>
                                <a href="{{ route('invoices.payment_form', $invoice) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-money-bill-wave"></i> تسجيل دفعة
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">لا توجد فواتير متأخرة عن السداد أكثر من 30 يوم</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
@if(count($agingData) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Aging Chart
        const currentTotal = {{ array_sum(array_column($agingData, 'current')) }};
        const days30Total = {{ array_sum(array_column($agingData, 'days30')) }};
        const days60Total = {{ array_sum(array_column($agingData, 'days60')) }};
        const days90Total = {{ array_sum(array_column($agingData, 'days90')) }};
        const days90PlusTotal = {{ array_sum(array_column($agingData, 'days90Plus')) }};

        const agingCtx = document.getElementById('agingChart').getContext('2d');
        const agingChart = new Chart(agingCtx, {
            type: 'pie',
            data: {
                labels: ['حالي', '1-30 يوم', '31-60 يوم', '61-90 يوم', 'أكثر من 90 يوم'],
                datasets: [{
                    data: [currentTotal, days30Total, days60Total, days90Total, days90PlusTotal],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 205, 86, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });

    function printReport() {
        window.print();
    }
</script>
@endif
@endsection
