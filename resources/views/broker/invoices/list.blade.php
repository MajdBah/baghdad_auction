@extends('layouts.app')

@section('content')
<div class="container" dir="rtl" lang="ar">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list ms-2"></i>قائمة فواتير الوسيط</h5>
                        <a href="{{ route('broker.invoices.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle ms-1"></i>إنشاء فاتورة جديدة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <form action="{{ route('broker.invoices.list') }}" method="GET" class="row g-3">
                                        <div class="col-md-4">
                                            <label for="broker_account_id" class="form-label">حساب الوسيط:</label>
                                            <select name="broker_account_id" id="broker_account_id" class="form-select" required onchange="this.form.submit()">
                                                <option value="">-- اختر حساب الوسيط --</option>
                                                @foreach($brokerAccounts as $account)
                                                    <option value="{{ $account->id }}" {{ $selectedAccount && $selectedAccount->id == $account->id ? 'selected' : '' }}>
                                                        {{ $account->name }} ({{ $account->account_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="direction" class="form-label">نوع الفواتير:</label>
                                            <select name="direction" id="direction" class="form-select" onchange="this.form.submit()">
                                                <option value="">جميع الفواتير</option>
                                                <option value="positive" {{ $direction == 'positive' ? 'selected' : '' }}>الفواتير الموجبة (إيرادات)</option>
                                                <option value="negative" {{ $direction == 'negative' ? 'selected' : '' }}>الفواتير السالبة (مصروفات)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter ms-1"></i>تصفية
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($selectedAccount)
                        <div class="alert alert-info mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-user ms-2"></i>حساب: {{ $selectedAccount->name }}</h5>
                                <span class="badge bg-secondary">{{ $selectedAccount->account_number }}</span>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>عدد الفواتير:</strong> {{ $invoices->count() }}
                                </div>
                                <div class="col-md-6">
                                    <strong>إجمالي الرصيد:</strong>
                                    <span class="{{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($balance, 2) }} د.ع
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($invoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>رقم الفاتورة</th>
                                            <th>من</th>
                                            <th>إلى</th>
                                            <th>تاريخ الإصدار</th>
                                            <th>المبلغ الإجمالي</th>
                                            <th>المبلغ المدفوع</th>
                                            <th>الرصيد المتبقي</th>
                                            <th>النوع</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->fromAccount->name }}</td>
                                                <td>{{ $invoice->toAccount->name }}</td>
                                                <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                                <td class="text-start">{{ number_format($invoice->total_amount, 2) }}</td>
                                                <td class="text-start">{{ number_format($invoice->paid_amount, 2) }}</td>
                                                <td class="text-start">{{ number_format($invoice->balance, 2) }}</td>
                                                <td>
                                                    <span class="badge {{ $invoice->isPositiveForBroker() ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $invoice->isPositiveForBroker() ? 'موجبة (إيراد)' : 'سالبة (مصروف)' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $invoice->isPaid() ? 'bg-success' : ($invoice->isPartiallyPaid() ? 'bg-warning' : 'bg-secondary') }}">
                                                        {{ $invoice->isPaid() ? 'مدفوعة' : ($invoice->isPartiallyPaid() ? 'مدفوعة جزئياً' : 'غير مدفوعة') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('broker.invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-primary">
                                        <tr>
                                            <th colspan="4" class="text-start">الإجمالي</th>
                                            <th class="text-start">{{ number_format($invoices->sum('total_amount'), 2) }}</th>
                                            <th class="text-start">{{ number_format($invoices->sum('paid_amount'), 2) }}</th>
                                            <th class="text-start">{{ number_format($invoices->sum('balance'), 2) }}</th>
                                            <th colspan="3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle ms-2"></i>لا توجد فواتير متطابقة مع معايير البحث.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle ms-2"></i>يرجى اختيار حساب الوسيط لعرض الفواتير المرتبطة به.
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('broker.invoices.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right ms-1"></i>العودة
                        </a>

                        @if($selectedAccount)
                            <a href="{{ route('broker.invoices.create') }}" class="btn btn-success">
                                <i class="fas fa-plus-circle ms-1"></i>إنشاء فاتورة جديدة
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
