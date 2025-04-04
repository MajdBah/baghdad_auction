<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة #{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: 'Cairo', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            direction: rtl;
            font-size: 12px;
            color: #333;
            background-color: #fff;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .company-logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 24px;
            margin: 20px 0 10px;
            color: #333;
            font-weight: bold;
        }
        .invoice-details, .client-details {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .invoice-meta, .client-info {
            width: 48%;
        }
        .panel {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }
        .panel-heading {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .meta-item {
            margin-bottom: 5px;
            display: flex;
        }
        .meta-label {
            font-weight: bold;
            width: 40%;
        }
        .meta-value {
            width: 60%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            margin-right: 0;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 10px;
            text-align: right;
        }
        .totals-table .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
            border-top: 2px solid #ddd;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-danger { background-color: #dc3545; }
        .badge-info { background-color: #17a2b8; }
        .badge-secondary { background-color: #6c757d; }
        .badge-dark { background-color: #343a40; }

        .notes-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .payment-info {
            margin-top: 30px;
            padding: 15px;
            border: 1px dashed #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 11px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.1;
            font-size: 100px;
            font-weight: bold;
            z-index: -1;
            color: #000;
        }
        @media print {
            body {
                font-size: 10pt;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-after: always;
            }
        }
        .barcode {
            text-align: center;
            margin-top: 20px;
        }
        .car-info {
            margin-bottom: 20px;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            border-top: 1px solid #ddd;
            width: 45%;
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    @if($invoice->status == 'cancelled')
        <div class="watermark">ملغية</div>
    @elseif($invoice->status == 'draft')
        <div class="watermark">مسودة</div>
    @endif

    <div class="container">
        <!-- بداية ترويسة الفاتورة -->
        <div class="invoice-header">
            <img src="{{ asset('images/logo.png') }}" alt="شعار الشركة" class="company-logo" onerror="this.style.display='none'">
            <div class="company-name">شركة بغداد لشحن السيارات</div>
            <div class="company-info">بغداد، العراق | هاتف: 07700000000 | بريد إلكتروني: info@baghdad-shipping.com</div>
            <h1 class="invoice-title">
                @if($invoice->type == 'sale')
                    فاتورة بيع
                @elseif($invoice->type == 'purchase')
                    فاتورة شراء
                @elseif($invoice->type == 'shipping')
                    فاتورة شحن
                @endif
                #{{ $invoice->invoice_number }}
            </h1>
        </div>

        <!-- تفاصيل الفاتورة والعميل -->
        <div class="invoice-details">
            <div class="invoice-meta panel">
                <div class="panel-heading">تفاصيل الفاتورة</div>
                <div class="meta-item">
                    <div class="meta-label">رقم الفاتورة:</div>
                    <div class="meta-value">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">تاريخ الإصدار:</div>
                    <div class="meta-value">{{ $invoice->issue_date }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">تاريخ الاستحقاق:</div>
                    <div class="meta-value">{{ $invoice->due_date }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">حالة الفاتورة:</div>
                    <div class="meta-value">
                        @if($invoice->status == 'paid')
                            <span class="badge badge-success">مدفوعة</span>
                        @elseif($invoice->status == 'partially_paid')
                            <span class="badge badge-warning">مدفوعة جزئياً</span>
                        @elseif($invoice->status == 'overdue')
                            <span class="badge badge-danger">متأخرة</span>
                        @elseif($invoice->status == 'issued')
                            <span class="badge badge-info">صادرة</span>
                        @elseif($invoice->status == 'draft')
                            <span class="badge badge-secondary">مسودة</span>
                        @elseif($invoice->status == 'cancelled')
                            <span class="badge badge-dark">ملغية</span>
                        @endif
                    </div>
                </div>
                @if($invoice->reference_number)
                    <div class="meta-item">
                        <div class="meta-label">الرقم المرجعي:</div>
                        <div class="meta-value">{{ $invoice->reference_number }}</div>
                    </div>
                @endif
            </div>

            <div class="client-info panel">
                <div class="panel-heading">معلومات الحساب</div>
                <div class="meta-item">
                    <div class="meta-label">الاسم:</div>
                    <div class="meta-value">{{ $invoice->account->name }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">رقم الحساب:</div>
                    <div class="meta-value">{{ $invoice->account->account_number }}</div>
                </div>
                @if($invoice->account->phone)
                    <div class="meta-item">
                        <div class="meta-label">رقم الهاتف:</div>
                        <div class="meta-value">{{ $invoice->account->phone }}</div>
                    </div>
                @endif
                @if($invoice->account->email)
                    <div class="meta-item">
                        <div class="meta-label">البريد الإلكتروني:</div>
                        <div class="meta-value">{{ $invoice->account->email }}</div>
                    </div>
                @endif
                @if($invoice->account->address)
                    <div class="meta-item">
                        <div class="meta-label">العنوان:</div>
                        <div class="meta-value">
                            {{ $invoice->account->address }}
                            @if($invoice->account->city)
                                ، {{ $invoice->account->city }}
                            @endif
                            @if($invoice->account->country)
                                ، {{ $invoice->account->country }}
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- معلومات السيارة إذا وجدت -->
        @if($invoice->car)
            <div class="car-info panel">
                <div class="panel-heading">معلومات السيارة</div>
                <div style="display: flex; justify-content: space-between;">
                    <div style="width: 48%;">
                        <div class="meta-item">
                            <div class="meta-label">الماركة/الموديل:</div>
                            <div class="meta-value">{{ $invoice->car->make }} {{ $invoice->car->model }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">سنة الصنع:</div>
                            <div class="meta-value">{{ $invoice->car->year }}</div>
                        </div>
                    </div>
                    <div style="width: 48%;">
                        <div class="meta-item">
                            <div class="meta-label">رقم الهيكل (VIN):</div>
                            <div class="meta-value">{{ $invoice->car->vin }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">اللون:</div>
                            <div class="meta-value">{{ $invoice->car->color ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- جدول بنود الفاتورة -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">الوصف</th>
                    <th style="width: 10%;">الكمية</th>
                    <th style="width: 20%;">سعر الوحدة</th>
                    <th style="width: 20%;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 2) }} $</td>
                        <td>{{ number_format($item->total, 2) }} $</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- جدول الإجماليات -->
        <table class="totals-table">
            <tr>
                <td><strong>المجموع الفرعي:</strong></td>
                <td>{{ number_format($invoice->subtotal, 2) }} $</td>
            </tr>
            @if($invoice->discount > 0)
                <tr>
                    <td><strong>الخصم:</strong></td>
                    <td>{{ number_format($invoice->discount, 2) }} $</td>
                </tr>
            @endif
            @if($invoice->tax > 0)
                <tr>
                    <td><strong>الضريبة:</strong></td>
                    <td>{{ number_format($invoice->tax, 2) }} $</td>
                </tr>
            @endif
            @if($invoice->shipping_fee > 0)
                <tr>
                    <td><strong>رسوم الشحن:</strong></td>
                    <td>{{ number_format($invoice->shipping_fee, 2) }} $</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>الإجمالي:</strong></td>
                <td><strong>{{ number_format($invoice->total_amount, 2) }} $</strong></td>
            </tr>
        </table>

        <!-- معلومات الدفع -->
        <div class="payment-info">
            <div class="panel-heading">معلومات الدفع</div>
            <div class="meta-item">
                <div class="meta-label">المبلغ المدفوع:</div>
                <div class="meta-value">{{ number_format($invoice->paid_amount, 2) }} $</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">المبلغ المتبقي:</div>
                <div class="meta-value">{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }} $</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">طرق الدفع:</div>
                <div class="meta-value">
                    تحويل بنكي: بنك بغداد - رقم الحساب: 1234567890<br>
                    الدفع المباشر: مقر الشركة
                </div>
            </div>
        </div>

        <!-- الملاحظات -->
        @if($invoice->notes)
            <div class="notes-section">
                <div class="panel-heading">ملاحظات</div>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- قسم التوقيعات -->
        <div class="signature-section">
            <div class="signature-box">
                <p>توقيع المستلم</p>
            </div>
            <div class="signature-box">
                <p>توقيع المصدر</p>
            </div>
        </div>

        <!-- باركود -->
        <div class="barcode">
            <svg id="invoice-barcode"></svg>
        </div>

        <!-- تذييل الصفحة -->
        <div class="footer">
            <p>{{ $invoice->invoice_number }} - تم إصدار هذه الفاتورة بواسطة نظام شركة بغداد لشحن السيارات</p>
            <p>جميع الحقوق محفوظة &copy; {{ date('Y') }} شركة بغداد لشحن السيارات</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin: 20px;">
        <button onclick="window.print();" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            طباعة الفاتورة
        </button>
        <button onclick="window.close();" style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            إغلاق
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            JsBarcode("#invoice-barcode", "{{ $invoice->invoice_number }}", {
                format: "CODE128",
                width: 2,
                height: 50,
                displayValue: true,
                text: "فاتورة رقم: {{ $invoice->invoice_number }}"
            });
        });
    </script>
</body>
</html>
