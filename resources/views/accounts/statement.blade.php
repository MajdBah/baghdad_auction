@extends('layouts.app')

@section('title', 'كشف حساب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">كشف حساب: {{ $account->name }} ({{ $account->account_number }})</h2>
        <div>
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="bi bi-printer"></i> طباعة الكشف
            </button>
            <a href="{{ route('accounts.show', $account) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> العودة للحساب
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">تحديد الفترة</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('accounts.statement', $account) }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> عرض النتائج
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                كشف حساب للفترة من {{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}
                إلى {{ \Carbon\Carbon::parse($endDate)->format('Y-m-d') }}
            </h5>
            <span class="badge bg-{{ $account->balance < 0 ? 'danger' : 'success' }} fs-6">
                الرصيد الحالي: {{ number_format($account->balance, 2) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>الوصف</th>
                            <th>المصدر/الوجهة</th>
                            <th>رقم المرجع</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $balance = 0;
                            $totalDebit = 0;
                            $totalCredit = 0;
                        @endphp

                        @forelse($allTransactions as $transaction)
                            @php
                                // Determine if this is a debit or credit for this account
                                $isDebit = $transaction->from_account_id == $account->id;
                                $amount = $transaction->amount;

                                // If commission is applied and this account pays it
                                if ($transaction->with_commission && $isDebit) {
                                    $amount += $transaction->commission_amount;
                                }

                                // Update running balance
                                if ($isDebit) {
                                    $balance -= $amount;
                                    $totalDebit += $amount;
                                } else {
                                    $balance += $amount;
                                    $totalCredit += $amount;
                                }

                                // Get other party name
                                $otherParty = $isDebit ?
                                    ($transaction->toAccount ? $transaction->toAccount->name : '—') :
                                    ($transaction->fromAccount ? $transaction->fromAccount->name : '—');

                                // Transaction description
                                $description = $transaction->description;
                                if ($transaction->car_id && $transaction->car) {
                                    $description .= ' (سيارة: ' . $transaction->car->make . ' ' . $transaction->car->model . ')';
                                }
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $transaction->transaction_date }}</td>
                                <td>
                                    @if($transaction->type == 'purchase')
                                        <span class="badge bg-secondary">شراء</span>
                                    @elseif($transaction->type == 'shipping')
                                        <span class="badge bg-info">شحن</span>
                                    @elseif($transaction->type == 'transfer')
                                        <span class="badge bg-primary">تحويل</span>
                                    @elseif($transaction->type == 'payment')
                                        <span class="badge bg-warning">دفعة</span>
                                    @elseif($transaction->type == 'commission')
                                        <span class="badge bg-dark">عمولة</span>
                                    @elseif($transaction->type == 'refund')
                                        <span class="badge bg-success">استرداد</span>
                                    @endif
                                </td>
                                <td>{{ $description }}</td>
                                <td>{{ $otherParty }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $transaction) }}">
                                        {{ $transaction->transaction_number }}
                                    </a>
                                </td>
                                <td class="text-danger">{{ $isDebit ? number_format($amount, 2) : '—' }}</td>
                                <td class="text-success">{{ !$isDebit ? number_format($amount, 2) : '—' }}</td>
                                <td class="{{ $balance < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">لا توجد معاملات خلال الفترة المحددة</td>
                            </tr>
                        @endforelse

                        <!-- Summary Row -->
                        @if(count($allTransactions) > 0)
                            <tr class="table-secondary fw-bold">
                                <td colspan="6" class="text-center">الإجمالي</td>
                                <td class="text-danger">{{ number_format($totalDebit, 2) }}</td>
                                <td class="text-success">{{ number_format($totalCredit, 2) }}</td>
                                <td class="{{ $balance < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($balance, 2) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(count($invoices) > 0)
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">الفواتير خلال الفترة</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>النوع</th>
                                <th>تاريخ الإصدار</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>المبلغ الإجمالي</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalInvoiceAmount = 0;
                                $totalPaidAmount = 0;
                                $totalRemainingAmount = 0;
                            @endphp

                            @foreach($invoices as $invoice)
                                @php
                                    $remaining = $invoice->total_amount - $invoice->paid_amount;
                                    $totalInvoiceAmount += $invoice->total_amount;
                                    $totalPaidAmount += $invoice->paid_amount;
                                    $totalRemainingAmount += $remaining;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice) }}">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($invoice->type == 'sale')
                                            <span class="badge bg-primary">بيع</span>
                                        @elseif($invoice->type == 'purchase')
                                            <span class="badge bg-secondary">شراء</span>
                                        @elseif($invoice->type == 'shipping')
                                            <span class="badge bg-info">شحن</span>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->issue_date }}</td>
                                    <td>{{ $invoice->due_date }}</td>
                                    <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td class="{{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($remaining, 2) }}
                                    </td>
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
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Summary Row -->
                            <tr class="table-secondary fw-bold">
                                <td colspan="5" class="text-center">الإجمالي</td>
                                <td>{{ number_format($totalInvoiceAmount, 2) }}</td>
                                <td>{{ number_format($totalPaidAmount, 2) }}</td>
                                <td class="{{ $totalRemainingAmount > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($totalRemainingAmount, 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4 text-center">
        <p class="mb-0 small text-muted">
            تم إنشاء كشف الحساب بتاريخ {{ now()->format('Y-m-d H:i') }}
        </p>
    </div>
</div>

<!-- Print Styles -->
@push('styles')
<style type="text/css" media="print">
    @page {
        size: A4;
        margin: 1cm;
    }
    body {
        font-size: 12pt;
    }
    .navbar, .sidebar, .card-header .btn, form, .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    table {
        width: 100% !important;
    }
</style>
@endpush
@endsection
