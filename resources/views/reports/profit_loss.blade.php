@extends('layouts.app')

@section('title', 'Profit and Loss Report')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">تقرير الأرباح والخسائر</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
        <li class="breadcrumb-item active">الأرباح والخسائر</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            معايير البحث
        </div>
        <div class="card-body">
            <form action="{{ route('reports.profit_loss') }}" method="GET" id="report-form">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="start_date" name="start_date" type="date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" required />
                            <label for="start_date">من تاريخ</label>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="end_date" name="end_date" type="date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}" required />
                            <label for="end_date">إلى تاريخ</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> عرض التقرير
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row" id="report-content">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-chart-line me-1"></i>
                    ملخص الأرباح والخسائر
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr class="table-success">
                                <th colspan="2">الإيرادات</th>
                            </tr>
                            <tr>
                                <td>مبيعات السيارات</td>
                                <td class="text-end">{{ number_format($revenue['car_sales'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>رسوم الشحن</td>
                                <td class="text-end">{{ number_format($revenue['shipping_fees'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>العمولات</td>
                                <td class="text-end">{{ number_format($revenue['commissions'], 2) }}</td>
                            </tr>
                            <tr class="table-success">
                                <th>إجمالي الإيرادات</th>
                                <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
                            </tr>

                            <tr><td colspan="2">&nbsp;</td></tr>

                            <tr class="table-danger">
                                <th colspan="2">المصروفات</th>
                            </tr>
                            <tr>
                                <td>شراء السيارات</td>
                                <td class="text-end">{{ number_format($expenses['car_purchases'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>تكاليف الشحن</td>
                                <td class="text-end">{{ number_format($expenses['shipping_costs'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>المصروفات التشغيلية</td>
                                <td class="text-end">{{ number_format($expenses['operational_costs'], 2) }}</td>
                            </tr>
                            <tr class="table-danger">
                                <th>إجمالي المصروفات</th>
                                <th class="text-end">{{ number_format($totalExpenses, 2) }}</th>
                            </tr>

                            <tr><td colspan="2">&nbsp;</td></tr>

                            <tr class="table-primary">
                                <th>صافي الربح</th>
                                <th class="text-end {{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($grossProfit, 2) }}</th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-chart-pie me-1"></i>
                    تحليل الإيرادات والمصروفات
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-center mb-3">توزيع الإيرادات</h5>
                            <canvas id="revenueChart" width="100%" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-center mb-3">توزيع المصروفات</h5>
                            <canvas id="expensesChart" width="100%" height="200"></canvas>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-center mb-3">اتجاه الربحية</h5>
                        <div id="profitByCarCount">عدد السيارات المربحة: {{ $carProfits->where('profit', '>', 0)->count() }} من أصل {{ $carProfits->count() }}</div>
                        <div class="progress mb-3">
                            @php
                                $profitablePercentage = $carProfits->count() > 0
                                    ? ($carProfits->where('profit', '>', 0)->count() / $carProfits->count()) * 100
                                    : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $profitablePercentage }}%"
                                aria-valuenow="{{ $profitablePercentage }}" aria-valuemin="0" aria-valuemax="100">
                                {{ number_format($profitablePercentage) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-car me-1"></i>
            ربحية السيارات
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>السيارة</th>
                            <th>سعر الشراء</th>
                            <th>تكلفة الشحن</th>
                            <th>إجمالي التكلفة</th>
                            <th>سعر البيع</th>
                            <th>الربح</th>
                            <th>نسبة الربح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carProfits as $car)
                        <tr class="{{ $car['profit'] > 0 ? 'table-success' : 'table-danger' }}">
                            <td>{{ $car['name'] }}</td>
                            <td>{{ number_format($car['purchase_price'], 2) }}</td>
                            <td>{{ number_format($car['shipping_cost'], 2) }}</td>
                            <td>{{ number_format($car['purchase_price'] + $car['shipping_cost'], 2) }}</td>
                            <td>{{ number_format($car['selling_price'], 2) }}</td>
                            <td>{{ number_format($car['profit'], 2) }}</td>
                            <td>
                                @php
                                    $totalCost = $car['purchase_price'] + $car['shipping_cost'];
                                    $profitPercentage = $totalCost > 0 ? ($car['profit'] / $totalCost) * 100 : 0;
                                @endphp
                                {{ number_format($profitPercentage, 1) }}%
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">لا توجد بيانات متاحة</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($carProfits->count() > 0)
                    <tfoot class="table-primary">
                        <tr>
                            <th>الإجمالي</th>
                            <th>{{ number_format($carProfits->sum('purchase_price'), 2) }}</th>
                            <th>{{ number_format($carProfits->sum('shipping_cost'), 2) }}</th>
                            <th>{{ number_format($carProfits->sum('purchase_price') + $carProfits->sum('shipping_cost'), 2) }}</th>
                            <th>{{ number_format($carProfits->sum('selling_price'), 2) }}</th>
                            <th>{{ number_format($carProfits->sum('profit'), 2) }}</th>
                            <th>
                                @php
                                    $totalCost = $carProfits->sum('purchase_price') + $carProfits->sum('shipping_cost');
                                    $avgProfitPercentage = $totalCost > 0 ? ($carProfits->sum('profit') / $totalCost) * 100 : 0;
                                @endphp
                                {{ number_format($avgProfitPercentage, 1) }}%
                            </th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-success" onclick="printReport()">
                    <i class="fas fa-print me-1"></i> طباعة التقرير
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'pie',
            data: {
                labels: ['مبيعات السيارات', 'رسوم الشحن', 'العمولات'],
                datasets: [{
                    data: [
                        {{ $revenue['car_sales'] }},
                        {{ $revenue['shipping_fees'] }},
                        {{ $revenue['commissions'] }}
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Expenses Chart
        const expensesCtx = document.getElementById('expensesChart').getContext('2d');
        const expensesChart = new Chart(expensesCtx, {
            type: 'pie',
            data: {
                labels: ['شراء السيارات', 'تكاليف الشحن', 'المصروفات التشغيلية'],
                datasets: [{
                    data: [
                        {{ $expenses['car_purchases'] }},
                        {{ $expenses['shipping_costs'] }},
                        {{ $expenses['operational_costs'] }}
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 205, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 205, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });

    function printReport() {
        const printContents = document.getElementById('report-content').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container mt-4">
                <h1 class="text-center mb-4">تقرير الأرباح والخسائر</h1>
                <p class="text-center">الفترة من: {{ $startDate->format('Y-m-d') }} إلى: {{ $endDate->format('Y-m-d') }}</p>
                ${printContents}
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContents;

        // Reload charts after printing
        location.reload();
    }
</script>
@endsection
