@extends('layouts.app')

@section('title', 'Reports Dashboard')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">التقارير المالية</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item active">التقارير</li>
    </ol>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['total_cars'] }}</h4>
                            <div>إجمالي السيارات</div>
                        </div>
                        <div class="fs-1"><i class="fas fa-car"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('cars.index') }}">عرض التفاصيل</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['unpaid_invoices'] }}</h4>
                            <div>الفواتير غير المدفوعة</div>
                        </div>
                        <div class="fs-1"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('invoices.index', ['status' => 'issued']) }}">عرض التفاصيل</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['cars_sold'] }}</h4>
                            <div>السيارات المباعة</div>
                        </div>
                        <div class="fs-1"><i class="fas fa-tags"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('cars.index', ['status' => 'sold']) }}">عرض التفاصيل</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['cars_shipped'] }}</h4>
                            <div>السيارات قيد الشحن</div>
                        </div>
                        <div class="fs-1"><i class="fas fa-shipping-fast"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('cars.index', ['status' => 'shipped']) }}">عرض التفاصيل</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    تقارير الحسابات
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('reports.account_statement') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-alt me-2"></i>
                                    كشف حساب تفصيلي
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        <a href="{{ route('reports.outstanding_balances') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-clock me-2"></i>
                                    الأرصدة المستحقة
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        <a href="{{ route('reports.aging_receivables') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    تقرير أعمار الديون
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    تقارير المبيعات والأرباح
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('reports.profit_loss') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-chart-line me-2"></i>
                                    الأرباح والخسائر
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        <a href="{{ route('reports.car_profitability') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-car me-2"></i>
                                    ربحية السيارات
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        <a href="{{ route('reports.shipping_companies') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-truck me-2"></i>
                                    أداء شركات الشحن
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        <a href="{{ route('reports.commission') }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-percentage me-2"></i>
                                    تقرير العمولات
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            وضع المخزون
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>الحالة</th>
                        <th>عدد السيارات</th>
                        <th>النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>تم الشراء</td>
                        <td>{{ $stats['cars_purchased'] }}</td>
                        <td>{{ $stats['total_cars'] > 0 ? number_format(($stats['cars_purchased'] / $stats['total_cars']) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td>قيد الشحن</td>
                        <td>{{ $stats['cars_shipped'] }}</td>
                        <td>{{ $stats['total_cars'] > 0 ? number_format(($stats['cars_shipped'] / $stats['total_cars']) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td>تم التسليم</td>
                        <td>{{ $stats['cars_delivered'] }}</td>
                        <td>{{ $stats['total_cars'] > 0 ? number_format(($stats['cars_delivered'] / $stats['total_cars']) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td>تم البيع</td>
                        <td>{{ $stats['cars_sold'] }}</td>
                        <td>{{ $stats['total_cars'] > 0 ? number_format(($stats['cars_sold'] / $stats['total_cars']) * 100, 1) : 0 }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
