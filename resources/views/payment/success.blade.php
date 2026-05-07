<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.payment_successful') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        .success-title {
            color: #28a745;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .success-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .payment-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <!-- Success Icon -->
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <!-- Success Title -->
        <h1 class="success-title">{{ __('messages.payment_successful') }}</h1>


        <!-- Simple Thank You Message -->
        <p class="text-muted">{{ __('messages.thank_you_for_your_payment') }}</p>
    </div>
</body>
</html>
