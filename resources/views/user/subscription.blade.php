{{-- resources/views/user/subscription.blade.php --}}
@extends('layouts.master')

@section('content')
<section class="content-body" id="subscription_page">
  <div class="container-fluid">

    {{-- Header Section --}}
  
      <div class="d-flex justify-content-between align-items-center text-head mb-3">
        <h1 class="h3">
          My Subscription
        </h1>
        @if(isset($sub['status']))
          <span class="badge bg-{{ $sub['status'] === 'paid' ? 'success' : 'warning' }} p-2 px-3 rounded-pill">
            <i class="fas fa-{{ $sub['status'] === 'paid' ? 'check-circle' : 'exclamation-circle' }} me-1"></i>
            {{ ucfirst($sub['status']) }}
          </span>
        @endif
      </div>
     


    {{-- Display Errors --}}
    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <strong>Please fix these issues:</strong>
        <ul class="mb-0 ">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @isset($sub)
      {{-- Main Subscription Card --}}
      <div class="card h-auto">
     
        <div class="card-body">
          <div class="row align-items-center">

            {{-- Plan Information --}}
            <div class="col-md-12">
              <div class="subscription-plan  ">
                <div class="d-flex align-items-center mb-3">
                  <div class="bg-primary text-white rounded-circle p-3 me-3">
                    <i class="fas fa-crown fa-2x"></i>
                  </div>
                  <div>
                    <h4 class="h5 mb-1">{{ $sub['name'] }}</h4>
                    <span class="badge bg-primary">Version {{ $sub['version'] }}</span>
                  </div>
                </div>
               
              </div>
            </div>
            <hr>
            {{-- Purchase Details --}}
            <div class="col-md-12">
              <div class="subscription-details ">
                <div class="d-flex justify-content-between mb-3">
                  <span class="detail-label">Purchased On:</span>
                  <span class="detail-value">
                    {{ $sub['purchase_date']->format('F j, Y') }}
                    <small class="text-muted">({{ $sub['purchase_date']->diffForHumans() }})</small>
                  </span>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                  <span class="detail-label">Amount Paid:</span>
                  <span class="detail-value text-success fw-bold">{{ $sub['paid_amount'] }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                  <span class="detail-label">Order ID:</span>
                  <span class="detail-value ">{{ $sub['order_id'] }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                  <span class="detail-label">Payment ID:</span>
                  <span class="detail-value ">{{ $sub['payment_id'] }}</span>
                </div>
                
                <div class="d-flex justify-content-between">
                  <span class="detail-label">Renewal Policy:</span>
                  <span class="detail-value">
                    {{ $sub['status'] === 'paid' ? 'Non-renewable (Lifetime access)' : 'N/A' }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      {{-- Support Card --}}
      <div class="card h-auto">
        <div class="card-header ">
          <h3 class="h5 mb-0">
            <i class="fas fa-headset text-primary me-2"></i>
            Customer Support
          </h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
              <div class="support-option ">
                <h4 class="h6 mb-3"><i class="fas fa-envelope text-primary me-2"></i> Email Support</h4>
                <p class="mb-2">For any questions or issues with your subscription</p>
                <a href="mailto:{{ $sub['supprot_email'] }}" class="text-primary ">
                  {{ $sub['supprot_email'] }}
                </a>
              </div>
            </div>
            <div class="col-md-6">
              <div class="support-option ">
                <h4 class="h6 mb-3"><i class="fas fa-phone text-primary me-2"></i> Phone Support</h4>
                <p class="mb-2">Available during business hours (9AM-5PM)</p>
                <a href="tel:{{ $sub['supprot_mobile'] }}" class="text-primary ">
                   {{ $sub['supprot_mobile'] }}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

  

    @else
      {{-- No Subscription State --}}
      <div class="empty-state text-center py-5">
        <div class="empty-state-icon -primary rounded-circle p-4 mb-3 mx-auto">
          <i class="fas fa-id-card-alt fa-2x text-primary"></i>
        </div>
        <h3 class="h4 mb-3">No Active Subscription</h3>
        <p class="text-muted mb-4">You don't have an active subscription at the moment.</p>
        <a href="/pricing" class="btn btn-primary px-4">
          <i class="fas fa-crown me-2"></i> View Plans
        </a>
      </div>
    @endisset

  </div>
</section>

<style>


</style>
@endsection