<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Quote Created</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0E8C73; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; border: 1px solid #E5E7EB; border-top: none; border-radius: 0 0 8px 8px; }
        .details { background: #F9FAFB; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6B7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Quote Created</h1>
            <p>Quote #{{ $quote->quote_number ?? 'N/A' }}</p>
        </div>
        
        <div class="content">
            <p>Dear {{ $client->name ?? 'Valued Client' }},</p>
            
            <p>We are pleased to provide you with a new quote for our services.</p>
            
            <div class="details">
                <p><strong>Quote Number:</strong> {{ $quote->quote_number ?? 'N/A' }}</p>
                <p><strong>Date:</strong> {{ $quote->created_at ? $quote->created_at->format('F d, Y') : 'N/A' }}</p>
                <p><strong>Total Amount:</strong> KES {{ number_format($quote->total ?? 0, 2) }}</p>
            </div>
            
            <p>If you have any questions, please contact us.</p>
            
            <p>Best regards,<br><strong>YADA CRM Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} YADA CRM. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
