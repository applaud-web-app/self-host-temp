@extends('layouts.master')
@push('styles')
    <style>
    .addon-card:hover { border-color: var(--primary); }
    .addon-icon-bg {
         width: 64px;
    height: 64px;
    display: flex;
    padding: 5px;
    overflow: hidden;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
    border-radius: 50%;
    border: 1px solid var(--primary);
      
    }
    .addon-card .addon-badge {
            position: absolute;
    top: 10px;
    right: 10px;
    font-size: 12px;
    }
    .addon-title { font-size: 1.18rem; font-weight: 600; margin-bottom: .35rem; }
    .addon-desc { font-size: .95rem; color: #666; min-height: 32px; }
  
</style>
@endpush

@section('content')
<section class="content-body">
   <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Addons & Modules</h2>

         <a href="{{ route('addons.upload') }}" class="btn btn-primary"> <i class="fas fa-plus pe-2"></i>Upload Module</a>

        </div>

   
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="row gy-4">
      @isset($addons)
        @foreach($addons as $addon)
          <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card addon-card  position-relative">
              <span class="badge addon-badge {{ $addon['status'] === 'available' ? 'bg-secondary' : 'bg-success' }}">
                {{ ucfirst($addon['status']) }}
              </span>
              <div class="card-body text-center d-flex flex-column">
                <div class="addon-icon-bg mb-3 mt-3">
                  <img src="{{ $addon['icon'] }}" alt="{{ $addon['name'] }} icon" class="img-fluid" >
                </div>
                <h5 class="addon-title">{{ $addon['name'] }} <small class="text-secondary">({{ $addon['version'] }})</small></h5>
                <p class="addon-desc mb-3">{{ $addon['description'] ?: 'No description available.' }}</p>
                <div class="fw-bold fs-4 text-primary mb-3">{{ $addon['price'] }}</div>
                @isset($addon['purchase_url'])
                  <div class="addon-actions">
                      <a href="{{$addon['purchase_url']}}" target="_blank" class="btn btn-primary btn-sm w-100">
                      <i class="fas fa-shopping-cart me-1"></i> {{$addon['btn_text']}}
                      </a>
                  </div>
                @endisset
              </div>
            </div>
          </div>
        @endforeach
      @endisset
    </div>

  </div>
</section>
@endsection
