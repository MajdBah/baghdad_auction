<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Show form for automatic payment distribution
     */
    public function showAutoPaymentForm($accountId = null)
    {
        $accounts = Account::all();
        $selectedAccount = null;
        $unpaidInvoices = collect();

        if ($accountId) {
            $selectedAccount = Account::findOrFail($accountId);
            $unpaidInvoices = Invoice::where('account_id', $accountId)
                ->whereIn('status', ['issued', 'partially_paid'])
                ->where('balance', '>', 0)
                ->orderBy('issue_date', 'asc')
                ->get();
        }

        return view('payments.auto_payment', compact('accounts', 'selectedAccount', 'unpaidInvoices'));
    }

    /**
     * Process automatic payment distribution
     */
    public function processAutoPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'distribution_method' => 'required|in:auto,manual',
            'selected_invoices' => 'required_if:distribution_method,manual|array',
            'selected_invoices.*' => 'exists:invoices,id',
            'payment_amounts' => 'required_if:distribution_method,manual|array',
            'payment_amounts.*' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Get the account
        $account = Account::findOrFail($request->account_id);

        // Get payment date (or use today)
        $paymentDate = $request->payment_date ? Carbon::parse($request->payment_date) : now();

        // Start transaction
        DB::beginTransaction();

        try {
            // Prepare payment data
            $paymentData = [
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'description' => $request->description ?: 'دفعة تلقائية',
                'date' => $paymentDate,
            ];

            $result = [];

            // Process based on distribution method
            if ($request->distribution_method === 'auto') {
                // Distribute payment to invoices automatically from oldest to newest
                $result = Invoice::distributePaymentToOldestInvoices(
                    $account,
                    $request->amount,
                    $paymentData,
                    auth()->user()
                );
            } else {
                // Manual distribution
                $result = $this->processManualDistribution(
                    $account,
                    $request->amount,
                    $request->selected_invoices,
                    $request->payment_amounts,
                    $paymentData
                );
            }

            // If everything was successful, commit transaction
            DB::commit();

            // Prepare success message
            $message = 'تم توزيع الدفعة بنجاح على ' . count($result['paid_invoices']) . ' فاتورة.';

            if ($result['remaining_amount'] > 0) {
                $message .= ' تبقى مبلغ ' . number_format($result['remaining_amount'], 2) . ' بدون استخدام.';
            }

            return redirect()->route('accounts.show', $account->id)
                ->with('success', $message)
                ->with('payment_result', $result);

        } catch (\Exception $e) {
            // If something goes wrong, rollback transaction
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفعة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process manual payment distribution
     */
    private function processManualDistribution($account, $totalAmount, $selectedInvoices, $paymentAmounts, $paymentData)
    {
        // Get account ID if an Account object was passed
        $accountId = $account instanceof Account ? $account->id : $account;

        $paidInvoices = [];
        $transactions = [];
        $totalPaid = 0;

        // Check if we have selected invoices
        if (empty($selectedInvoices)) {
            return [
                'status' => 'error',
                'message' => 'لم يتم تحديد أي فواتير للدفع',
                'remaining_amount' => $totalAmount,
                'paid_invoices' => []
            ];
        }

        \Log::info('بدء معالجة الدفع اليدوي', [
            'account_id' => $accountId,
            'total_amount' => $totalAmount,
            'selected_invoices' => $selectedInvoices,
            'payment_amounts' => $paymentAmounts
        ]);

        // Process each selected invoice
        foreach ($selectedInvoices as $invoiceId) {
            // Get the invoice
            $invoice = Invoice::findOrFail($invoiceId);

            // Skip if invoice doesn't belong to the account
            if ($invoice->account_id != $accountId) {
                \Log::warning('تم تخطي فاتورة لأنها لا تنتمي للحساب المحدد', [
                    'invoice_id' => $invoiceId,
                    'account_id' => $accountId,
                    'invoice_account_id' => $invoice->account_id
                ]);
                continue;
            }

            // Get payment amount for this invoice
            $amountToApply = isset($paymentAmounts[$invoiceId]) ? floatval($paymentAmounts[$invoiceId]) : 0;

            // Skip if no payment is being made
            if ($amountToApply <= 0) {
                \Log::info('تم تخطي فاتورة لأن المبلغ المخصص صفر', [
                    'invoice_id' => $invoiceId,
                    'amount' => $amountToApply
                ]);
                continue;
            }

            // Ensure amount doesn't exceed balance
            $originalAmountToApply = $amountToApply;
            $amountToApply = min($amountToApply, (float)$invoice->balance);

            if ($originalAmountToApply != $amountToApply) {
                \Log::warning('تم تعديل مبلغ الدفع ليتوافق مع رصيد الفاتورة', [
                    'invoice_id' => $invoiceId,
                    'original_amount' => $originalAmountToApply,
                    'adjusted_amount' => $amountToApply,
                    'invoice_balance' => $invoice->balance
                ]);
            }

            \Log::info('تسجيل دفعة للفاتورة', [
                'invoice_id' => $invoiceId,
                'amount' => $amountToApply,
                'before_paid_amount' => $invoice->paid_amount,
                'before_balance' => $invoice->balance
            ]);

            // Record the payment on the invoice
            $invoice->recordPayment($amountToApply);

            // Generate a unique transaction number
            $transactionNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Create transaction record for this payment
            $transactionData = [
                'transaction_number' => $transactionNumber,
                'type' => 'payment',
                'to_account_id' => $accountId,  // الحساب المستلم للدفعة
                'invoice_id' => $invoice->id,
                'amount' => $amountToApply,
                'transaction_date' => $paymentData['date'] ?? now(),
                'description' => $paymentData['description'] ?? 'دفعة يدوية',
                'reference_number' => $paymentData['reference_number'] ?? null,
                'payment_method' => $paymentData['payment_method'] ?? null,
                'status' => 'completed',
                'created_by' => auth()->user() ? auth()->user()->id : 1 // استخدام المستخدم الحالي أو المستخدم الافتراضي
            ];

            \Log::info('إنشاء سجل معاملة', $transactionData);

            $transaction = Transaction::create($transactionData);
            $transactions[] = $transaction;

            \Log::info('تم إنشاء سجل معاملة', [
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'invoice_id' => $invoice->id,
                'amount' => $amountToApply
            ]);

            $totalPaid += $amountToApply;

            $paidInvoices[] = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'paid_amount' => $amountToApply,
                'new_balance' => $invoice->balance,
                'status' => $invoice->status,
                'transaction_id' => $transaction->id
            ];

            \Log::info('اكتملت معالجة الفاتورة', [
                'invoice_id' => $invoice->id,
                'after_paid_amount' => $invoice->paid_amount,
                'after_balance' => $invoice->balance,
                'after_status' => $invoice->status
            ]);
        }

        // Calculate remaining amount
        $remainingAmount = $totalAmount - $totalPaid;

        \Log::info('اكتملت معالجة جميع الفواتير', [
            'total_paid' => $totalPaid,
            'remaining_amount' => $remainingAmount,
            'invoices_count' => count($paidInvoices)
        ]);

        // If there's remaining amount, create a credit transaction
        if ($remainingAmount > 0) {
            // Generate a unique transaction number for credit
            $creditTransactionNumber = 'CRED-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $creditData = [
                'transaction_number' => $creditTransactionNumber,
                'type' => 'credit',
                'to_account_id' => $accountId,
                'amount' => $remainingAmount,
                'transaction_date' => $paymentData['date'] ?? now(),
                'description' => 'رصيد متبقي من الدفعة الإجمالية ' . $totalAmount,
                'payment_method' => $paymentData['payment_method'] ?? null,
                'reference_number' => $paymentData['reference_number'] ?? null,
                'status' => 'completed',
                'created_by' => auth()->user() ? auth()->user()->id : 1
            ];

            \Log::info('إنشاء سجل رصيد متبقي', $creditData);

            $creditTransaction = Transaction::create($creditData);
            $transactions[] = $creditTransaction;

            \Log::info('تم إنشاء سجل رصيد متبقي', [
                'transaction_id' => $creditTransaction->id,
                'transaction_number' => $creditTransaction->transaction_number,
                'amount' => $remainingAmount
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'تم توزيع الدفعة بنجاح',
            'total_paid' => $totalPaid,
            'remaining_amount' => $remainingAmount,
            'paid_invoices' => $paidInvoices,
            'transactions' => $transactions
        ];
    }

    /**
     * Get unpaid invoices for an account (for AJAX request)
     */
    public function getUnpaidInvoices($accountId)
    {
        $unpaidInvoices = Invoice::where('account_id', $accountId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('balance', '>', 0)
            ->orderBy('issue_date', 'asc')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'issue_date' => $invoice->issue_date->format('Y-m-d'),
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance' => $invoice->balance,
                    'status' => $invoice->status,
                    'is_overdue' => $invoice->isOverdue(),
                ];
            });

        return response()->json($unpaidInvoices);
    }
}
