@extends('layouts.app')

@section('title', 'إدارة الفواتير')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الفواتير</h2>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> إنشاء فاتورة جديدة
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == '' || request('status') == 'all' ? 'active' : '' }}"
                               href="{{ route('invoices.index', ['status' => 'all']) }}">
                                الكل
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'draft' ? 'active' : '' }}"
                               href="{{ route('invoices.index', ['status' => 'draft']) }}">
                                المسودات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'issued' ? 'active' : '' }}"
                               href="{{ route('invoices.index', ['status' => 'issued']) }}">
                                الصادرة
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'partially_paid' ? 'active' : '' }}"
                               href="{{ route('invoices.index', ['status' => 'partially_paid']) }}">
                                مدفوعة جزئياً
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'paid' ? 'active' : '' }}"
                               href="{{ route('invoices.index', ['status' => 'paid']) }}">
                                المدفوعة
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'overdue' ? 'active' : '' }}"
                               href="{{ route('invoices.index', ['status' => 'overdue']) }}">
                                المتأخرة
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('invoices.index') }}" method="GET" class="d-flex">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <input type="text" name="search" class="form-control" placeholder="بحث برقم الفاتورة..." value="{{ request('search') }}">
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
                            <th>رقم الفاتورة</th>
                            <th>النوع</th>
                            <th>الحساب</th>
                            <th>إجمالي المبلغ</th>
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
                                <td>
                                    @if($invoice->type == 'sale')
                                        <span class="badge bg-primary">بيع</span>
                                    @elseif($invoice->type == 'purchase')
                                        <span class="badge bg-secondary">شراء</span>
                                    @elseif($invoice->type == 'shipping')
                                        <span class="badge bg-info">شحن</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('accounts.show', $invoice->account) }}">
                                        {{ $invoice->account->name }}
                                    </a>
                                </td>
                                <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                <td class="{{ ($invoice->total_amount - $invoice->paid_amount) > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}
                                </td>
                                <td>{{ $invoice->issue_date }}</td>
                                <td>{{ $invoice->due_date }}</td>
                                <td>
                                    @if($invoice->status == 'draft')
                                        <span class="badge bg-secondary">مسودة</span>
                                    @elseif($invoice->status == 'issued')
                                        <span class="badge bg-info">صادرة</span>
                                    @elseif($invoice->status == 'partially_paid')
                                        <span class="badge bg-warning">مدفوعة جزئياً</span>
                                    @elseif($invoice->status == 'paid')
                                        <span class="badge bg-success">مدفوعة</span>
                                    @elseif($invoice->status == 'overdue')
                                        <span class="badge bg-danger">متأخرة</span>
                                    @elseif($invoice->status == 'cancelled')
                                        <span class="badge bg-dark">ملغية</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($invoice->status == 'draft')
                                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="{{ route('invoices.issue', $invoice) }}" class="btn btn-sm btn-success"
                                               onclick="return confirm('هل أنت متأكد من إصدار هذه الفاتورة؟')">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        @endif
                                        @if($invoice->status != 'cancelled' && $invoice->status != 'paid')
                                            <a href="{{ route('invoices.payment', $invoice) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-cash"></i>
                                            </a>
                                        @endif
                                        @if($invoice->status == 'draft')
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="confirmDelete('{{ $invoice->id }}', '{{ $invoice->invoice_number }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $invoice->id }}"
                                                  action="{{ route('invoices.destroy', $invoice) }}"
                                                  method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                        @if($invoice->status != 'cancelled' && $invoice->status != 'draft')
                                            <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-sm btn-dark" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">لا توجد فواتير لعرضها</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(id, invoiceNumber) {
        if (confirm(`هل أنت متأكد من حذف الفاتورة رقم "${invoiceNumber}"؟`)) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endpush
