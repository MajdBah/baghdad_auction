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
        $request->validate([
            'type' => 'required|in:invoice,bill',
            'account_id' => 'required|exists:accounts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.item_type' => 'required|string',
            'car_id' => 'nullable|exists:cars,id',
            'status' => 'required|in:draft,issued',
        ]);

        DB::beginTransaction();

        try {
            // Calculate invoice totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += ($item['quantity'] * $item['unit_price']);
            }

            $discount = $request->discount ?? 0;
            $taxRate = $request->tax_rate ?? 0;
            $taxAmount = ($taxRate / 100) * ($subtotal - $discount);
            $shippingFee = $request->shipping_fee ?? 0;
            $totalAmount = $subtotal - $discount + $taxAmount + $shippingFee;

            // Create invoice number
            $prefix = $request->type === 'invoice' ? 'INV' : 'BILL';
            $invoiceNumber = $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            // Create the invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'type' => $request->type,
                'account_id' => $request->account_id,
                'car_id' => $request->car_id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance' => $totalAmount,
                'status' => $request->status,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Create invoice items
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];

                // Only assign car_id if the item_type is related to a car service
                $carId = null;
                if (isset($item['item_type']) && in_array($item['item_type'], ['car_service', 'car_part', 'car_repair']) && $request->car_id) {
                    $carId = $request->car_id;
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'car_id' => $carId,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $itemTotal,
                    'item_type' => isset($item['item_type']) ? $item['item_type'] : 'general',
                ]);
            }

            // Create a transaction if selected and invoice is issued
            if ($request->has('create_transaction') && $request->status === 'issued') {
                $transactionNumber = 'TRX-' . strtoupper(Str::random(8));

                // Find intermediary account
                $intermediaryAccount = Account::where('type', 'intermediary')->first();

                if (!$intermediaryAccount) {
                    throw new \Exception('حساب وسيط غير موجود. يرجى إنشاء حساب وسيط قبل إنشاء فواتير مع معاملات تلقائية.');
                }

                // Determine transaction direction based on invoice type
                if ($request->type === 'invoice') {
                    // Sales invoice: from customer to intermediary
                    $fromAccountId = $request->account_id;
                    $toAccountId = $intermediaryAccount->id;
                } else {
                    // Purchase invoice: from intermediary to supplier
                    $fromAccountId = $intermediaryAccount->id;
                    $toAccountId = $request->account_id;
                }

                // Create the transaction
                $transaction = Transaction::create([
                    'transaction_number' => $transactionNumber,
                    'type' => $request->type === 'invoice' ? 'sale' : 'purchase',
                    'from_account_id' => $fromAccountId,
                    'to_account_id' => $toAccountId,
                    'car_id' => $request->car_id,
                    'amount' => $totalAmount,
                    'commission_amount' => 0,
                    'with_commission' => false,
                    'reference_number' => $invoiceNumber,
                    'transaction_date' => $request->issue_date,
                    'description' => 'Transaction for ' . $invoiceNumber,
                    'status' => 'completed',
                    'created_by' => Auth::id(),
                ]);

                // Update account balances
                $fromAccount = Account::findOrFail($fromAccountId);
                $fromAccount->balance -= $totalAmount;
                $fromAccount->save();

                $toAccount = Account::findOrFail($toAccountId);
                $toAccount->balance += $totalAmount;
                $toAccount->save();
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', ($request->type === 'invoice' ? 'فاتورة البيع' : 'فاتورة الشراء') . ' تم إنشاؤها بنجاح');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Invoice creation error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'فشل في إنشاء الفاتورة: ' . $e->getMessage())->withInput();
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

        DB::beginTransaction();

        try {
            // Create a transaction for the payment
            $transactionNumber = 'PAY-' . strtoupper(Str::random(8));

            // Determine accounts based on invoice type
            if ($invoice->type === 'invoice') {
                // For customer invoices, money comes from customer to intermediary
                $fromAccountId = $invoice->account_id;
                $toAccountId = Account::where('type', 'intermediary')->first()->id;
            } else {
                // For bills, money goes from intermediary to shipping company
                $fromAccountId = Account::where('type', 'intermediary')->first()->id;
                $toAccountId = $invoice->account_id;
            }

            // Create the transaction
            Transaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'payment',
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'amount' => $request->amount,
                'commission_amount' => 0,
                'with_commission' => false,
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->payment_date,
                'description' => 'Payment for ' . $invoice->invoice_number,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            // Update account balances
            $fromAccount = Account::findOrFail($fromAccountId);
            $fromAccount->balance -= $request->amount;
            $fromAccount->save();

            $toAccount = Account::findOrFail($toAccountId);
            $toAccount->balance += $request->amount;
            $toAccount->save();

            // Update invoice
            $invoice->paid_amount += $request->amount;
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;

            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();

            DB::commit();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Payment recorded successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage())->withInput();
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

        // Generate PDF (integration with a PDF library would be needed here)
        // For demonstration purposes, we'll just return to the invoice view
        return view('invoices.pdf', compact('invoice'));
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
