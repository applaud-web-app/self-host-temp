@extends('layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
<style>
  .content-body { padding-top: 1rem; }
  .card { border-radius: .75rem; }
  .preview-box { position: sticky; top: 1rem; }
  .required-asterisk::after { content: " *"; color: #dc3545; margin-left: 2px; }
  .invalid-feedback { display: block; }
  .counter { font-variant-numeric: tabular-nums; }
  .preview-img { width: 100%; height: auto; border-radius: .5rem; border: 1px solid #e9ecef; }
  .btn-processing { pointer-events: none; opacity: 0.6; }
  /* make select2 look like a normal form-control when invalid */
  .select2-container--default .select2-selection--single.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + .75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='5'/%3e%3cline x1='6' y1='3' x2='6' y2='6'/%3e%3ccircle cx='6' cy='8.5' r='0.5' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(.375em + .1875rem) center;
    background-size: calc(.75em + .375rem) calc(.75em + .375rem);
  }
</style>
@endpush

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2 class="me-auto mb-0">Create Short Link</h2>
    </div>

    <div class="row g-3">
      <!-- LEFT: FORM -->
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-body">
            <form id="ytPushForm" method="POST" action="{{ route('url_shortener.link.store') }}" novalidate>
              @csrf

              <!-- Link URL -->
              <div class="mb-4">
                <label for="url" class="form-label required-asterisk">Link</label>
                <input
                  type="url"
                  class="form-control @error('url') is-invalid @enderror"
                  id="url"
                  name="url"
                  value="{{ old('url') }}"
                  placeholder="https://www.example.com/"
                  title="E.g. https://www.example.com/"
                  required
                >
                @error('url')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Prompt (max 200) -->
              <div class="mb-4">
                <label for="prompt" class="form-label required-asterisk">Prompt</label>
                <input
                  type="text"
                  class="form-control @error('prompt') is-invalid @enderror"
                  id="prompt"
                  name="prompt"
                  maxlength="200"
                  value="{{ old('prompt') }}"
                  placeholder="Enter your prompt (max 200 characters)"
                  required
                >
                <div class="d-flex justify-content-between mt-1 small text-muted">
                  <span>Keep it concise and clear.</span>
                  <span class="counter"><span id="promptCount">{{ mb_strlen(old('prompt','')) }}</span>/200</span>
                </div>
                @error('prompt')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Domain (Select2, AJAX) -->
              <div class="form-group mb-3" style="min-width: 320px" id="hiddenSelect">
                <label for="domain-select" class="form-label required-asterisk">Domain</label>
                <select
                  class="default-select form-control form-select wide @error('domain') is-invalid @enderror"
                  id="domain-select"
                  name="domain"
                  data-old="{{ old('domain') }}"
                >
                  <option value="">Search for Domain...</option>
                  @if(old('domain'))
                    <option value="{{ old('domain') }}" selected>{{ old('domain') }}</option>
                  @endif
                  <option value="default.com" selected>default.com</option>
                </select>
                @error('domain')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Forced Subscribe -->
              <div class="mb-4 form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  value="1"
                  id="forced_subscribe"
                  name="forced_subscribe"
                  {{ old('forced_subscribe') ? 'checked' : '' }}
                >
                <label class="form-check-label" for="forced_subscribe">
                  Forced Subscribe (require users to be subscribed)
                </label>
              </div>

              <div class="d-flex justify-content-end">
                <button type="submit" id="submitBtn" class="btn btn-primary">Save</button>
                <button type="button" id="processingBtn" class="btn btn-primary btn-processing" style="display: none;">
                  Processing...
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- RIGHT: PREVIEW -->
      <div class="col-lg-5">
        <div class="card shadow-sm preview-box">
          <div class="card-body">
            <h5 class="mb-3">Preview</h5>
            <img
              src="{{ asset('images/default-prompt-example.png') }}"
              alt="Prompt Preview"
              class="preview-img"
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script>
  // --- helpers ---
  function sanitize(str){ return String(str || '').trim(); }
  function truncate(str, n){
    if(!str) return '';
    return str.length > n ? str.slice(0, n-1) + '…' : str;
  }

  // --- elements ---
  const $prompt = document.getElementById('prompt');
  const $promptCount = document.getElementById('promptCount');
  const $url = document.getElementById('url');
  const $domain = document.getElementById('domain-select');
  const form = document.getElementById('ytPushForm');
  const $submitBtn = document.getElementById('submitBtn');
  const $processingBtn = document.getElementById('processingBtn');

  // prompt live counter
  if ($prompt && $promptCount) {
    $prompt.addEventListener('input', () => {
      $promptCount.textContent = ($prompt.value || '').length;
    });
  }

  // simple client-side validation aligned with server rules
  form.addEventListener('submit', function(e){
    // clear old errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback.js').forEach(el => el.remove());

    let hasError = false;

    // URL required + must be a valid URL
    const urlVal = sanitize($url.value);
    try {
      if(!urlVal){ throw new Error('missing'); }
      new URL(urlVal);
    } catch {
      hasError = true;
      $url.classList.add('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback js';
      fb.textContent = 'Please enter a valid URL (e.g. https://www.example.com/).';
      $url.insertAdjacentElement('afterend', fb);
    }

    // Prompt required (maxlength handled by HTML)
    const pr = sanitize($prompt.value);
    if(!pr){
      hasError = true;
      $prompt.classList.add('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback js';
      fb.textContent = 'Please enter a prompt.';
      $prompt.insertAdjacentElement('afterend', fb);
    }

    // Domain required
    const domVal = sanitize($domain.value);
    if(!domVal){
      hasError = true;
      // add invalid style to select2 widget
      const $sel = $('#domain-select').data('select2')?.$container?.find('.select2-selection');
      if ($sel) $sel.addClass('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback js';
      fb.textContent = 'Please select a domain.';
      document.getElementById('hiddenSelect').appendChild(fb);
    }

    if(hasError){
      e.preventDefault();
      return false;
    }

    // Show processing button and hide submit button
    $submitBtn.style.display = 'none';
    $processingBtn.style.display = 'inline-block';
    return true;
  });

  // ——— Select2 setup ——————————————————————————————————————
  $('#domain-select').select2({
    placeholder: 'Search for Domain…',
    allowClear: true,
    ajax: {
      url: "{{ route('domain.domain-list') }}",
      dataType: 'json',
      delay: 250,
      data: p => ({ q: p.term || '' }),
      processResults: r => ({
        results: (r.data || []).map(i => ({
          id: i.text,
          text: i.text
        }))
      }),
      cache: true
    },
    templateResult: d => d.loading ? d.text : $(`<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
    escapeMarkup: m => m
  });

  // Ensure old value is visible if it came from validation errors
  const oldDomain = $('#domain-select').data('old');
  if (oldDomain) {
    const exists = $('#domain-select option[value="'+oldDomain.replace(/"/g,'\\"')+'"]').length;
    if (!exists) {
      const opt = new Option(oldDomain, oldDomain, true, true);
      $('#domain-select').append(opt).trigger('change');
    }
  }
</script>
@endpush