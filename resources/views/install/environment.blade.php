@extends('layouts.single-master')
@section('title', 'Environment Check | Aplu')

@section('content')
<style>
    .env-card {
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .env-heading {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2d3748;
    }

    .env-subtitle {
        font-size: 1.1rem;
        color: #4a5568;
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .checklist-item {
        background-color: #e6fffa;
        border-left: 5px solid #38b2ac;
        padding: 10px 15px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .checklist-item i {
        color: #38b2ac;
        margin-right: 10px;
    }

    .checklist-item span {
        color: #4a5568;
    }

    .folder-requirements {
        margin-top: 2rem;
    }

    .folder-requirements h4 {
        margin-bottom: 1rem;
        text-align: left;
        color: #2d3748;
        font-weight: 600;
    }

    .btn-continue {
        margin-top: 2rem;
    }
    .check { color: #38b2ac !important; }
    .fail  { color: #e53e3e !important; }
    .btn-disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .requirements-error {
        color: #e53e3e;
        margin-top: 1rem;
        text-align: center;
        font-weight: 500;
    }
</style>


<section class="section-padding">
  <div class="container">
    <div class="env-card card">
      <h1 class="env-heading">🔍 Environment Check</h1>
      <p class="env-subtitle">Let's make sure your environment meets all requirements.</p>
      
      <div class="row">
        @foreach(array_chunk($requirements, ceil(count($requirements)/2), true) as $column)
          <div class="col-md-6">
            @foreach($column as $label => $ok)
              <div class="checklist-item">
                <i class="fas fa-{{ $ok ? 'check' : 'times' }} {{ $ok ? 'check' : 'fail' }}"></i>
                <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>

      <div class="folder-requirements">
        <h4>Folder Requirements</h4>
        <div class="row">
          @foreach($folders as $path => $writable)
            <div class="col-md-6">
              <div class="checklist-item">
                <i class="fas fa-{{ $writable ? 'check' : 'times' }} {{ $writable ? 'check' : 'fail' }}"></i>
                <span>{{ $path }} is {{ $writable ? 'writable' : 'not writable' }}.</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      @if(!$requirements)
        <div class="requirements-error">
          <i class="fas fa-exclamation-circle me-2"></i>
          Please fix all requirements before continuing.
        </div>
      @endif

      <form method="POST" action="{{ route('install.environment.post') }}" id="environmentForm">
        @csrf
        <button type="submit" id="continueBtn"
                class="btn btn-primary w-100 btn-continue {{ !$requirements ? 'btn-disabled' : '' }}" 
                {{ !$requirements ? 'disabled' : '' }}>
          Continue Setup
        </button>
      </form>
    </div>
  </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('environmentForm');
        const continueBtn = document.getElementById('continueBtn');
        
        form.addEventListener('submit', function(e) {
            @if(!$requirements)
                e.preventDefault();
                alert('Please fix all requirements before continuing.');
                return;
            @endif
            
            // Only proceed with loading state if all requirements are met
            if (continueBtn && !continueBtn.classList.contains('btn-disabled')) {
                e.preventDefault();
                
                // Add loading state
                continueBtn.classList.add('btn-loading');
                continueBtn.innerHTML = `
                    <div class="spinner-border spinner-border-sm text-light spinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span>Processing...</span>
                `;
                
                // Submit the form after a brief delay to show the loading state
                setTimeout(() => {
                    form.submit();
                }, 300);
            }
        });
    });
</script>
@endsection