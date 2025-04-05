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
                                    <!-- Plantilla oculta - no se envía al servidor -->
                                    <tr class="item-row" id="item-row-template" style="display: none;">
                                        <td>
                                            <input type="text" class="form-control item-description" name="_template_items[0][description]" placeholder="وصف البند" disabled>
                                        </td>
                                        <td>
                                            <input type="number" min="1" step="1" class="form-control item-quantity" name="_template_items[0][quantity]" placeholder="الكمية" value="1" disabled>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" min="0" step="0.01" class="form-control item-price" name="_template_items[0][unit_price]" placeholder="السعر" value="0.00" disabled>
                                                <span class="input-group-text">$</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control item-total" readonly value="0.00" disabled>
                                                <span class="input-group-text">$</span>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-item-btn" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                        <input type="hidden" name="_template_items[0][item_type]" value="standard" disabled>
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
    $(document).ready(function() {
        console.log('تم تحميل سكريبت الفاتورة - نسخة جديدة');

        // تعريف المتغيرات
        let itemCounter = -1; // Iniciar desde -1 para que el primer ítem sea 0

        // إضافة أول بند عند تحميل الصفحة
        addNewItem();

        // دالة إضافة بند جديد
        function addNewItem() {
            itemCounter++;
            console.log('إضافة بند جديد: ' + itemCounter);

            const newRow = $('<tr>', {
                class: 'item-row',
                id: 'item-row-' + itemCounter
            });

            newRow.html(`
                <td>
                    <input type="text" class="form-control item-description" name="items[${itemCounter}][description]" placeholder="وصف البند" required>
                </td>
                <td>
                    <input type="number" min="1" step="1" class="form-control item-quantity" name="items[${itemCounter}][quantity]" placeholder="الكمية" value="1" required>
                </td>
                <td>
                    <div class="input-group">
                        <input type="number" min="0" step="0.01" class="form-control item-price" name="items[${itemCounter}][unit_price]" placeholder="السعر" value="0.00" required>
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
                <input type="hidden" name="items[${itemCounter}][item_type]" value="standard">
            `);

            // إضافة الصف للجدول
            $('#itemsContainer').append(newRow);

            // حذف الإشعار (إذا كان ظاهراً)
            $('#noItemsAlert').hide();

            // تحديث الإجمالي
            updateInvoiceTotal();

            // إضافة مستمعي الأحداث
            attachEventsToRow(newRow);
        }

        // إضافة مستمعي الأحداث للصف
        function attachEventsToRow(row) {
            // تحديث إجمالي البند
            $(row).find('.item-quantity, .item-price').on('input', function() {
                updateRowTotal(row);
            });

            // حذف البند
            $(row).find('.remove-item-btn').on('click', function() {
                if ($('.item-row:visible').length > 1) {
                    $(row).remove();
                    updateInvoiceTotal();
                } else {
                    alert('يجب أن يكون هناك بند واحد على الأقل في الفاتورة');
                }
            });

            // تحديث إجمالي البند أول مرة
            updateRowTotal(row);
        }

        // تحديث إجمالي البند
        function updateRowTotal(row) {
            const quantity = parseFloat($(row).find('.item-quantity').val()) || 0;
            const price = parseFloat($(row).find('.item-price').val()) || 0;
            const total = (quantity * price).toFixed(2);
            $(row).find('.item-total').val(total);
            updateInvoiceTotal();
        }

        // تحديث إجمالي الفاتورة
        function updateInvoiceTotal() {
            let subtotal = 0;

            // حساب المجموع الفرعي - only count visible rows
            $('.item-row:visible .item-total').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });

            // الحصول على قيم الخصم والضريبة والشحن
            const discount = parseFloat($('#discount').val()) || 0;
            const tax = parseFloat($('#tax').val()) || 0;
            const shipping = parseFloat($('#shipping_fee').val()) || 0;

            // حساب الإجمالي
            const grandTotal = subtotal - discount + tax + shipping;

            // تحديث العناصر
            $('#subtotal').text(subtotal.toFixed(2) + ' $');
            $('#grandTotal').text(grandTotal.toFixed(2) + ' $');
            $('#total_amount').val(grandTotal.toFixed(2));
        }

        // ربط مستمع حدث لزر إضافة البند
        $('#addItemBtn').on('click', function() {
            addNewItem();
        });

        // ربط مستمعي الأحداث للخصم والضريبة والشحن
        $('#discount, #tax, #shipping_fee').on('input', updateInvoiceTotal);

        // تعيين تاريخ الاستحقاق تلقائياً (+30 يوم من تاريخ الفاتورة)
        $('#issue_date').on('change', function() {
            const issueDate = new Date($(this).val());
            if (!isNaN(issueDate.getTime())) {
                const dueDate = new Date(issueDate);
                dueDate.setDate(dueDate.getDate() + 30);
                const dueDateString = dueDate.toISOString().split('T')[0];
                $('#due_date').val(dueDateString);
            }
        });

        // تحديث تاريخ الاستحقاق عند تحميل الصفحة
        $('#issue_date').trigger('change');

        // التحقق من صحة النموذج قبل الإرسال
        $('#invoiceForm').on('submit', function(e) {
            e.preventDefault(); // منع الإرسال التلقائي للنموذج

            // التحقق من وجود عنصر واحد على الأقل في الفاتورة
            if ($('.item-row:visible').length === 0) {
                $('#noItemsAlert').show();
                return false;
            }

            // التأكد من تعطيل قالب البند المخفي
            $('#item-row-template').find('input, select').prop('disabled', true);

            // إضافة سجل في وحدة التحكم لتتبع الأخطاء
            console.log('تقديم نموذج الفاتورة - بدء العملية');

            // إظهار مؤشر التحميل
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...');
            submitBtn.prop('disabled', true);

            // إرسال النموذج مباشرة بدون AJAX
            this.submit();

            // إذا وصلنا إلى هنا، فقد تم إرسال النموذج بنجاح
            return true;
        });
    });
</script>
@endpush
