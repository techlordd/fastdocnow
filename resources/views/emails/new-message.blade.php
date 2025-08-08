<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Message Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .header {
            background-color: #4a5568; /* A neutral dark color */
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
            line-height: 1.6;
        }
        .content h1 {
            font-size: 22px;
            color: #2d3748;
        }
        .message-quote {
            background-color: #f7fafc;
            border-left: 4px solid #718096;
            padding: 15px;
            margin: 20px 0;
            font-style: italic;
        }
        .button-container {
            text-align: center;
            margin-top: 30px;
        }
        .button {
            background-color: #3490dc; /* A nice blue */
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .footer {
            background-color: #edf2f7;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
        }
        .footer a {
            color: #3490dc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Replace 'logo.png' with the path to your actual logo -->
            <img src="{{ asset('images/logo-placeholder.png') }}" alt="Your Logo">
            <h2>New Message Received</h2>
        </div>
        <div class="content">
            <h1>Hi {{ $emailMessage->conversation->participants()->where('user_id', '!=', $emailSender->id)->first()->name }},</h1>
            <p>You have received a new message from <strong>{{ $emailSender->name }}</strong> in the conversation at FastDocNow.com".</p>
            <div class="message-quote">
                <p>"{{ Str::limit($emailMessage->content, 150) }}"</p>
            </div>

            <p>You can view the full message by clicking the button below:</p>

            <div class="button-container">
                <a href="{{ url('/chat/' . $emailMessage->conversation_id) }}" class="button">View Conversation</a>
            </div>
        </div>
        <div class="footer">
            <p>You are receiving this email because you have notifications enabled for this conversation.</p>
            <p><a href="{{ url('/profile') }}">Manage your notification settings</a>.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>