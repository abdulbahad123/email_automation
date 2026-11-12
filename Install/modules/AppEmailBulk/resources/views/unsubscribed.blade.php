<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Unsubscribed') }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 40px 16px; }
        .card { max-width: 480px; margin: 40px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; text-align: center; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05); }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { font-size: 14px; color: #475569; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('You have been unsubscribed') }}</h1>
        <p>
            @if(!empty($email))
                {{ __(':email has been removed from future bulk email campaigns.', ['email' => $email]) }}
            @else
                {{ __('Your email address has been removed from future bulk email campaigns.') }}
            @endif
        </p>
    </div>
</body>
</html>

