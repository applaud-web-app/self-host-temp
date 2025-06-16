@extends('layouts.master')


@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        {{-- Page Heading --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-4">
            <div class="d-flex align-items-center gap-2">
                <h2 class="mb-0">My Subscription</h2>
            </div>
            <span class="badge light badge-success border border-success fs-6 px-3 py-2">
                Active&nbsp;&middot;&nbsp;Lifetime Access
            </span>
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
            $transactionId   = 'TXN-8350921';
            $price           = '$199.00';
            $paymentMethod   = 'Credit Card (**** 1234)';
            $renewalPolicy   = 'Non‑renewable (Lifetime)';
            $purchaseLocation= 'Online Store';
            $supportEmail    = 'support@example.com';

            // Prepare detail rows with Font Awesome icons
            $details = [
                ['label' => 'Plan',             'value' => $planName,          'icon' => 'fas fa-gem'],
                ['label' => 'Purchase Date',    'value' => $purchaseDate->format('d M Y') . ' ('. $purchaseDate->diffForHumans(null, true). ' ago)', 'icon' => 'fas fa-calendar-check'],
                ['label' => 'Transaction ID',   'value' => $transactionId,      'icon' => 'fas fa-receipt'],
                ['label' => 'Amount Paid',      'value' => $price,             'icon' => 'fas fa-dollar-sign'],
                ['label' => 'Payment Method',   'value' => $paymentMethod,      'icon' => 'fas fa-credit-card'],
                ['label' => 'Renewal Policy',   'value' => $renewalPolicy,      'icon' => 'fas fa-redo'],
                ['label' => 'Purchase Location','value' => $purchaseLocation,   'icon' => 'fas fa-store'],
            ];
        @endphp

        <div class="row g-4">
            {{-- Subscription Details --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i> Subscription Details</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($details as $detail)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="{{ $detail['icon'] }} me-2 text-muted"></i>
                                        <strong>{{ $detail['label'] }}:</strong>
                                    </div>
                                    <span class="text-end">{{ $detail['value'] }}</span>
                                </li>
                            @endforeach
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-life-ring me-2 text-muted"></i>
                                    <strong>Support Contact:</strong>
                                </div>
                                <a href="mailto:{{ $supportEmail }}" class="text-decoration-none">{{ $supportEmail }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Features & Benefits --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-stars me-2 text-primary"></i> Features &amp; Benefits</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($features as $feature => $description)
                                <li class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-check-circle me-2 mt-1"></i>
                                        <div>
                                            <strong>{{ $feature }}</strong>
                                            <p class="mb-0 small text-muted">{{ $description }}</p>
                                        </div>
                                    </div>
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
