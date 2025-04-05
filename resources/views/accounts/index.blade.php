@extends('layouts.app')

@section('title', 'إدارة الحسابات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الحسابات</h2>
        <a href="{{ route('accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> إضافة حساب جديد
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة الحسابات</h5>
            <div class="btn-group" role="group">
                <a href="{{ route('accounts.index', ['type' => 'all']) }}" class="btn btn-outline-secondary {{ $type === 'all' ? 'active' : '' }}">الكل</a>
                <a href="{{ route('accounts.index', ['type' => 'customer']) }}" class="btn btn-outline-secondary {{ $type === 'customer' ? 'active' : '' }}">العملاء</a>
                <a href="{{ route('accounts.index', ['type' => 'shipping_company']) }}" class="btn btn-outline-secondary {{ $type === 'shipping_company' ? 'active' : '' }}">شركات الشحن</a>
                <a href="{{ route('accounts.index', ['type' => 'intermediary']) }}" class="btn btn-outline-secondary {{ $type === 'intermediary' ? 'active' : '' }}">الوسطاء</a>
            </div>
            <a href="{{ route('accounts.index', ['negative' => true]) }}" class="btn btn-danger ms-2 {{ request()->has('negative') ? 'active' : '' }}">
                <i class="bi bi-dash-circle"></i> الحسابات بالسالب
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الحساب</th>
                            <th>الاسم</th>
                            <th>النوع</th>
                            <th>الرصيد</th>
                            <th>جهة الاتصال</th>
                            <th>رقم الهاتف</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $account->account_number }}</td>
                                <td>{{ $account->name }}</td>
                                <td>
                                    @if($account->type == 'customer')
                                        <span class="badge bg-primary">عميل</span>
                                    @elseif($account->type == 'shipping_company')
                                        <span class="badge bg-success">شركة شحن</span>
                                    @elseif($account->type == 'intermediary')
                                        <span class="badge bg-info">وسيط</span>
                                    @endif
                                </td>
                                <td class="{{ $account->balance < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($account->balance, 2) }}
                                </td>
                                <td>{{ $account->contact_person ?? '-' }}</td>
                                <td>{{ $account->phone ?? '-' }}</td>
                                <td>
                                    @if($account->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('accounts.show', $account) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('accounts.statement', $account) }}" class="btn btn-sm btn-secondary">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('{{ $account->id }}', '{{ $account->name }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $account->id }}"
                                          action="{{ route('accounts.destroy', $account) }}"
                                          method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">لا توجد حسابات لعرضها</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $accounts->links() }}
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
