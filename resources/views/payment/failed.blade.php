<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.payment_failed') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card card-block card-stretch">
                    <div class="card-body text-center p-5">
                        <!-- Failed Icon -->
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Failed Title -->
                        <h2 class="mb-3 text-danger">{{ __('messages.payment_failed') }}</h2>

                        <!-- Failed Message -->
                        <p class="mb-4 text-muted">{{ $message ?? __('messages.your_payment_could_not_be_processed') }}</p>

                        <!-- No Details Available -->
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle"></i> {{ __('messages.payment_processing_failed') }}
                        </div>
                
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .text-danger {
            color: #dc3545 !important;
        }
        .border-danger {
            border-color: #dc3545 !important;
        }
    </style>
</body>
</html>
