<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');

        $query = Invoice::with('account');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->paginate(15);

        return view('invoices.index', compact('invoices', 'type', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $cars = Car::whereIn('status', ['purchased', 'shipped', 'delivered'])->get();

        return view('invoices.create', compact('accounts', 'cars'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('بدء عملية حفظ الفاتورة', ['request_data' => $request->except(['_token'])]);

        try {
            $validated = $request->validate([
                'type' => 'required|in:invoice,bill',
                'from_account_id' => 'required|exists:accounts,id',
                'account_id' => 'required|exists:accounts,id',
                'due_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'status' => 'required|in:draft,issued',
            ]);

            \Log::info('تم التحقق من صحة البيانات بنجاح');

            // حساب المجموع الفرعي وإجمالي الفاتورة
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discount = $request->discount ?: 0;
            $tax = $request->tax ?: 0;
            $shipping_fee = $request->shipping_fee ?: 0;
            $total_amount = $subtotal - $discount + $tax + $shipping_fee;
            $balance = $total_amount; // في البداية، الرصيد يساوي إجمالي المبلغ

            // تحديد من/إلى حسابات وفقًا للنوع
            $fromAccountId = $request->from_account_id;
            $toAccountId = $request->account_id;

            \Log::info('معلومات الحسابات', [
                'fromAccountId' => $fromAccountId,
                'toAccountId' => $toAccountId
            ]);

            // تحديد اتجاه الفاتورة بالنسبة للحساب الوسيط
            $direction = null;
            if ($request->type == 'invoice') {
                // فاتورة مبيعات (INV) - دخل للحساب الوسيط
                $direction = 'positive';
            } else if ($request->type == 'bill') {
                // فاتورة مشتريات (BILL) - مصروف للحساب الوسيط
                $direction = 'negative';
            }

            // توليد رقم الفاتورة
            $prefix = $request->type == 'invoice' ? 'INV-' : 'BILL-';
            $date = date('Ymd');
            $random = strtoupper(substr(uniqid(), -4));
            $invoice_number = $prefix . $date . '-' . $random;

            \Log::info('تفاصيل الفاتورة قبل الحفظ', [
                'invoice_number' => $invoice_number,
                'type' => $request->type,
                'direction' => $direction,
                'subtotal' => $subtotal,
                'total_amount' => $total_amount
            ]);

            try {
                DB::beginTransaction();

                // إنشاء الفاتورة بالطريقة المباشرة
                $invoice = new Invoice();
                $invoice->invoice_number = $invoice_number;
                $invoice->type = $request->type;
                $invoice->account_id = $toAccountId;
                $invoice->from_account_id = $fromAccountId;
                $invoice->to_account_id = $toAccountId;
                $invoice->direction = $direction;
                $invoice->car_id = $request->car_id;
                $invoice->issue_date = now();
                $invoice->due_date = $request->due_date;
                $invoice->subtotal = $subtotal;
                $invoice->discount = $discount;
                $invoice->tax_rate = 0; // حقل مطلوب في قاعدة البيانات
                $invoice->tax_amount = $tax;
                $invoice->shipping_fee = $shipping_fee;
                $invoice->total_amount = $total_amount;
                $invoice->paid_amount = 0;
                $invoice->balance = $balance;
                $invoice->status = $request->status;
                $invoice->reference_number = $request->reference_number;
                $invoice->notes = $request->notes;
                $invoice->created_by = auth()->id();

                \Log::info('محاولة حفظ الفاتورة');
                $saved = $invoice->save();

                if (!$saved) {
                    \Log::error('فشل في حفظ الفاتورة بدون استثناء');
                    throw new \Exception('فشل في حفظ الفاتورة بدون استثناء');
                }

                \Log::info('تم إنشاء الفاتورة بنجاح', ['invoice_id' => $invoice->id]);

                // إضافة بنود الفاتورة
                foreach ($request->items as $key => $item) {
                    $invoiceItem = new InvoiceItem();
                    $invoiceItem->invoice_id = $invoice->id;
                    $invoiceItem->description = $item['description'];
                    $invoiceItem->quantity = $item['quantity'];
                    $invoiceItem->unit_price = $item['unit_price'];
                    $invoiceItem->total = $item['quantity'] * $item['unit_price'];
                    $invoiceItem->item_type = $item['item_type'] ?? 'standard';

                    $invoiceItem->save();

                    \Log::info('تم إنشاء بند الفاتورة', ['item_id' => $invoiceItem->id]);
                }

                // إنشاء معاملة مالية وتحديث أرصدة الحسابات إذا تم تحديد ذلك أو كانت الفاتورة مصدرة
                if ($request->status === 'issued') {
                    // الحصول على حساب الوسيط
                    $intermediaryAccount = Account::where('type', 'intermediary')->first();
                    if (!$intermediaryAccount) {
                        throw new \Exception('لم يتم العثور على حساب الوسيط');
                    }

                    $transactionData = [
                        'transaction_number' => 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                        'type' => $direction === 'positive' ? 'income' : 'expense',
                        'from_account_id' => $fromAccountId,
                        'to_account_id' => $toAccountId,
                        'invoice_id' => $invoice->id,
                        'amount' => $total_amount,
                        'transaction_date' => now(),
                        'description' => 'معاملة تلقائية من الفاتورة رقم ' . $invoice_number,
                        'status' => 'completed',
                        'created_by' => auth()->id()
                    ];

                    \Log::info('إنشاء معاملة مالية', $transactionData);
                    $transaction = Transaction::create($transactionData);
                    \Log::info('تم إنشاء المعاملة المالية', ['transaction_id' => $transaction->id]);

                    // تحديث أرصدة الحسابات
                    if ($request->type == 'invoice') {
                        // فاتورة مبيعات: خصم من حساب العميل (المصدر)
                        $fromAccount = Account::findOrFail($fromAccountId);
                        $fromAccountOldBalance = $fromAccount->balance;
                        $fromAccount->balance -= $total_amount;
                        $fromAccount->save();

                        \Log::info('تم تحديث رصيد حساب العميل', [
                            'account_id' => $fromAccount->id,
                            'account_name' => $fromAccount->name,
                            'old_balance' => $fromAccountOldBalance,
                            'new_balance' => $fromAccount->balance,
                            'operation' => 'خصم',
                            'amount' => $total_amount
                        ]);
                    } else {
                        // فاتورة مشتريات: خصم من حساب الوسيط
                        $intermediaryOldBalance = $intermediaryAccount->balance;
                        $intermediaryAccount->balance -= $total_amount;
                        $intermediaryAccount->save();

                        \Log::info('تم تحديث رصيد حساب الوسيط', [
                            'account_id' => $intermediaryAccount->id,
                            'account_name' => $intermediaryAccount->name,
                            'old_balance' => $intermediaryOldBalance,
                            'new_balance' => $intermediaryAccount->balance,
                            'operation' => 'خصم',
                            'amount' => $total_amount
                        ]);
                    }
                }

                DB::commit();
                \Log::info('تم حفظ الفاتورة والالتزام بالمعاملة بنجاح');

                // إرسال الفاتورة بالبريد الإلكتروني إذا تم تحديد ذلك
                if ($request->has('send_email') && $request->send_email && $request->status === 'issued') {
                    // هنا يمكن إضافة كود إرسال البريد الإلكتروني
                    \Log::info('طلب إرسال بريد إلكتروني للفاتورة', ['invoice_id' => $invoice->id]);
                }

                return redirect()->route('invoices.show', $invoice)
                    ->with('success', 'تم إنشاء الفاتورة بنجاح');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('خطأ أثناء حفظ الفاتورة', [
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);

                return back()->withInput()
                    ->with('error', 'حدث خطأ أثناء إنشاء الفاتورة: ' . $e->getMessage());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('خطأ في التحقق من صحة البيانات', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('خطأ عام أثناء معالجة طلب الفاتورة', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()
                ->with('error', 'حدث خطأ عام أثناء معالجة الفاتورة: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['account', 'items.car', 'createdBy']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        // Only allow editing draft and issued invoices
        if (!in_array($invoice->status, ['draft', 'issued'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot edit invoices that have been paid or cancelled');
        }

        $invoice->load('items.car');
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $cars = Car::whereIn('status', ['purchased', 'shipped', 'delivered', 'sold'])->get();

        return view('invoices.edit', compact('invoice', 'accounts', 'cars'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        // Only allow updating draft and issued invoices
        if (!in_array($invoice->status, ['draft', 'issued'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot update invoices that have been paid or cancelled');
        }

        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.car_id' => 'nullable|exists:cars,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.item_type' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // Get values with proper defaults
            $totalDiscount = $request->input('discount', 0) ?: 0;
            $shippingFee = $request->input('shipping_fee', 0) ?: 0;
            $invoiceTaxRate = $request->input('tax_rate', 0) ?: 0;

            // Calculate invoice totals
            $subtotal = 0;
            $taxAmount = 0;
            $totalAmount = 0;

            // Delete existing items not in the request
            $existingItemIds = collect($request->items)
                ->pluck('id')
                ->filter()
                ->toArray();

            $invoice->items()
                ->whereNotIn('id', $existingItemIds)
                ->delete();

            // Update or create items
            foreach ($request->items as $item) {
                $itemSubtotal = ($item['quantity'] * $item['unit_price']);
                $itemDiscount = isset($item['discount']) && is_numeric($item['discount']) ? $item['discount'] : 0;
                $itemSubtotalAfterDiscount = $itemSubtotal - $itemDiscount;
                $itemTaxRate = isset($item['tax_rate']) && is_numeric($item['tax_rate']) ? $item['tax_rate'] : $invoiceTaxRate;
                $itemTaxAmount = $itemSubtotalAfterDiscount * ($itemTaxRate / 100);
                $itemTotal = $itemSubtotalAfterDiscount + $itemTaxAmount;

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTaxAmount;
                $totalAmount += $itemTotal;

                $itemData = [
                    'car_id' => $item['car_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'tax_rate' => $itemTaxRate,
                    'tax_amount' => $itemTaxAmount,
                    'total' => $itemTotal,
                    'item_type' => $item['item_type'],
                ];

                // Update or create the item
                if (!empty($item['id'])) {
                    // Update existing item
                    InvoiceItem::find($item['id'])?->update($itemData);
                } else {
                    // Create new item
                    $itemData['invoice_id'] = $invoice->id;
                    InvoiceItem::create($itemData);
                }
            }

            // Update final total amount with additional discount and shipping
            $finalSubtotal = $subtotal - $totalDiscount;
            $finalTotalAmount = $finalSubtotal + $taxAmount + $shippingFee;
            $balance = $finalTotalAmount - ($invoice->paid_amount ?: 0);

            // Update invoice
            $invoice->update([
                'account_id' => $request->account_id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'tax_rate' => $invoiceTaxRate,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $finalTotalAmount,
                'balance' => $balance,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', ($invoice->type === 'invoice' ? 'فاتورة البيع' : 'فاتورة الشراء') . ' تم تحديثها بنجاح');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'حدث خطأ أثناء حفظ الفاتورة. الرجاء المحاولة مرة أخرى. ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        // Only allow deleting draft and issued invoices
        if (!in_array($invoice->status, ['draft', 'issued'])) {
            return back()->with('error', 'Cannot delete invoices that have been paid or cancelled');
        }

        DB::beginTransaction();

        try {
            // Delete all invoice items
            $invoice->items()->delete();

            // Delete the invoice
            $invoice->delete();

            DB::commit();

            return redirect()->route('invoices.index')
                ->with('success', ($invoice->type === 'invoice' ? 'Invoice' : 'Bill') . ' deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to delete ' . ($invoice->type === 'invoice' ? 'invoice' : 'bill') . ': ' . $e->getMessage());
        }
    }

    /**
     * Show form for recording a payment
     */
    public function showPaymentForm(Invoice $invoice)
    {
        // Only allow payments for issued or partially paid invoices
        if (!in_array($invoice->status, ['issued', 'partially_paid'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot record payment for invoices that are fully paid or cancelled');
        }

        // Calculate the remaining amount
        $remainingAmount = $invoice->balance;

        // Get all active accounts for the dropdown
        $accounts = Account::where('is_active', true)->orderBy('name')->get();

        return view('invoices.payment', compact('invoice', 'remainingAmount', 'accounts'));
    }

    /**
     * Record a payment for an invoice
     */
    public function recordPayment(Request $request, Invoice $invoice)
    {
        // Only allow payments for issued or partially paid invoices
        if (!in_array($invoice->status, ['issued', 'partially_paid'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot record payment for invoices that are fully paid or cancelled');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance,
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        \Log::info('بدء تسجيل دفعة للفاتورة', [
            'invoice_id' => $invoice->id,
            'invoice_type' => $invoice->type,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $request->amount,
            'invoice_account_id' => $invoice->account_id,
            'invoice_from_account_id' => $invoice->from_account_id,
            'invoice_to_account_id' => $invoice->to_account_id
        ]);

        $invoice->load(['account', 'fromAccount', 'toAccount']);

        DB::beginTransaction();

        try {
            // Create a transaction for the payment
            $transactionNumber = 'PAY-' . strtoupper(Str::random(8));

            // الحصول على حساب الوسيط
            $intermediaryAccount = Account::where('type', 'intermediary')->first();
            if (!$intermediaryAccount) {
                throw new \Exception('لم يتم العثور على حساب الوسيط');
            }

            \Log::info('معلومات حساب الوسيط', [
                'intermediary_id' => $intermediaryAccount->id,
                'intermediary_name' => $intermediaryAccount->name
            ]);

            // Determine accounts based on invoice type
            if ($invoice->type === 'invoice') {
                // For customer invoices (INV), money comes from customer to intermediary
                $fromAccountId = $invoice->from_account_id; // حساب العميل (from_account_id)
                $toAccountId = $intermediaryAccount->id; // حساب الوسيط

                \Log::info('دفعة فاتورة مبيعات (INV) - من العميل إلى الوسيط', [
                    'from_account_id' => $fromAccountId,
                    'from_account_name' => $invoice->fromAccount ? $invoice->fromAccount->name : 'غير محدد',
                    'to_account_id' => $toAccountId,
                    'to_account_name' => $intermediaryAccount->name
                ]);
            } else {
                // For bills (BILL), money goes from intermediary to shipping company
                $fromAccountId = $intermediaryAccount->id; // حساب الوسيط
                $toAccountId = $invoice->to_account_id; // حساب شركة الشحن أو المورد (to_account_id)

                \Log::info('دفعة فاتورة مشتريات (BILL) - من الوسيط إلى المورد', [
                    'from_account_id' => $fromAccountId,
                    'from_account_name' => $intermediaryAccount->name,
                    'to_account_id' => $toAccountId,
                    'to_account_name' => $invoice->toAccount ? $invoice->toAccount->name : 'غير محدد'
                ]);
            }

            // Create the transaction
            $transaction = Transaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'payment',
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'amount' => $request->amount,
                'commission_amount' => 0,
                'with_commission' => false,
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->payment_date,
                'description' => 'دفعة للفاتورة رقم ' . $invoice->invoice_number,
                'status' => 'completed',
                'created_by' => Auth::id(),
                'invoice_id' => $invoice->id
            ]);

            \Log::info('تم إنشاء سجل المعاملة المالية', [
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number
            ]);

            // Update account balances - تحديث أرصدة الحسابات
            $fromAccount = Account::findOrFail($fromAccountId);
            $fromAccountOldBalance = $fromAccount->balance;
            $fromAccount->balance += $request->amount; // زيادة رصيد الحساب الدافع (تسوية الدين)
            $fromAccount->save();

            \Log::info('تم تحديث رصيد حساب المصدر (الدافع)', [
                'account_id' => $fromAccount->id,
                'account_name' => $fromAccount->name,
                'old_balance' => $fromAccountOldBalance,
                'new_balance' => $fromAccount->balance,
                'operation' => 'زيادة', 
                'amount' => $request->amount
            ]);

            $toAccount = Account::findOrFail($toAccountId);
            $toAccountOldBalance = $toAccount->balance;
            $toAccount->balance += $request->amount; // زيادة رصيد الحساب المستلم (يستلم المبلغ)
            $toAccount->save();

            \Log::info('تم تحديث رصيد حساب الوجهة (المستلم)', [
                'account_id' => $toAccount->id,
                'account_name' => $toAccount->name,
                'old_balance' => $toAccountOldBalance,
                'new_balance' => $toAccount->balance,
                'operation' => 'زيادة',
                'amount' => $request->amount
            ]);

            // Update invoice
            $oldPaidAmount = $invoice->paid_amount;
            $oldBalance = $invoice->balance;
            $oldStatus = $invoice->status;

            $invoice->paid_amount += $request->amount;
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;

            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();

            \Log::info('تم تحديث بيانات الفاتورة', [
                'invoice_id' => $invoice->id,
                'old_paid_amount' => $oldPaidAmount,
                'new_paid_amount' => $invoice->paid_amount,
                'old_balance' => $oldBalance,
                'new_balance' => $invoice->balance,
                'old_status' => $oldStatus,
                'new_status' => $invoice->status
            ]);

            DB::commit();
            \Log::info('تم تسجيل الدفعة بنجاح');

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'تم تسجيل الدفعة بنجاح');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('فشل في تسجيل الدفعة', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'فشل في تسجيل الدفعة: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Cancel an invoice
     */
    public function cancel(Invoice $invoice)
    {
        // Only allow cancelling draft and issued invoices
        if (!in_array($invoice->status, ['draft', 'issued'])) {
            return back()->with('error', 'Cannot cancel invoices that have been paid');
        }

        $invoice->update(['status' => 'cancelled']);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', ($invoice->type === 'invoice' ? 'Invoice' : 'Bill') . ' cancelled successfully');
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['account', 'items.car', 'createdBy']);

        // Use the print view template since it's already formatted for printing
        return view('invoices.print', compact('invoice'));
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Invoice $invoice)
    {
        // Email functionality would be implemented here
        // For demonstration purposes, we'll just return a success message

        return redirect()->route('invoices.show', $invoice)
            ->with('success', ($invoice->type === 'invoice' ? 'Invoice' : 'Bill') . ' sent via email successfully');
    }

    /**
     * عرض البنود المرتبطة بفاتورة معينة (للتشخيص)
     */
    public function showItems(Invoice $invoice)
    {
        $invoice->load('items');
        return response()->json([
            'invoice_number' => $invoice->invoice_number,
            'items_count' => $invoice->items->count(),
            'items' => $invoice->items
        ]);
    }

    /**
     * Print the invoice in a printer-friendly format
     */
    public function printInvoice(Invoice $invoice)
    {
        $invoice->load(['account', 'items.car', 'createdBy']);

        return view('invoices.print', compact('invoice'));
    }
}
