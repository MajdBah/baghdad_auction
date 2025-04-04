@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">لوحة التحكم</h1>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white">عدد الحسابات</h6>
                            <h2 class="mb-0">{{ \App\Models\Account::count() }}</h2>
                        </div>
                        <i class="bi bi-person-badge fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('accounts.index') }}" class="text-white text-decoration-none">عرض التفاصيل</a>
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white">عدد السيارات</h6>
                            <h2 class="mb-0">{{ \App\Models\Car::count() }}</h2>
                        </div>
                        <i class="bi bi-truck fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('cars.index') }}" class="text-white text-decoration-none">عرض التفاصيل</a>
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark">عدد الفواتير</h6>
                            <h2 class="mb-0">{{ \App\Models\Invoice::count() }}</h2>
                        </div>
                        <i class="bi bi-receipt fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('invoices.index') }}" class="text-dark text-decoration-none">عرض التفاصيل</a>
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-info text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark">عدد العمليات</h6>
                            <h2 class="mb-0">{{ \App\Models\Transaction::count() }}</h2>
                        </div>
                        <i class="bi bi-arrow-left-right fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('transactions.index') }}" class="text-dark text-decoration-none">عرض التفاصيل</a>
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">أحدث العمليات المالية</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم العملية</th>
                                    <th>التاريخ</th>
                                    <th>الحساب</th>
                                    <th>المبلغ</th>
                                    <th>النوع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(\App\Models\Transaction::with(['fromAccount', 'toAccount'])->latest()->take(5)->get() as $transaction)
                                <tr>
                                    <td>{{ $transaction->id }}</td>
                                    <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                                    <td>
                                        @if($transaction->fromAccount)
                                            {{ $transaction->fromAccount->name }}
                                        @elseif($transaction->toAccount)
                                            {{ $transaction->toAccount->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($transaction->amount, 2) }}</td>
                                    <td>
                                        @if($transaction->type == 'purchase')
                                            <span class="badge bg-secondary">شراء</span>
                                        @elseif($transaction->type == 'shipping')
                                            <span class="badge bg-info">شحن</span>
                                        @elseif($transaction->type == 'transfer')
                                            <span class="badge bg-primary">تحويل</span>
                                        @elseif($transaction->type == 'payment')
                                            <span class="badge bg-success">دفعة</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $transaction->type }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">لا توجد عمليات مالية حتى الآن</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-primary">عرض جميع العمليات</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">أحدث السيارات المضافة</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم السيارة</th>
                                    <th>الموديل</th>
                                    <th>اللون</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(\App\Models\Car::latest()->take(5)->get() as $car)
                                <tr>
                                    <td>{{ $car->vin }}</td>
                                    <td>{{ $car->model }}</td>
                                    <td>{{ $car->color }}</td>
                                    <td>
                                        @if($car->status == 'available')
                                            <span class="badge bg-success">متاح</span>
                                        @elseif($car->status == 'in_transit')
                                            <span class="badge bg-warning">قيد الشحن</span>
                                        @elseif($car->status == 'sold')
                                            <span class="badge bg-info">تم البيع</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $car->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">لا توجد سيارات حتى الآن</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('cars.index') }}" class="btn btn-sm btn-primary">عرض جميع السيارات</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">أحدث الفواتير</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>الحساب</th>
                                    <th>إجمالي المبلغ</th>
                                    <th>الحالة</th>
                                    <th>خيارات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(\App\Models\Invoice::with('account')->latest()->take(5)->get() as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                    <td>
                                        @if($invoice->account)
                                            {{ $invoice->account->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>
                                        @if($invoice->status == 'paid')
                                            <span class="badge bg-success">مدفوعة</span>
                                        @elseif($invoice->status == 'partially_paid')
                                            <span class="badge bg-warning">مدفوعة جزئياً</span>
                                        @elseif($invoice->status == 'overdue')
                                            <span class="badge bg-danger">متأخرة</span>
                                        @elseif($invoice->status == 'issued')
                                            <span class="badge bg-info">صادرة</span>
                                        @elseif($invoice->status == 'draft')
                                            <span class="badge bg-secondary">مسودة</span>
                                        @elseif($invoice->status == 'cancelled')
                                            <span class="badge bg-dark">ملغية</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $invoice->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">لا توجد فواتير حتى الآن</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-primary">عرض جميع الفواتير</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Any dashboard-specific JavaScript can go here
    $(document).ready(function() {
        console.log('Dashboard loaded');
    });
</script>
@endpush
