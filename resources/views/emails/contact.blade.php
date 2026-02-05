<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Contact Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 10px 10px;
        }
        .field {
            margin-bottom: 20px;
        }
        .label {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .value {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3b82f6;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">New Contact Form Submission</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">HiFastLink Website</p>
    </div>
    
    <div class="content">
        <div class="field">
            <div class="label">Name:</div>
            <div class="value">{{ $contactData['name'] }}</div>
        </div>

        <div class="field">
            <div class="label">Email:</div>
            <div class="value">
                <a href="mailto:{{ $contactData['email'] }}" style="color: #1e40af; text-decoration: none;">
                    {{ $contactData['email'] }}
                </a>
            </div>
        </div>

        @if(!empty($contactData['phone']))
        <div class="field">
            <div class="label">Phone:</div>
            <div class="value">{{ $contactData['phone'] }}</div>
        </div>
        @endif

        <div class="field">
            <div class="label">Subject:</div>
            <div class="value">{{ $contactData['subject'] }}</div>
        </div>

        <div class="field">
            <div class="label">Message:</div>
            <div class="value" style="white-space: pre-wrap;">{{ $contactData['message'] }}</div>
        </div>
    </div>

    <div class="footer">
        <p>This email was sent from the HiFastLink contact form</p>
        <p>{{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
