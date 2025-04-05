<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'type',
        'account_id',
        'from_account_id',
        'to_account_id',
        'direction',
        'car_id',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax_rate',
        'tax_amount',
        'shipping_fee',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'notes',
        'reference_number',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the account associated with the invoice
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the source account associated with the invoice
     */
    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * Get the destination account associated with the invoice
     */
    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Get the car associated with the invoice
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the user who created the invoice
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the items for this invoice
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for this invoice
     */
    public function payments()
    {
        return $this->hasMany(Transaction::class, 'invoice_id');
    }

    /**
     * Scope a query to only include invoices of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include invoices with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereIn('status', ['issued', 'partially_paid']);
    }

    /**
     * Check if the invoice is fully paid
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the invoice is partially paid
     */
    public function isPartiallyPaid()
    {
        return $this->status === 'partially_paid';
    }

    /**
     * Check if the invoice is overdue
     */
    public function isOverdue()
    {
        return $this->due_date < now() && !$this->isPaid();
    }

    /**
     * Check if the invoice is positive for the broker account
     * (من حساب العميل إلى حساب الوسيط)
     */
    public function isPositiveForBroker()
    {
        return $this->direction === 'positive';
    }

    /**
     * Check if the invoice is negative for the broker account
     * (من حساب الوسيط إلى حساب شركة الشحن)
     */
    public function isNegativeForBroker()
    {
        return $this->direction === 'negative';
    }

    /**
     * Record a payment for this invoice
     */
    public function recordPayment($amount)
    {
        $this->paid_amount = (float)$this->paid_amount + (float)$amount;
        $this->balance = (float)$this->total_amount - (float)$this->paid_amount;

        if ($this->balance <= 0) {
            $this->status = 'paid';
            $this->balance = 0; // لضمان عدم وجود قيم سالبة
        } else {
            $this->status = 'partially_paid';
        }

        // تسجيل البيانات التي سيتم حفظها للتشخيص
        \Log::info('تسجيل دفعة للفاتورة #' . $this->invoice_number, [
            'invoice_id' => $this->id,
            'amount_paid' => $amount,
            'new_paid_amount' => $this->paid_amount,
            'new_balance' => $this->balance,
            'new_status' => $this->status
        ]);

        $saved = $this->save();

        if (!$saved) {
            \Log::error('فشل في حفظ دفعة للفاتورة #' . $this->invoice_number);
        } else {
            // إعادة تحميل البيانات من قاعدة البيانات للتأكد من تحديثها
            $this->refresh();
            \Log::info('تم حفظ دفعة للفاتورة #' . $this->invoice_number . ' بنجاح', [
                'invoice_id' => $this->id,
                'current_paid_amount' => $this->paid_amount,
                'current_balance' => $this->balance,
                'current_status' => $this->status
            ]);
        }

        return $saved;
    }

    /**
     * Distribute payment automatically among unpaid invoices, starting from oldest
     *
     * @param int|Account $account Account ID or Account model
     * @param float $totalAmount Total amount to be distributed
     * @return array Array containing payment information and affected invoices
     */
    public static function distributePayment($account, float $totalAmount)
    {
        // Get account ID if an Account object was passed
        $accountId = $account instanceof Account ? $account->id : $account;

        // Get all unpaid invoices for this account, ordered by issue_date (oldest first)
        $unpaidInvoices = self::where('account_id', $accountId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('balance', '>', 0)
            ->orderBy('issue_date', 'asc')
            ->get();

        if ($unpaidInvoices->isEmpty()) {
            return [
                'status' => 'error',
                'message' => 'لا توجد فواتير غير مدفوعة لهذا الحساب',
                'remaining_amount' => $totalAmount,
                'affected_invoices' => []
            ];
        }

        $remainingAmount = $totalAmount;
        $affectedInvoices = [];
        $transaction = null;

        // Begin database transaction to ensure all operations complete successfully
        \DB::beginTransaction();

        try {
            foreach ($unpaidInvoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                // Calculate amount to apply to this invoice
                $amountToApply = min($remainingAmount, $invoice->balance);

                // Record the payment
                $invoice->recordPayment($amountToApply);

                // Create transaction record for this payment
                $transaction = Transaction::create([
                    'account_id' => $accountId,
                    'invoice_id' => $invoice->id,
                    'type' => 'payment',
                    'amount' => $amountToApply,
                    'date' => now(),
                    'description' => 'دفعة تلقائية من الدفعة الإجمالية ' . $totalAmount,
                    'status' => 'completed'
                ]);

                $remainingAmount -= $amountToApply;

                $affectedInvoices[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'paid_amount' => $amountToApply,
                    'new_balance' => $invoice->balance,
                    'status' => $invoice->status
                ];
            }

            // If there's remaining amount, create a credit transaction
            if ($remainingAmount > 0) {
                Transaction::create([
                    'account_id' => $accountId,
                    'type' => 'credit',
                    'amount' => $remainingAmount,
                    'date' => now(),
                    'description' => 'رصيد متبقي من الدفعة الإجمالية ' . $totalAmount,
                    'status' => 'completed'
                ]);
            }

            \DB::commit();

            return [
                'status' => 'success',
                'message' => 'تم توزيع الدفعة بنجاح',
                'total_paid' => $totalAmount - $remainingAmount,
                'remaining_credit' => $remainingAmount,
                'affected_invoices' => $affectedInvoices
            ];

        } catch (\Exception $e) {
            \DB::rollBack();

            return [
                'status' => 'error',
                'message' => 'حدث خطأ أثناء معالجة الدفعة: ' . $e->getMessage(),
                'remaining_amount' => $totalAmount,
                'affected_invoices' => []
            ];
        }
    }

    /**
     * Distribute payment automatically among unpaid invoices, starting from oldest
     *
     * @param int|Account $account Account ID or Account model
     * @param float $totalAmount Total amount to be distributed
     * @param array $paymentData Additional payment data (method, reference, etc.)
     * @param User $user User who processed the payment
     * @return array Array containing payment information and affected invoices
     */
    public static function distributePaymentToOldestInvoices($account, float $totalAmount, array $paymentData = [], $user = null)
    {
        // Get account ID if an Account object was passed
        $accountId = $account instanceof Account ? $account->id : $account;

        \Log::info('بدء توزيع الدفعة التلقائي', [
            'account_id' => $accountId,
            'total_amount' => $totalAmount,
            'payment_data' => $paymentData
        ]);

        // Get all unpaid invoices for this account, ordered by issue_date (oldest first)
        $unpaidInvoices = self::where('account_id', $accountId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('balance', '>', 0)
            ->orderBy('issue_date', 'asc')
            ->get();

        if ($unpaidInvoices->isEmpty()) {
            \Log::warning('لا توجد فواتير غير مدفوعة للحساب', [
                'account_id' => $accountId
            ]);

            return [
                'status' => 'error',
                'message' => 'لا توجد فواتير غير مدفوعة لهذا الحساب',
                'remaining_amount' => $totalAmount,
                'paid_invoices' => []
            ];
        }

        $remainingAmount = $totalAmount;
        $paidInvoices = [];
        $transactions = [];

        // Begin database transaction to ensure all operations complete successfully
        \DB::beginTransaction();

        try {
            foreach ($unpaidInvoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                // Calculate amount to apply to this invoice
                $amountToApply = min($remainingAmount, (float)$invoice->balance);

                \Log::info('تسجيل دفعة للفاتورة', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $amountToApply,
                    'before_paid_amount' => $invoice->paid_amount,
                    'before_balance' => $invoice->balance
                ]);

                // Record the payment
                $invoice->recordPayment($amountToApply);

                // Generate a unique transaction number
                $transactionNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

                // Create transaction record for this payment
                $description = $paymentData['description'] ?? 'دفعة تلقائية من الدفعة الإجمالية ' . $totalAmount;

                $transactionData = [
                    'transaction_number' => $transactionNumber,
                    'type' => 'payment',
                    'to_account_id' => $accountId,  // الحساب المستلم للدفعة
                    'invoice_id' => $invoice->id,
                    'amount' => $amountToApply,
                    'transaction_date' => $paymentData['date'] ?? now(),
                    'description' => $description,
                    'payment_method' => $paymentData['payment_method'] ?? null,
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'status' => 'completed'
                ];

                // Add user ID if provided
                if ($user) {
                    $transactionData['created_by'] = $user->id;
                } else {
                    $transactionData['created_by'] = 1; // استخدام المستخدم الافتراضي
                }

                \Log::info('إنشاء سجل معاملة', $transactionData);

                $transaction = Transaction::create($transactionData);
                $transactions[] = $transaction;

                \Log::info('تم إنشاء سجل معاملة', [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'invoice_id' => $invoice->id,
                    'amount' => $amountToApply
                ]);

                $remainingAmount -= $amountToApply;

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
                    'status' => 'completed'
                ];

                // Add user ID if provided
                if ($user) {
                    $creditData['created_by'] = $user->id;
                } else {
                    $creditData['created_by'] = 1; // استخدام المستخدم الافتراضي
                }

                \Log::info('إنشاء سجل رصيد متبقي', $creditData);

                $creditTransaction = Transaction::create($creditData);
                $transactions[] = $creditTransaction;

                \Log::info('تم إنشاء سجل رصيد متبقي', [
                    'transaction_id' => $creditTransaction->id,
                    'transaction_number' => $creditTransaction->transaction_number,
                    'amount' => $remainingAmount
                ]);
            }

            \DB::commit();

            \Log::info('اكتملت عملية توزيع الدفعة بنجاح', [
                'total_amount' => $totalAmount,
                'total_paid' => $totalAmount - $remainingAmount,
                'remaining_amount' => $remainingAmount,
                'paid_invoices_count' => count($paidInvoices)
            ]);

            return [
                'status' => 'success',
                'message' => 'تم توزيع الدفعة بنجاح',
                'total_paid' => $totalAmount - $remainingAmount,
                'remaining_amount' => $remainingAmount,
                'paid_invoices' => $paidInvoices,
                'transactions' => $transactions
            ];

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('فشل في توزيع الدفعة', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'حدث خطأ أثناء معالجة الدفعة: ' . $e->getMessage(),
                'remaining_amount' => $totalAmount,
                'paid_invoices' => []
            ];
        }
    }

    /**
     * إنشاء فاتورة بين حسابين مع تحديد الاتجاه بالنسبة للوسيط
     *
     * @param array $data بيانات الفاتورة
     * @param int|Account $fromAccount الحساب المصدر
     * @param int|Account $toAccount الحساب الوجهة
     * @param bool $isPositiveForBroker هل الفاتورة موجبة للوسيط؟
     * @param User|null $user المستخدم الذي أنشأ الفاتورة
     * @return Invoice الفاتورة التي تم إنشاؤها
     */
    public static function createBetweenAccounts(array $data, $fromAccount, $toAccount, bool $isPositiveForBroker, $user = null)
    {
        // تحويل حسابات المصدر والوجهة إلى معرفات إذا تم تمريرها كنماذج
        $fromAccountId = $fromAccount instanceof Account ? $fromAccount->id : $fromAccount;
        $toAccountId = $toAccount instanceof Account ? $toAccount->id : $toAccount;

        // تحديد نوع الفاتورة بناءً على الاتجاه
        $invoiceType = $isPositiveForBroker ? 'income' : 'expense';

        // تجهيز البيانات الأساسية للفاتورة
        $invoiceData = array_merge($data, [
            'type' => $invoiceType,
            'from_account_id' => $fromAccountId,
            'to_account_id' => $toAccountId,
            'direction' => $isPositiveForBroker ? 'positive' : 'negative',
            'status' => 'issued',
            'created_by' => $user ? $user->id : 1, // استخدام المستخدم الافتراضي إذا لم يتم تمرير مستخدم
        ]);

        // حساب الموازنة - لضمان عدم وجود قيم سالبة
        if (!isset($invoiceData['paid_amount'])) {
            $invoiceData['paid_amount'] = 0;
        }

        if (!isset($invoiceData['balance'])) {
            $invoiceData['balance'] = $invoiceData['total_amount'];
        }

        \Log::info('إنشاء فاتورة بين حسابين', [
            'from_account_id' => $fromAccountId,
            'to_account_id' => $toAccountId,
            'direction' => $isPositiveForBroker ? 'positive' : 'negative',
            'type' => $invoiceType,
        ]);

        // إنشاء الفاتورة
        return self::create($invoiceData);
    }

    /**
     * الحصول على الفواتير المتعلقة بحساب الوسيط
     *
     * @param int|Account $brokerAccount حساب الوسيط
     * @param string|null $direction اتجاه الفواتير (موجب/سالب/الكل)
     * @return \Illuminate\Database\Eloquent\Collection مجموعة الفواتير
     */
    public static function getBrokerInvoices($brokerAccount, $direction = null)
    {
        $brokerAccountId = $brokerAccount instanceof Account ? $brokerAccount->id : $brokerAccount;

        $query = self::where(function($q) use ($brokerAccountId) {
            $q->where('from_account_id', $brokerAccountId)
              ->orWhere('to_account_id', $brokerAccountId);
        });

        if ($direction === 'positive') {
            $query->where('direction', 'positive');
        } elseif ($direction === 'negative') {
            $query->where('direction', 'negative');
        }

        return $query->orderBy('issue_date', 'desc')->get();
    }

    /**
     * الحصول على رصيد حساب الوسيط من الفواتير (الإيرادات - المصروفات)
     *
     * @param int|Account $brokerAccount حساب الوسيط
     * @return float رصيد الوسيط
     */
    public static function calculateBrokerBalance($brokerAccount)
    {
        $brokerAccountId = $brokerAccount instanceof Account ? $brokerAccount->id : $brokerAccount;

        // حساب مجموع الفواتير الموجبة (من العميل إلى الوسيط)
        $positiveTotal = self::where(function($q) use ($brokerAccountId) {
                $q->where('from_account_id', $brokerAccountId)
                  ->orWhere('to_account_id', $brokerAccountId);
            })
            ->where('direction', 'positive')
            ->sum('total_amount');

        // حساب مجموع الفواتير السالبة (من الوسيط إلى شركة الشحن)
        $negativeTotal = self::where(function($q) use ($brokerAccountId) {
                $q->where('from_account_id', $brokerAccountId)
                  ->orWhere('to_account_id', $brokerAccountId);
            })
            ->where('direction', 'negative')
            ->sum('total_amount');

        // حساب الرصيد الإجمالي (الإيرادات - المصروفات)
        return $positiveTotal - $negativeTotal;
    }
}
