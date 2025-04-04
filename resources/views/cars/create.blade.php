@extends('layouts.app')

@section('title', 'إضافة سيارة جديدة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إضافة سيارة جديدة</h2>
        <a href="{{ route('cars.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> العودة للسيارات
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">بيانات السيارة</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('cars.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <!-- بيانات السيارة -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">بيانات السيارة الأساسية</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="make" class="form-label">الماركة <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('make') is-invalid @enderror"
                                               id="make" name="make" value="{{ old('make') }}" required>
                                        @error('make')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="model" class="form-label">الموديل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('model') is-invalid @enderror"
                                               id="model" name="model" value="{{ old('model') }}" required>
                                        @error('model')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="year" class="form-label">سنة الصنع <span class="text-danger">*</span></label>
                                        <input type="number" min="1900" max="{{ date('Y') + 1 }}" class="form-control @error('year') is-invalid @enderror"
                                               id="year" name="year" value="{{ old('year') }}" required>
                                        @error('year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="color" class="form-label">اللون</label>
                                        <input type="text" class="form-control @error('color') is-invalid @enderror"
                                               id="color" name="color" value="{{ old('color') }}">
                                        @error('color')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="vin" class="form-label">رقم الهيكل (VIN) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('vin') is-invalid @enderror"
                                           id="vin" name="vin" value="{{ old('vin') }}" required>
                                    <small class="form-text text-muted">رقم التعريف الفريد للسيارة (17 حرف)</small>
                                    @error('vin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">وصف السيارة</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">حالة السيارة <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror"
                                            id="status" name="status" required>
                                        <option value="" disabled selected>-- اختر الحالة --</option>
                                        <option value="in_auction" {{ old('status') == 'in_auction' ? 'selected' : '' }}>بالمزاد</option>
                                        <option value="purchased" {{ old('status') == 'purchased' ? 'selected' : '' }}>تم الشراء</option>
                                        <option value="shipping" {{ old('status') == 'shipping' ? 'selected' : '' }}>قيد الشحن</option>
                                        <option value="delivered" {{ old('status') == 'delivered' ? 'selected' : '' }}>تم التسليم</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="images" class="form-label">صور السيارة</label>
                                    <input type="file" class="form-control @error('images') is-invalid @enderror"
                                           id="images" name="images[]" multiple accept="image/*">
                                    <small class="form-text text-muted">يمكنك اختيار عدة صور (الحد الأقصى 5 صور)</small>
                                    @error('images')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- بيانات المعاملة -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">بيانات العملاء والشحن</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="customer_account_id" class="form-label">العميل</label>
                                    <select class="form-select @error('customer_account_id') is-invalid @enderror"
                                            id="customer_account_id" name="customer_account_id">
                                        <option value="">-- اختر العميل --</option>
                                        @foreach($customers as $account)
                                            <option value="{{ $account->id }}" {{ old('customer_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} ({{ $account->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="shipping_company_id" class="form-label">شركة الشحن</label>
                                    <select class="form-select @error('shipping_company_id') is-invalid @enderror"
                                            id="shipping_company_id" name="shipping_company_id">
                                        <option value="">-- اختر شركة الشحن --</option>
                                        @foreach($shippingCompanies as $account)
                                            <option value="{{ $account->id }}" {{ old('shipping_company_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} ({{ $account->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('shipping_company_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="auction_name" class="form-label">اسم المزاد</label>
                                    <input type="text" class="form-control @error('auction_name') is-invalid @enderror"
                                           id="auction_name" name="auction_name" value="{{ old('auction_name') }}">
                                    @error('auction_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="purchase_price" class="form-label">سعر الشراء <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control @error('purchase_price') is-invalid @enderror"
                                                   id="purchase_price" name="purchase_price" value="{{ old('purchase_price') }}" required>
                                            <span class="input-group-text">$</span>
                                        </div>
                                        @error('purchase_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="purchase_date" class="form-label">تاريخ الشراء</label>
                                        <input type="date" class="form-control @error('purchase_date') is-invalid @enderror"
                                               id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}">
                                        @error('purchase_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="shipping_cost" class="form-label">تكلفة الشحن</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control @error('shipping_cost') is-invalid @enderror"
                                                   id="shipping_cost" name="shipping_cost" value="{{ old('shipping_cost', 0) }}">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        @error('shipping_cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="shipping_date" class="form-label">تاريخ الشحن</label>
                                        <input type="date" class="form-control @error('shipping_date') is-invalid @enderror"
                                               id="shipping_date" name="shipping_date" value="{{ old('shipping_date') }}">
                                        @error('shipping_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="delivery_date" class="form-label">تاريخ التسليم المتوقع</label>
                                        <input type="date" class="form-control @error('delivery_date') is-invalid @enderror"
                                               id="delivery_date" name="delivery_date" value="{{ old('delivery_date') }}">
                                        @error('delivery_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="actual_delivery_date" class="form-label">تاريخ التسليم الفعلي</label>
                                        <input type="date" class="form-control @error('actual_delivery_date') is-invalid @enderror"
                                               id="actual_delivery_date" name="actual_delivery_date" value="{{ old('actual_delivery_date') }}">
                                        @error('actual_delivery_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">ملاحظات</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="create_transactions" name="create_transactions"
                                {{ old('create_transactions') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_transactions">
                                إنشاء معاملات مالية تلقائياً (شراء وشحن)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> حفظ السيارة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusField = document.getElementById('status');
        const shippingFields = document.getElementById('shipping_company_id');
        const shippingCostField = document.getElementById('shipping_cost');
        const shippingDateField = document.getElementById('shipping_date');
        const deliveryDateField = document.getElementById('delivery_date');
        const actualDeliveryDateField = document.getElementById('actual_delivery_date');

        // تحديث حقول الشحن والتسليم بناءً على الحالة المختارة
        statusField.addEventListener('change', function() {
            const status = this.value;

            if (status === 'in_auction' || status === 'purchased') {
                shippingFields.required = false;
                shippingCostField.required = false;
                shippingDateField.required = false;
            } else if (status === 'shipping' || status === 'delivered') {
                shippingFields.required = true;
                shippingCostField.required = true;
                shippingDateField.required = true;
            }

            if (status === 'delivered') {
                actualDeliveryDateField.required = true;
            } else {
                actualDeliveryDateField.required = false;
            }
        });

        // تنفيذ التحقق الأولي عند تحميل الصفحة
        if (statusField.value) {
            statusField.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
