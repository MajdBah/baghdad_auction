@extends('layouts.app')

@section('title', 'إنشاء فاتورة جديدة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إنشاء فاتورة جديدة</h2>
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> العودة للفواتير
        </a>
    </div>

    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <!-- البيانات الأساسية للفاتورة -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">البيانات الأساسية للفاتورة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">الحساب <span class="text-danger">*</span></label>
                                <select class="form-select @error('account_id') is-invalid @enderror"
                                        id="account_id" name="account_id" required>
                                    <option value="" selected disabled>-- اختر الحساب --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('account_id', request('account_id')) == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="issue_date" class="form-label">تاريخ الفاتورة <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                       id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                @error('issue_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">نوع الفاتورة <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror"
                                        id="type" name="type" required>
                                    <option value="" selected disabled>-- اختر النوع --</option>
                                    <option value="invoice" {{ old('type') == 'invoice' ? 'selected' : '' }}>فاتورة بيع</option>
                                    <option value="bill" {{ old('type') == 'bill' ? 'selected' : '' }}>فاتورة شراء</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                       id="due_date" name="due_date" value="{{ old('due_date') }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="car_id" class="form-label">السيارة</label>
                                <select class="form-select @error('car_id') is-invalid @enderror"
                                        id="car_id" name="car_id">
                                    <option value="">-- اختر السيارة (اختياري) --</option>
                                    @foreach($cars as $car)
                                        <option value="{{ $car->id }}" {{ old('car_id', request('car_id')) == $car->id ? 'selected' : '' }}>
                                            {{ $car->make }} {{ $car->model }} ({{ $car->year }}) - {{ $car->vin }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('car_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="reference_number" class="form-label">الرقم المرجعي</label>
                                <input type="text" class="form-control @error('reference_number') is-invalid @enderror"
                                       id="reference_number" name="reference_number" value="{{ old('reference_number') }}">
                                <small class="form-text text-muted">أي رقم مرجعي خارجي مرتبط بهذه الفاتورة</small>
                                @error('reference_number')
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

                <!-- بنود الفاتورة -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">بنود الفاتورة</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                            <i class="bi bi-plus-circle"></i> إضافة بند
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">الوصف</th>
                                        <th style="width: 15%;">الكمية</th>
                                        <th style="width: 20%;">السعر الوحدة</th>
                                        <th style="width: 20%;">الإجمالي</th>
                                        <th style="width: 5%;">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsContainer">
                                    <!-- سيتم إضافة البنود هنا -->
                                    <tr class="item-row" id="item-row-template" style="display: none;">
                                        <td>
                                            <input type="text" class="form-control item-description" name="items[0][description]" placeholder="وصف البند" required>
                                        </td>
                                        <td>
                                            <input type="number" min="1" step="1" class="form-control item-quantity" name="items[0][quantity]" placeholder="الكمية" value="1" required>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" min="0" step="0.01" class="form-control item-price" name="items[0][unit_price]" placeholder="السعر" value="0.00" required>
                                                <span class="input-group-text">$</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control item-total" readonly value="0.00">
                                                <span class="input-group-text">$</span>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-item-btn">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                        <input type="hidden" name="items[0][item_type]" value="standard">
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3" role="alert" id="noItemsAlert" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i> الرجاء إضافة بند واحد على الأقل للفاتورة.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- ملخص الفاتورة -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">ملخص الفاتورة</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>المجموع الفرعي:</span>
                                <span id="subtotal">0.00 $</span>
                            </div>
                            <div class="mb-3">
                                <label for="discount" class="form-label">الخصم</label>
                                <div class="input-group">
                                    <input type="number" min="0" step="0.01" class="form-control @error('discount') is-invalid @enderror"
                                           id="discount" name="discount" value="{{ old('discount', 0) }}">
                                    <span class="input-group-text">$</span>
                                </div>
                                @error('discount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="tax" class="form-label">الضريبة</label>
                                <div class="input-group">
                                    <input type="number" min="0" step="0.01" class="form-control @error('tax') is-invalid @enderror"
                                           id="tax" name="tax" value="{{ old('tax', 0) }}">
                                    <span class="input-group-text">$</span>
                                </div>
                                @error('tax')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="shipping_fee" class="form-label">رسوم الشحن</label>
                                <div class="input-group">
                                    <input type="number" min="0" step="0.01" class="form-control @error('shipping_fee') is-invalid @enderror"
                                           id="shipping_fee" name="shipping_fee" value="{{ old('shipping_fee', 0) }}">
                                    <span class="input-group-text">$</span>
                                </div>
                                @error('shipping_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>الإجمالي:</span>
                                <span id="grandTotal">0.00 $</span>
                            </div>
                            <input type="hidden" name="total_amount" id="total_amount" value="0">
                        </div>
                    </div>
                </div>

                <!-- خيارات الفاتورة -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">خيارات الفاتورة</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">حالة الفاتورة</label>
                            <select class="form-select @error('status') is-invalid @enderror"
                                    id="status" name="status">
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                                <option value="issued" {{ old('status') == 'issued' ? 'selected' : '' }}>إصدار مباشر</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="create_transaction" name="create_transaction"
                                   {{ old('create_transaction') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_transaction">
                                إنشاء معاملة مالية تلقائية
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="send_email" name="send_email"
                                   {{ old('send_email') ? 'checked' : '' }}>
                            <label class="form-check-label" for="send_email">
                                إرسال الفاتورة بالبريد الإلكتروني للعميل
                            </label>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> حفظ الفاتورة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemCounter = 0;
        const itemTemplate = document.getElementById('item-row-template');
        const itemsContainer = document.getElementById('itemsContainer');
        const addItemBtn = document.getElementById('addItemBtn');
        const subtotalElement = document.getElementById('subtotal');
        const grandTotalElement = document.getElementById('grandTotal');
        const totalAmountInput = document.getElementById('total_amount');
        const discountInput = document.getElementById('discount');
        const taxInput = document.getElementById('tax');
        const shippingFeeInput = document.getElementById('shipping_fee');
        const noItemsAlert = document.getElementById('noItemsAlert');
        const invoiceForm = document.getElementById('invoiceForm');
        const issueDateInput = document.getElementById('issue_date');
        const dueDateInput = document.getElementById('due_date');

        // إضافة بند جديد
        function addNewItem() {
            itemCounter++;
            console.log("إضافة عنصر جديد #" + itemCounter);

            // نسخ قالب البند
            const newRow = itemTemplate.cloneNode(true);
            newRow.style.display = 'table-row';
            newRow.id = `item-row-${itemCounter}`;

            // تحديث أسماء الحقول بالمؤشر الصحيح
            newRow.querySelectorAll('input').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace('[0]', `[${itemCounter}]`));
                }
            });

            // إضافة مستمعات الأحداث للحقول
            const quantityInput = newRow.querySelector('.item-quantity');
            const priceInput = newRow.querySelector('.item-price');
            const totalInput = newRow.querySelector('.item-total');

            function updateItemTotal() {
                const quantity = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const total = quantity * price;
                totalInput.value = total.toFixed(2);
                updateInvoiceTotal();
            }

            quantityInput.addEventListener('input', updateItemTotal);
            priceInput.addEventListener('input', updateItemTotal);

            // إضافة مستمع حدث لزر الحذف
            const removeBtn = newRow.querySelector('.remove-item-btn');
            removeBtn.addEventListener('click', function() {
                newRow.remove();
                updateInvoiceTotal();
                checkItemsCount();
            });

            // إضافة البند إلى الجدول
            itemsContainer.appendChild(newRow);
            checkItemsCount();

            // تحديث الإجمالي
            updateItemTotal();

            return newRow;
        }

        // إضافة بند عند الضغط على زر الإضافة
        addItemBtn.addEventListener('click', function() {
            console.log("تم النقر على زر إضافة بند");
            addNewItem();
        });

        // تحديث إجمالي الفاتورة
        function updateInvoiceTotal() {
            let subtotal = 0;

            // جمع إجماليات البنود
            document.querySelectorAll('.item-total').forEach(input => {
                if (input.closest('tr').style.display !== 'none') {
                    subtotal += parseFloat(input.value) || 0;
                }
            });

            const discount = parseFloat(discountInput.value) || 0;
            const tax = parseFloat(taxInput.value) || 0;
            const shippingFee = parseFloat(shippingFeeInput.value) || 0;

            // حساب الإجمالي النهائي
            const grandTotal = subtotal - discount + tax + shippingFee;

            // تحديث العناصر
            subtotalElement.textContent = subtotal.toFixed(2) + ' $';
            grandTotalElement.textContent = grandTotal.toFixed(2) + ' $';
            totalAmountInput.value = grandTotal.toFixed(2);

            console.log("تم تحديث إجمالي الفاتورة: " + grandTotal.toFixed(2));
        }

        // مستمعات الأحداث للعناصر التي تؤثر على الإجمالي
        discountInput.addEventListener('input', updateInvoiceTotal);
        taxInput.addEventListener('input', updateInvoiceTotal);
        shippingFeeInput.addEventListener('input', updateInvoiceTotal);

        // التحقق من وجود بنود
        function checkItemsCount() {
            const visibleItems = Array.from(itemsContainer.querySelectorAll('.item-row'))
                .filter(row => row.style.display !== 'none' && row.id !== 'item-row-template');

            console.log("عدد البنود المرئية: " + visibleItems.length);

            if (visibleItems.length === 0) {
                noItemsAlert.style.display = 'block';
            } else {
                noItemsAlert.style.display = 'none';
            }
        }

        // التحقق قبل إرسال النموذج
        invoiceForm.addEventListener('submit', function(e) {
            const visibleItems = Array.from(itemsContainer.querySelectorAll('.item-row'))
                .filter(row => row.style.display !== 'none' && row.id !== 'item-row-template');

            if (visibleItems.length === 0) {
                e.preventDefault();
                noItemsAlert.style.display = 'block';
                window.scrollTo(0, noItemsAlert.offsetTop - 100);
                console.log("تم إلغاء الإرسال: لا توجد بنود");
                return false;
            }

            console.log("تم إرسال النموذج بنجاح");
        });

        // تحديد تاريخ الاستحقاق الافتراضي (+30 يوم من تاريخ الفاتورة)
        function updateDueDate() {
            const invoiceDate = new Date(issueDateInput.value);
            if (!isNaN(invoiceDate.getTime())) {
                const dueDate = new Date(invoiceDate);
                dueDate.setDate(dueDate.getDate() + 30);
                dueDateInput.value = dueDate.toISOString().split('T')[0];
                console.log("تم تحديث تاريخ الاستحقاق: " + dueDateInput.value);
            }
        }

        if (!dueDateInput.value) {
            updateDueDate();
        }

        issueDateInput.addEventListener('change', updateDueDate);

        // إضافة بند افتراضي عند تحميل الصفحة
        console.log("تحميل الصفحة - إضافة بند افتراضي");
        addNewItem();
    });
</script>
@endpush
