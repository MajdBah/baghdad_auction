@extends('layouts.app')

@section('content')
<div class="container" dir="rtl" lang="ar">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-invoice ms-2"></i>إدارة فواتير الوسيط</h5>
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

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">عرض الفواتير حسب حساب الوسيط</h5>
                                    <form action="{{ route('broker.invoices.list') }}" method="GET" class="row g-3">
                                        <div class="col-md-4">
                                            <label for="broker_account_id" class="form-label">اختر حساب الوسيط:</label>
                                            <select name="broker_account_id" id="broker_account_id" class="form-select" required>
                                                <option value="">-- اختر حساب الوسيط --</option>
                                                @foreach($brokerAccounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->account_number }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="direction" class="form-label">نوع الفواتير:</label>
                                            <select name="direction" id="direction" class="form-select">
                                                <option value="">جميع الفواتير</option>
                                                <option value="positive">الفواتير الموجبة (إيرادات)</option>
                                                <option value="negative">الفواتير السالبة (مصروفات)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search ms-1"></i>عرض الفواتير
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="mb-0"><i class="fas fa-info-circle ms-2"></i>نظام فواتير الوسيط</h6>
                        <p class="mb-0 mt-2">
                            هذا النظام يسمح بإدارة الفواتير بين حساب الوسيط والحسابات الأخرى، حيث:
                        </p>
                        <ul class="mt-2 mb-0">
                            <li>
                                <strong>الفواتير الموجبة:</strong> من حساب العميل إلى حساب الوسيط (إيرادات للوسيط)
                            </li>
                            <li>
                                <strong>الفواتير السالبة:</strong> من حساب الوسيط إلى حساب شركة الشحن (مصروفات للوسيط)
                            </li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right ms-1"></i>العودة إلى لوحة التحكم
                        </a>
                        <a href="{{ route('broker.invoices.create') }}" class="btn btn-success">
                            <i class="fas fa-plus-circle ms-1"></i>إنشاء فاتورة جديدة
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
