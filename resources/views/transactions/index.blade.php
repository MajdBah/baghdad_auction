@extends('layouts.app')

@section('title', 'العمليات المالية')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة العمليات المالية</h2>
        <div>
            <a href="{{ route('transactions.transferForm') }}" class="btn btn-success me-2">
                <i class="bi bi-arrow-left-right"></i> تحويل بين الحسابات
            </a>
            <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> إضافة عملية جديدة
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'all' ? 'active' : '' }}" href="{{ route('transactions.index', ['type' => 'all']) }}">
                                الكل
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'purchase' ? 'active' : '' }}" href="{{ route('transactions.index', ['type' => 'purchase']) }}">
                                عمليات الشراء
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'shipping' ? 'active' : '' }}" href="{{ route('transactions.index', ['type' => 'shipping']) }}">
                                الشحن
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'transfer' ? 'active' : '' }}" href="{{ route('transactions.index', ['type' => 'transfer']) }}">
                                التحويلات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'payment' ? 'active' : '' }}" href="{{ route('transactions.index', ['type' => 'payment']) }}">
                                الدفعات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'commission' ? 'active' : '' }}" href="{{ route('transactions.index', ['type' => 'commission']) }}">
                                العمولات
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('transactions.index') }}" method="GET" class="d-flex">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="text" name="search" class="form-control" placeholder="بحث برقم المعاملة..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>رقم المعاملة</th>
                            <th>النوع</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>المبلغ</th>
                            <th>العمولة</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_number }}</td>
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
                                <td>
                                    @if($transaction->fromAccount)
                                        <a href="{{ route('accounts.show', $transaction->fromAccount) }}">
                                            {{ $transaction->fromAccount->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->toAccount)
                                        <a href="{{ route('accounts.show', $transaction->toAccount) }}">
                                            {{ $transaction->toAccount->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                                <td>
                                    @if($transaction->with_commission)
                                        <span class="text-danger">{{ number_format($transaction->commission_amount, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
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
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($transaction->status == 'pending')
                                            <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success"
                                                    onclick="confirmProcess('{{ $transaction->id }}')">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="confirmCancel('{{ $transaction->id }}')">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <form id="process-form-{{ $transaction->id }}"
                                                action="{{ route('transactions.process', $transaction) }}"
                                                method="POST" class="d-none">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                            <form id="cancel-form-{{ $transaction->id }}"
                                                action="{{ route('transactions.cancel', $transaction) }}"
                                                method="POST" class="d-none">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">لا توجد معاملات لعرضها</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmProcess(id) {
        if (confirm('هل أنت متأكد من تنفيذ هذه المعاملة؟ سيتم تعديل أرصدة الحسابات ذات الصلة.')) {
            document.getElementById('process-form-' + id).submit();
        }
    }

    function confirmCancel(id) {
        if (confirm('هل أنت متأكد من إلغاء هذه المعاملة؟')) {
            document.getElementById('cancel-form-' + id).submit();
        }
    }
</script>
@endpush
