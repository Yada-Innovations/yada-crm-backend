<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Reminder</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            border-bottom: 2px solid #7F77DD;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 20px;
            font-weight: 700;
            color: #7F77DD;
        }
        .badge {
            display: inline-block;
            background: #FFF3CD;
            color: #856404;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .badge-urgent {
            background: #F8D7DA;
            color: #721C24;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 8px 0;
        }
        p {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        .invoice-details {
            background: #f8f8f8;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 8px 0;
            font-size: 14px;
            color: #444;
        }
        .invoice-details td:first-child {
            color: #888;
            width: 140px;
        }
        .invoice-details .total {
            font-size: 18px;
            font-weight: 700;
            color: #7F77DD;
            border-top: 2px solid #ddd;
            padding-top: 12px;
            margin-top: 4px;
        }
        .btn {
            display: inline-block;
            background: #7F77DD;
            color: #fff;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
        }
        .btn:hover {
            background: #6a62c4;
        }
        .footer {
            font-size: 12px;
            color: #aaa;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
        }
        .status-paid {
            color: #28a745;
            font-weight: 600;
        }
        .status-overdue {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">YADA CRM</div>
        </div>

        <div class="badge {{ $daysLeft <= 7 ? 'badge-urgent' : '' }}">
            ⏰ {{ $daysLeft <= 7 ? 'URGENT' : 'Reminder' }} - Due in {{ $daysLeft }} days
        </div>

        <h1>Invoice Payment Reminder</h1>

        <p>Hi {{ $invoice->client->name }},</p>

        <p>This is a reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> is due for payment in <strong>{{ $daysLeft }} days</strong>.</p>

        <div class="invoice-details">
            <table>
                <tr>
                    <td>Invoice Number</td>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                </tr>
                <tr>
                    <td>Amount Due</td>
                    <td><strong>KES {{ number_format($invoice->total, 2) }}</strong></td>
                </tr>
                <tr>
                    <td>Due Date</td>
                    <td><strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('F j, Y') }}</strong></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td class="{{ $invoice->status === 'paid' ? 'status-paid' : ($invoice->status === 'overdue' ? 'status-overdue' : '') }}">
                        {{ ucfirst($invoice->status) }}
                    </td>
                </tr>
                <tr class="total">
                    <td>Total</td>
                    <td>KES {{ number_format($invoice->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <p>To avoid any late fees or service interruption, please make payment before the due date.</p>

        <div style="text-align: center;">
            <a href="http://localhost:3000/invoices" class="btn">View Invoice</a>
        </div>

        <p style="margin-top: 16px; font-size: 13px; color: #888;">
            If you have already made this payment, please disregard this message.
        </p>

        <div class="footer">
            This is an automated reminder from YADA CRM. &copy; {{ date('Y') }} Yada Innovations.<br>
            For questions, contact us at support@yadacrm.com
        </div>
    </div>
</body>
</html>