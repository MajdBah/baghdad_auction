@extends('layouts.app')

@section('title', 'Account Statement Report')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">كشف حساب تفصيلي</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
        <li class="breadcrumb-item active">كشف حساب</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            معايير البحث
        </div>
        <div class="card-body">
            <form action="{{ route('reports.account_statement') }}" method="GET" id="statement-form">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="account_id" name="account_id" required>
                                <option value="">اختر الحساب</option>
                                @foreach(App\Models\Account::where('is_active', true)->orderBy('name')->get() as $account)
                                <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->account_number }}) - {{ $account->type }}
                                </option>
                                @endforeach
                            </select>
                            <label for="account_id">الحساب</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="start_date" name="start_date" type="date" value="{{ request('start_date', now()->subMonths(1)->format('Y-m-d')) }}" required />
                            <label for="start_date">من تاريخ</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="end_date" name="end_date" type="date" value="{{ request('end_date', now()->format('Y-m-d')) }}" required />
                            <label for="end_date">إلى تاريخ</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> عرض كشف الحساب
                    </button>
                    @if(isset($account))
                    <button type="button" class="btn btn-success" onclick="printStatement()">
                        <i class="fas fa-print me-1"></i> طباعة
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(isset($account))
    <div class="card mb-4" id="statement-card">
        <div class="card-header">
            <i class="fas fa-file-alt me-1"></i>
            كشف حساب: {{ $account->name }}
        </div>
        <div class="card-body">
            <div class="mb-4 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-6">
                        <h5>بيانات الحساب</h5>
                        <p><strong>اسم الحساب:</strong> {{ $account->name }}</p>
                        <p><strong>رقم الحساب:</strong> {{ $account->account_number }}</p>
                        <p><strong>نوع الحساب:</strong>
                            @if($account->type == 'customer')
                                عميل
                            @elseif($account->type == 'shipping_company')
                                شركة شحن
                            @elseif($account->type == 'intermediary')
                                وسيط
                            @else
                                {{ $account->type }}
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>معلومات التقرير</h5>
                        <p><strong>الفترة من:</strong> {{ $startDate->format('Y-m-d') }}</p>
                        <p><strong>إلى:</strong> {{ $endDate->format('Y-m-d') }}</p>
                        <p><strong>الرصيد الافتتاحي:</strong> {{ number_format($openingBalance, 2) }}</p>
                        <p><strong>الرصيد الختامي:</strong> {{ number_format($currentBalance, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الوصف</th>
                            <th>المرجع</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary">
                            <td>{{ $startDate->format('Y-m-d') }}</td>
                            <td>رصيد افتتاحي</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>{{ number_format($openingBalance, 2) }}</td>
                        </tr>

                        @foreach($statement as $entry)
                        <tr>
                            <td>{{ $entry['date']->format('Y-m-d') }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td>{{ $entry['reference'] }}</td>
                            <td>{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                            <td>{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                            <td>{{ number_format($entry['balance'], 2) }}</td>
                        </tr>
                        @endforeach

                        <tr class="table-secondary">
                            <td>{{ $endDate->format('Y-m-d') }}</td>
                            <td>رصيد ختامي</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>{{ number_format($currentBalance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <h5>ملخص الحركات</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">إجمالي المدين</h6>
                                <p class="card-text fs-4">{{ number_format(collect($statement)->sum('debit'), 2) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">إجمالي الدائن</h6>
                                <p class="card-text fs-4">{{ number_format(collect($statement)->sum('credit'), 2) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">عدد الحركات</h6>
                                <p class="card-text fs-4">{{ count($statement) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoices->count() > 0)
            <div class="mt-4">
                <h5>الفواتير خلال الفترة</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>النوع</th>
                                <th>تاريخ الإصدار</th>
                                <th>المبلغ الإجمالي</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td>
                                    @if($invoice->type == 'invoice')
                                        فاتورة مبيعات
                                    @elseif($invoice->type == 'bill')
                                        فاتورة شراء
                                    @else
                                        {{ $invoice->type }}
                                    @endif
                                </td>
                                <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                <td>{{ number_format($invoice->balance, 2) }}</td>
                                <td>
                                    @if($invoice->status == 'issued')
                                        <span class="badge bg-warning text-dark">صادرة</span>
                                    @elseif($invoice->status == 'partially_paid')
                                        <span class="badge bg-info">مدفوعة جزئياً</span>
                                    @elseif($invoice->status == 'paid')
                                        <span class="badge bg-success">مدفوعة</span>
                                    @elseif($invoice->status == 'cancelled')
                                        <span class="badge bg-danger">ملغاة</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $invoice->status }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function printStatement() {
        const printContents = document.getElementById('statement-card').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container mt-4">
                <h1 class="text-center mb-4">كشف حساب</h1>
                ${printContents}
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContents;
    }
</script>
@endsection
