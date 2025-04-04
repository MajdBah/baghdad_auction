@extends('layouts.app')

@section('title', 'تفاصيل الحساب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تفاصيل الحساب: {{ $account->name }}</h2>
        <div>
            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-warning me-2">
                <i class="bi bi-pencil"></i> تعديل الحساب
            </a>
            <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> العودة للحسابات
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Account Information -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">معلومات الحساب</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>رقم الحساب:</strong></span>
                            <span>{{ $account->account_number }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>نوع الحساب:</strong></span>
                            <span>
                                @if($account->type == 'customer')
                                    <span class="badge bg-primary">عميل</span>
                                @elseif($account->type == 'shipping_company')
                                    <span class="badge bg-success">شركة شحن</span>
                                @elseif($account->type == 'intermediary')
                                    <span class="badge bg-info">وسيط</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>الرصيد الحالي:</strong></span>
                            <span class="{{ $account->balance < 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                {{ number_format($account->balance, 2) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>الشخص المسؤول:</strong></span>
                            <span>{{ $account->contact_person ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>رقم الهاتف:</strong></span>
                            <span>{{ $account->phone ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>البريد الإلكتروني:</strong></span>
                            <span>{{ $account->email ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>العنوان:</strong></span>
                            <span>{{ $account->address ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>الحالة:</strong></span>
                            <span>
                                @if($account->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>تاريخ الإنشاء:</strong></span>
                            <span>{{ $account->created_at->format('Y-m-d') }}</span>
                        </li>
                        @if($account->notes)
                        <li class="list-group-item">
                            <strong>ملاحظات:</strong>
                            <p class="mt-2 mb-0">{{ $account->notes }}</p>
                        </li>
                        @endif
                    </ul>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('accounts.statement', $account) }}" class="btn btn-primary">
                            <i class="bi bi-file-text"></i> كشف حساب
                        </a>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete('{{ $account->id }}', '{{ $account->name }}')">
                            <i class="bi bi-trash"></i> حذف الحساب
                        </button>
                        <form id="delete-form-{{ $account->id }}" action="{{ route('accounts.destroy', $account) }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Stats and Quick Actions -->
        <div class="col-md-7 mb-4">
            <div class="row">
                <!-- Stats -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">إحصائيات الحساب</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h1 class="mb-0">{{ $stats['total_transactions'] }}</h1>
                                            <p class="mb-0">إجمالي المعاملات</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h1 class="mb-0">{{ $stats['total_invoices'] }}</h1>
                                            <p class="mb-0">إجمالي الفواتير</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h1 class="mb-0">{{ $stats['unpaid_invoices'] }}</h1>
                                            <p class="mb-0">الفواتير غير المدفوعة</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">إجراءات سريعة</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('transactions.create', ['account_id' => $account->id]) }}" class="btn btn-primary d-block">
                                        <i class="bi bi-plus-circle"></i> معاملة جديدة
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('transfers.create', ['from_account_id' => $account->id]) }}" class="btn btn-success d-block">
                                        <i class="bi bi-arrow-left-right"></i> تحويل رصيد
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('invoices.create', ['account_id' => $account->id]) }}" class="btn btn-info d-block">
                                        <i class="bi bi-receipt"></i> فاتورة جديدة
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">أحدث المعاملات</h5>
                            <a href="{{ route('transactions.index', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>الرقم</th>
                                            <th>النوع</th>
                                            <th>المبلغ</th>
                                            <th>التاريخ</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($incomingTransactions) > 0 || count($outgoingTransactions) > 0)
                                            @foreach($incomingTransactions as $transaction)
                                                <tr>
                                                    <td>{{ $transaction->transaction_number }}</td>
                                                    <td>
                                                        <span class="badge bg-success">وارد</span>
                                                        @if($transaction->type == 'purchase')
                                                            <span class="badge bg-secondary">شراء</span>
                                                        @elseif($transaction->type == 'shipping')
                                                            <span class="badge bg-info">شحن</span>
                                                        @elseif($transaction->type == 'transfer')
                                                            <span class="badge bg-primary">تحويل</span>
                                                        @elseif($transaction->type == 'payment')
                                                            <span class="badge bg-warning">دفعة</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-success">+ {{ number_format($transaction->amount, 2) }}</td>
                                                    <td>{{ $transaction->transaction_date }}</td>
                                                    <td>
                                                        @if($transaction->status == 'completed')
                                                            <span class="badge bg-success">مكتملة</span>
                                                        @elseif($transaction->status == 'pending')
                                                            <span class="badge bg-warning">معلقة</span>
                                                        @elseif($transaction->status == 'cancelled')
                                                            <span class="badge bg-danger">ملغية</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @foreach($outgoingTransactions as $transaction)
                                                <tr>
                                                    <td>{{ $transaction->transaction_number }}</td>
                                                    <td>
                                                        <span class="badge bg-danger">صادر</span>
                                                        @if($transaction->type == 'purchase')
                                                            <span class="badge bg-secondary">شراء</span>
                                                        @elseif($transaction->type == 'shipping')
                                                            <span class="badge bg-info">شحن</span>
                                                        @elseif($transaction->type == 'transfer')
                                                            <span class="badge bg-primary">تحويل</span>
                                                        @elseif($transaction->type == 'payment')
                                                            <span class="badge bg-warning">دفعة</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-danger">- {{ number_format($transaction->amount, 2) }}</td>
                                                    <td>{{ $transaction->transaction_date }}</td>
                                                    <td>
                                                        @if($transaction->status == 'completed')
                                                            <span class="badge bg-success">مكتملة</span>
                                                        @elseif($transaction->status == 'pending')
                                                            <span class="badge bg-warning">معلقة</span>
                                                        @elseif($transaction->status == 'cancelled')
                                                            <span class="badge bg-danger">ملغية</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6" class="text-center">لا توجد معاملات لعرضها</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">أحدث الفواتير</h5>
                            <a href="{{ route('invoices.index', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>رقم الفاتورة</th>
                                            <th>المبلغ</th>
                                            <th>المدفوع</th>
                                            <th>المتبقي</th>
                                            <th>تاريخ الإصدار</th>
                                            <th>تاريخ الاستحقاق</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                                <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                                <td>{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                                                <td>{{ $invoice->issue_date }}</td>
                                                <td>{{ $invoice->due_date }}</td>
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
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">لا توجد فواتير لعرضها</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        if (confirm(`هل أنت متأكد من حذف الحساب "${name}"؟`)) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endpush
