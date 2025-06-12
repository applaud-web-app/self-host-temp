@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">My Subscription</h2>
        </div>

        @php
            $planName = "Pro Plan (Lifetime)";
            $purchaseDate = \Carbon\Carbon::parse('2025-05-01')->startOfDay();
            $features = [
                'Unlimited Access to All Features' => 'Enjoy full access to every tool and feature we offer.',
                'Priority Support' => 'Get fast responses from our dedicated support team.',
                'Lifetime Updates' => 'Receive all future updates with no extra cost.',
                'Access to Premium Tools' => 'Unlock powerful premium tools to enhance your workflow.',
                'Early Access to New Features' => 'Be among the first to use our newest features and updates.',
            ];
            $transactionId = 'TXN-8350921';
            $price = '$199.00';
            $paymentMethod = 'Credit Card (**** 1234)';
            $renewalPolicy = 'Non-renewable (Lifetime)';
            $purchaseLocation = 'Online Store';
            $supportEmail = 'support@example.com';
        @endphp

        <div class="row">
            <!-- Subscription Details Card -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Subscription Details</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Plan:</strong> {{ $planName }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Purchase Date:</strong> {{ $purchaseDate->format('d M Y') }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Status:</strong> Active (Lifetime Access)</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Transaction ID:</strong> {{ $transactionId }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Amount Paid:</strong> {{ $price }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Payment Method:</strong> {{ $paymentMethod }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Renewal Policy:</strong> {{ $renewalPolicy }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Purchase Location:</strong> {{ $purchaseLocation }}</li>
                            <li class="list-group-item d-flex justify-content-between px-0"><strong>Support Contact:</strong> <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Features & Benefits Card -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Features & Benefits</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($features as $feature => $description)
                                <li class="list-group-item px-0">
                                    <strong>{{ $feature }}</strong>
                                    <p class="mb-0 small text-muted">{{ $description }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
