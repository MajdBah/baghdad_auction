<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrokerInvoiceController extends Controller
{
    /**
     * عرض صفحة فواتير الوسيط
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // البحث عن حسابات من نوع وسيط
        $brokerAccounts = Account::where('type', 'broker')->get();

        return view('broker.invoices.index', [
            'brokerAccounts' => $brokerAccounts
        ]);
    }

    /**
     * عرض صفحة إنشاء فاتورة جديدة من/إلى حساب الوسيط
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // الحصول على حسابات الوسطاء
        $brokerAccounts = Account::where('type', 'broker')->get();

        // الحصول على حسابات الشركات والعملاء
        $clientAccounts = Account::where('type', 'client')->get();
        $shippingAccounts = Account::where('type', 'shipping')->get();

        // تجميع كل الحسابات (غير الوسطاء) لاستخدامها في حقل "الحساب الآخر"
        $otherAccounts = Account::whereNotIn('type', ['broker'])->get();

        return view('broker.invoices.create', [
            'brokerAccounts' => $brokerAccounts,
            'clientAccounts' => $clientAccounts,
            'shippingAccounts' => $shippingAccounts,
            'otherAccounts' => $otherAccounts,
        ]);
    }

    /**
     * حفظ الفاتورة الجديدة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // التحقق من صحة البيانات
        $validated = $request->validate([
            'invoice_number' => 'required|unique:invoices,invoice_number',
            'broker_account_id' => 'required|exists:accounts,id',
            'other_account_id' => 'required|exists:accounts,id|different:broker_account_id',
            'direction' => 'required|in:positive,negative',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'shipping_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // تحديد الحساب المصدر والوجهة بناءً على اتجاه الفاتورة
            $brokerAccount = Account::findOrFail($request->broker_account_id);
            $otherAccount = Account::findOrFail($request->other_account_id);

            $isPositive = $request->direction === 'positive';

            // تحديد الحساب المصدر والوجهة بناءً على الاتجاه
            if ($isPositive) {
                // من العميل إلى الوسيط (موجب للوسيط)
                $fromAccount = $otherAccount;
                $toAccount = $brokerAccount;
            } else {
                // من الوسيط إلى شركة الشحن (سالب للوسيط)
                $fromAccount = $brokerAccount;
                $toAccount = $otherAccount;
            }

            // حساب الضريبة والإجمالي
            $subtotal = (float)$request->subtotal;
            $taxRate = (float)($request->tax_rate ?? 0);
            $taxAmount = $subtotal * ($taxRate / 100);
            $discount = (float)($request->discount ?? 0);
            $shippingFee = (float)($request->shipping_fee ?? 0);
            $totalAmount = $subtotal + $taxAmount + $shippingFee - $discount;

            // إعداد بيانات الفاتورة
            $invoiceData = [
                'invoice_number' => $request->invoice_number,
                'account_id' => $brokerAccount->id, // حقل موروث للتوافق مع النظام القديم
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance' => $totalAmount,
                'status' => 'issued',
                'notes' => $request->notes
            ];

            // إنشاء الفاتورة باستخدام الطريقة الجديدة
            $invoice = Invoice::createBetweenAccounts(
                $invoiceData,
                $fromAccount,
                $toAccount,
                $isPositive,
                Auth::user()
            );

            DB::commit();

            return redirect()->route('broker.invoices.show', $invoice->id)
                ->with('success', 'تم إنشاء الفاتورة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل في إنشاء فاتورة الوسيط: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الفاتورة: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل فاتورة محددة
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $invoice = Invoice::with(['fromAccount', 'toAccount', 'items', 'payments'])
            ->findOrFail($id);

        return view('broker.invoices.show', [
            'invoice' => $invoice
        ]);
    }

    /**
     * عرض جميع الفواتير المتعلقة بحساب وسيط محدد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function brokerInvoices(Request $request)
    {
        $brokerAccountId = $request->broker_account_id;
        $direction = $request->direction; // 'positive', 'negative', أو null لعرض الكل

        $brokerAccounts = Account::where('type', 'broker')->get();
        $selectedAccount = null;
        $invoices = collect(); // مجموعة فارغة افتراضياً
        $balance = 0;

        if ($brokerAccountId) {
            $selectedAccount = Account::findOrFail($brokerAccountId);
            $invoices = Invoice::getBrokerInvoices($selectedAccount, $direction);
            $balance = Invoice::calculateBrokerBalance($selectedAccount);
        }

        return view('broker.invoices.list', [
            'brokerAccounts' => $brokerAccounts,
            'selectedAccount' => $selectedAccount,
            'invoices' => $invoices,
            'balance' => $balance,
            'direction' => $direction
        ]);
    }
}
