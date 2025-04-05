                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="from_account_id" class="form-label">حساب المصدر <span class="text-danger">*</span></label>
                                <select class="form-select @error('from_account_id') is-invalid @enderror"
                                        id="from_account_id" name="from_account_id" required>
                                    <option value="" selected disabled>-- اختر حساب المصدر --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('from_account_id', $invoice->from_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">حساب الوجهة <span class="text-danger">*</span></label>
                                <select class="form-select @error('account_id') is-invalid @enderror"
                                        id="account_id" name="account_id" required>
                                    <option value="" selected disabled>-- اختر حساب الوجهة --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('account_id', $invoice->account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_id')
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
                                    <option value="invoice" {{ old('type', $invoice->type) == 'invoice' ? 'selected' : '' }}>فاتورة بيع (INV)</option>
                                    <option value="bill" {{ old('type', $invoice->type) == 'bill' ? 'selected' : '' }}>فاتورة شراء (BILL)</option>
                                </select>
                                <small class="form-text text-muted">
                                    فاتورة البيع (INV): دخل للحساب الوسيط | فاتورة الشراء (BILL): مصروف للحساب الوسيط
                                </small>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                       id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
