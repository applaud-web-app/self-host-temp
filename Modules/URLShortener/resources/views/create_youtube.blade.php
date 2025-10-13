@extends('layouts.master')

@push('styles')
<style>
  .content-body { padding-top: 1rem; }
  .card { border-radius: .75rem; }
  .preview-box { position: sticky; top: 1rem; }
  .required-asterisk::after { content: " *"; color: #dc3545; margin-left: 2px; }
  .invalid-feedback { display: block; }
  .counter { font-variant-numeric: tabular-nums; }
  .preview-img { width: 100%; height: auto; border-radius: .5rem; border: 1px solid #e9ecef; }
  .btn-processing {
    pointer-events: none;
    opacity: 0.6;
  }
</style>
@endpush

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2 class="me-auto mb-0">Create YT Link</h2>
    </div>

    <div class="row g-3">
      <!-- LEFT: FORM -->
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-body">
            <form id="ytPushForm" method="POST" action="{{ route('url_shortener.youtube.store') }}" novalidate>
              @csrf

              <!-- Channel URL -->
              <div class="mb-4">
                <label for="channel_url" class="form-label required-asterisk">YouTube Channel URL</label>
                <input
                  type="url"
                  class="form-control @error('channel_url') is-invalid @enderror"
                  id="channel_url"
                  name="channel_url"
                  value="{{ old('channel_url') }}"
                  placeholder="https://www.youtube.com/@yourhandle"
                  pattern="^https?:\/\/(www\.)?youtube\.com\/@[\w.\-]+(\/.*)?$"
                  title="Enter a valid YouTube @handle URL, e.g. https://www.youtube.com/@yourhandle"
                  required
                >
                <div class="form-text">
                  Only <code>@handle</code> channel links are allowed (e.g. <code>https://www.youtube.com/@yourhandle</code>).
                </div>
                @error('channel_url')
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
<script>
  // --- helpers ---
  function sanitize(str){ return String(str || '').trim(); }
  function truncate(str, n){
    if(!str) return '';
    return str.length > n ? str.slice(0, n-1) + '…' : str;
  }

  // ONLY allow /@handle channel URLs
  const HANDLE_RE = /^https?:\/\/(www\.)?youtube\.com\/@[\w.\-]+(\/.*)?$/i;

  // --- elements ---
  const $prompt = document.getElementById('prompt');
  const $promptCount = document.getElementById('promptCount');
  const $channel = document.getElementById('channel_url');
  const $forced = document.getElementById('forced_subscribe');
  const form = document.getElementById('ytPushForm');
  const $submitBtn = document.getElementById('submitBtn');
  const $processingBtn = document.getElementById('processingBtn');

  // prompt live counter
  if ($prompt && $promptCount) {
    $prompt.addEventListener('input', () => {
      $promptCount.textContent = ($prompt.value || '').length;
    });
  }

  // simple client-side validation that enforces ONLY @handle URLs
  form.addEventListener('submit', function(e){
    // clear old errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback.js').forEach(el => el.remove());

    let hasError = false;

    // Channel URL required + must match ONLY @handle
    const ch = sanitize($channel.value);
    if(!ch || !HANDLE_RE.test(ch)){
      hasError = true;
      $channel.classList.add('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback js';
      fb.textContent = 'Please enter a valid YouTube @handle URL (e.g. https://www.youtube.com/@yourhandle).';
      $channel.insertAdjacentElement('afterend', fb);
    }

    // Prompt required (HTML maxlength already enforces 200)
    const pr = sanitize($prompt.value);
    if(!pr){
      hasError = true;
      $prompt.classList.add('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback js';
      fb.textContent = 'Please enter a prompt.';
      $prompt.insertAdjacentElement('afterend', fb);
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
</script>
@endpush