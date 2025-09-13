@extends('layouts.master')

@section('content')
@push('styles')
  <style>
    .nav-tabs .nav-link.active, .nav-tabs .nav-item.show .nav-link{
      border: 1px dashed #b1b1b1;
    }
    .nav-tabs{
      border-bottom: none;
    }
  </style>
@endpush
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head">
      <h2 class="mb-3 flex-shrink-0 me-3">
        Custom Prompt <small class="fs-16 text-primary">[{{ $domainData->name }}]</small>
      </h2>
    </div>

    {{-- TOP TABS --}}
    <ul class="nav nav-tabs mb-0 border-none" id="promptTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 py-3" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-pane" type="button" role="tab" aria-controls="manual-pane" aria-selected="true">
          <strong>Manual Prompt</strong>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-3" id="default-tab" data-bs-toggle="tab" data-bs-target="#default-pane" type="button" role="tab" aria-controls="default-pane" aria-selected="false">
          <strong>Default Prompt</strong>
        </button>
      </li>
    </ul>

    <div class="tab-content card p-4 rounded-0" id="promptTabsContent">
      {{-- MANUAL PROMPT TAB --}}
      <div class="tab-pane fade show active" id="manual-pane" role="tabpanel" aria-labelledby="manual-tab">
        <form method="POST" action="{{ $action }}" id="customPromptForm" novalidate>
          @csrf
          <div class="row g-3">
            <div class="col-lg-7 col-md-7 col-12">
              {{-- Custom Prompt Fields --}}
              <div class="card h-auto">
                <div class="card-body p-3">
                  <div class="row">
                    <div class="col-12 mb-3">
                      <label for="title" class="form-label">Title</label>
                      <input type="text" name="title" id="title" class="form-control" maxlength="100" value="{{ old('title', $customPrompt->title ?? 'We want to notify you about the latest updates.') }}" required>
                    </div>

                    <div class="col-12 mb-3">
                      <label for="description" class="form-label">Description</label>
                      <input type="text" name="description" id="description" class="form-control" maxlength="100" value="{{ old('description', $customPrompt->description ?? 'You can unsubscribe anytime later.') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                      <label for="status" class="form-label">Status</label>
                      @php
                        // Priority: old('status') → model value → default 'active'
                        $statusString = old('status');

                        if ($statusString === null) {
                            // Try model fields; if missing/null, default to 1 (active)
                            $statusInt = isset($customPrompt)
                                ? (int)($customPrompt->status ?? $customPrompt->cp_status ?? 1)
                                : 1; // no model yet → active

                            $statusString = $statusInt === 1 ? 'active' : 'inactive';
                        }
                      @endphp

                      <select name="status" id="status" class="form-control" required>
                        <option value="active"   {{ $statusString === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $statusString === 'inactive' ? 'selected' : '' }}>Inactive</option>
                      </select>
                    </div>

                    <div class="col-md-6 mb-3">
                      <label for="widget_icon" class="form-label">Icon URL</label>
                      <div class="input-group">
                        <input type="url" class="form-control" name="widget_icon" id="widget_icon"
                               maxlength="2048"
                               value="{{ old('widget_icon', $customPrompt->icon ?? asset('images/push/icons/alarm-1.png')) }}" required>
                        <button class="btn btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#iconModal">
                          <i class="fas fa-upload"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Allow Button Settings --}}
              <div class="card h-auto mt-3">
                <div class="card-body p-3">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label for="allowButtonText" class="form-label">Allow Button Text</label>
                      <input type="text" name="allowButtonText" id="allowButtonText" class="form-control"
                             maxlength="100"
                             value="{{ old('allowButtonText', $customPrompt->allow_btn_text ?? 'Allow') }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label for="allowButtonColor" class="form-label">Allow Button Background Color</label>
                      <div class="input-group">
                        <input type="text" class="form-control" id="allowButtonColorText" placeholder="Color code" readonly>
                        <input type="color" name="allowButtonColor" id="allowButtonColor" class="form-control p-1"
                               value="{{ old('allowButtonColor', $customPrompt->allow_btn_color ?? '#00c220') }}" required>
                      </div>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label for="allowButtonTextColor" class="form-label">Allow Button Text Color</label>
                      <div class="input-group">
                        <input type="text" class="form-control" id="allowButtonTextColorText" placeholder="Color code" readonly>
                        <input type="color" name="allowButtonTextColor" id="allowButtonTextColor" class="form-control p-1"
                               value="{{ old('allowButtonTextColor', $customPrompt->allow_btn_text_color ?? '#ffffff') }}" required>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Deny / Allow-Only Settings --}}
              <div class="card h-auto mt-3">
                <div class="card-body p-3">
                  <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                      <label for="denyButtonText" class="form-label">Deny Button Text</label>
                      <input type="text" name="denyButtonText" id="denyButtonText" class="form-control"
                             maxlength="100"
                             value="{{ old('denyButtonText', $customPrompt->deny_btn_text ?? 'Deny') }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label for="denyButtonColor" class="form-label">Deny Button Background Color</label>
                      <div class="input-group">
                        <input type="text" class="form-control" id="denyButtonColorText" placeholder="Color code" readonly>
                        <input type="color" name="denyButtonColor" id="denyButtonColor" class="form-control p-1"
                               value="{{ old('denyButtonColor', $customPrompt->deny_btn_color ?? '#ff0000') }}" required>
                      </div>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label for="denyButtonTextColor" class="form-label">Deny Button Text Color</label>
                      <div class="input-group">
                        <input type="text" class="form-control" id="denyButtonTextColorText" placeholder="Color code" readonly>
                        <input type="color" name="denyButtonTextColor" id="denyButtonTextColor" class="form-control p-1"
                               value="{{ old('denyButtonTextColor', $customPrompt->deny_btn_text_color ?? '#ffffff') }}" required>
                      </div>
                    </div>

                    <div class="col-md-4 mb-3 d-none" id="denyTextAllowOnlyWrap">
                      <label for="denyTextAllowOnly" class="form-label">Deny Text (AllowOnly Mode)</label>
                      <input type="text" name="denyTextAllowOnly" id="denyTextAllowOnly" class="form-control"
                             maxlength="100"
                             value="{{ old('denyTextAllowOnly', $customPrompt->deny_text_allow_only ?? '') }}">
                    </div>

                    <div class="col-md-6">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="allowOnlyMode" name="allowOnlyMode"
                          {{ old('allowOnlyMode', $customPrompt->allow_only ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="allowOnlyMode">
                          Enable <strong>Allow-Only Mode</strong>
                        </label>
                      </div>
                    </div>

                  </div>
                </div>
              </div>

              {{-- ADVANCED SETTINGS ACCORDION --}}
              <div class="accordion mt-3" id="advancedAccordion">
                <div class="accordion-item">
                  <h2 class="card accordion-header" id="advHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advCollapse" aria-expanded="false" aria-controls="advCollapse">
                      Advanced Settings
                    </button>
                  </h2>
                  <div id="advCollapse" class="accordion-collapse collapse card" aria-labelledby="advHeading" data-bs-parent="#advancedAccordion">
                    <div class="accordion-body">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label for="customPromptDesktop" class="form-label">Custom Prompt on Desktop</label>
                          @php
                            $deskVal = old('customPromptDesktop', ($customPrompt->enable_desktop ?? true) ? 'enable' : 'disable');
                          @endphp
                          <select name="customPromptDesktop" id="customPromptDesktop" class="form-control">
                            <option value="enable"  {{ $deskVal == 'enable'  ? 'selected' : '' }}>Enable</option>
                            <option value="disable" {{ $deskVal == 'disable' ? 'selected' : '' }}>Disable</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label for="customPromptMobile" class="form-label">Custom Prompt on Mobile</label>
                          @php
                            $mobVal = old('customPromptMobile', ($customPrompt->enable_mobile ?? true) ? 'enable' : 'disable');
                          @endphp
                          <select name="customPromptMobile" id="customPromptMobile" class="form-control">
                            <option value="enable"  {{ $mobVal == 'enable'  ? 'selected' : '' }}>Enable</option>
                            <option value="disable" {{ $mobVal == 'disable' ? 'selected' : '' }}>Disable</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label for="promptLocationMobile" class="form-label">Prompt Location on Mobile</label>
                          @php
                            $locVal = old('promptLocationMobile', $customPrompt->mobile_location ?? 'bottom');
                          @endphp
                          <select name="promptLocationMobile" id="promptLocationMobile" class="form-control">
                            <option value="top"    {{ $locVal=='top' ? 'selected':'' }}>Top</option>
                            <option value="center" {{ $locVal=='center' ? 'selected':'' }}>Center</option>
                            <option value="bottom" {{ $locVal=='bottom' ? 'selected':'' }}>Bottom</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label for="promptDelay" class="form-label">Prompt Delay (in Seconds)</label>
                          <input type="number" name="promptDelay" id="promptDelay" class="form-control"
                                 min="0"
                                 value="{{ old('promptDelay', $customPrompt->delay ?? 0) }}">
                        </div>

                        <div class="col-md-6">
                          <label for="reappearIfDeny" class="form-label">Reappear if Deny (in Seconds)</label>
                          <input type="number" name="reappearIfDeny" id="reappearIfDeny" class="form-control"
                                 min="0"
                                 value="{{ old('reappearIfDeny', $customPrompt->reappear ?? 0) }}">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Actions --}}
              <div class="mt-3">
                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                 <i class="fas fa-bolt me-1"></i> Generate Script
                </button>
                <button type="button" class="btn btn-primary w-100 d-none" id="processingBtn">
                  <i class="fas fa-spinner fa-spin me-2"></i> Processing...
                </button>
              </div>
            </div>

            {{-- Notification Preview (Manual) --}}
            <div class="col-lg-5 col-md-5 col-12">
              <div class="card h-auto p-4" id="manual-preview">
                <div class="d-flex align-items-center">
                  <img class="notification-icon" id="notification-icon"
                       src="{{ old('widget_icon', $customPrompt->icon ?? asset('images/push/icons/alarm-1.png')) }}"
                       alt="Icon" width="48" height="48">
                  <div class="ms-3">
                    <h5 class="mb-1 notification-title" id="notification-title">
                      {{ old('title', $customPrompt->title ?? 'We want to notify you about the latest updates.') }}
                    </h5>
                    <p class="mb-0 notification-description" id="notification-description">
                      {{ old('description', $customPrompt->description ?? 'You can unsubscribe anytime later.') }}
                    </p>
                  </div>
                </div>
                <div class="notification-footer mt-3 d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-secondary btn-sm" id="deny-button">
                    {{ old('denyButtonText', $customPrompt->deny_btn_text ?? 'Deny') }}
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" id="allow-button">
                    {{ old('allowButtonText', $customPrompt->allow_btn_text ?? 'Allow') }}
                  </button>
                  <span class="text-muted small d-none" id="deny-inline-text"></span>
                </div>
                <p class="powered-by mt-3 mb-0">Powered by <span class="text-primary">Aplu Push</span></p>
              </div>
            </div>
          </div>
        </form>
      </div>

      {{-- DEFAULT PROMPT TAB --}}
      <div class="tab-pane fade" id="default-pane" role="tabpanel" aria-labelledby="default-tab">
        <div class="row g-3">
          <div class="col-lg-7 col-md-7 col-12">
            <div class="card h-auto p-4">
              <div class="gap-2">
                 <div>
                  <h5 class="mb-1">Default Browser Prompt</h5>
                  <p class="mb-2 text-muted">
                    Use the native browser permission prompt with one click. <br>
                    If you want to use the default permission prompt only, you can use our lightweight script. 
                    It contains just the minimal setup for the default prompt. Click the <strong>Generate</strong> button to use it.
                  </p>
                </div>

                <div class="">
                  <a href="{{ $defaultIntegration }}" class="btn btn-outline-primary">
                    <i class="fas fa-bolt me-1"></i> Generate Script
                  </a>
                </div>
              </div>
              <div id="defaultScriptResult" class="mt-3 d-none">
                <label class="form-label">Generated Script</label>
                <pre class="bg-light p-3 rounded small mb-2" id="defaultScriptBox"></pre>
                <button class="btn btn-sm btn-secondary" type="button" id="copyDefaultScript">Copy</button>
              </div>
            </div>
          </div>

          {{-- Default Preview --}}
          <div class="col-lg-5 col-md-5 col-12">
            <div class="card h-auto p-3">
              <img src="{{ asset('images/default-prompt-example.png') }}"
                   class="img-fluid rounded border" alt="Default Prompt Example">
              <p class="powered-by mt-3 mb-0">Powered by <span class="text-primary">Aplu Push</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Icon Picker Modal --}}
    <div class="modal fade" id="iconModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="iconModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fs-20" id="iconModalLabel">Select an Icon</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body d-flex flex-wrap" id="icon-container">
            <div id="icon-loader" class="spinner-border m-auto" role="status">
              <span class="visually-hidden">Loading…</span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  // ===== Light-weight helpers =====
  const ICON_URLS = {!! json_encode($iconUrls) !!};
  const hexColor = /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/;

  function loadIcons() {
    const $wrap = $('#icon-container').empty();
    ICON_URLS.forEach(url => {
      $('<div class="m-1">')
        .append(
          $('<img>', { src: url, width: 52, height: 52, class: 'img-thumbnail p-2', alt: 'icon', loading: 'lazy' })
            .css('cursor', 'pointer')
            .on('click', () => setIcon(url))
        ).appendTo($wrap);
    });
  }
  function setIcon(url){
    $('#widget_icon').val(url);
    $('#notification-icon').attr('src', url);
    $('#iconModal').modal('hide');
  }

  // Single preview updater (reduces multiple handlers)
  function updatePreview(){
    // Title / Description / Icon
    $('#notification-title').text($('#title').val());
    $('#notification-description').text($('#description').val());
    $('#notification-icon').attr('src', $('#widget_icon').val());

    // Allow button
    $('#allow-button').text($('#allowButtonText').val())
      .css('background-color', $('#allowButtonColor').val())
      .css('color', $('#allowButtonTextColor').val());
    $('#allowButtonColorText').val($('#allowButtonColor').val());
    $('#allowButtonTextColorText').val($('#allowButtonTextColor').val());

    // Deny button
    const allowOnly = $('#allowOnlyMode').is(':checked');
    $('#deny-button').text($('#denyButtonText').val())
      .css('background-color', $('#denyButtonColor').val())
      .css('color', $('#denyButtonTextColor').val())
      .toggle(!allowOnly);
    $('#denyButtonColorText').val($('#denyButtonColor').val());
    $('#denyButtonTextColorText').val($('#denyButtonTextColor').val());

    // AllowOnly inline text
    $('#deny-inline-text').toggleClass('d-none', !allowOnly)
      .text($('#denyTextAllowOnly').val() || '');
  }

  // Debounced bind (simple)
  function bindLive(id, ev = 'input'){ $(id).on(ev, updatePreview); }

  // ===== Ready =====
  $(function(){
    // Tabs: keep manual preview visible only on manual tab
    $('#promptTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
      const target = $(e.target).attr('data-bs-target');
      $('#manual-preview').closest('.col-lg-5').toggle(target === '#manual-pane');
    });

    // Icon modal
    $('#iconModal').on('show.bs.modal', loadIcons);

    // Bind once
    bindLive('#title'); bindLive('#description'); bindLive('#widget_icon');
    bindLive('#allowButtonText'); bindLive('#allowButtonColor', 'input'); bindLive('#allowButtonTextColor', 'input');
    bindLive('#denyButtonText'); bindLive('#denyButtonColor', 'input'); bindLive('#denyButtonTextColor', 'input');
    bindLive('#allowOnlyMode', 'change'); bindLive('#denyTextAllowOnly');

    // Seed preview values
    updatePreview();

    // jQuery Validate
    $('#customPromptForm').validate({
      ignore: [],
      rules: {
        title: { required:true, maxlength:100 },
        description: { required:true, maxlength:100 },
        widget_icon: { required:true, url:true, maxlength:2048 },
        allowButtonText: { required:true, maxlength:100 },
        denyButtonText: { required:true, maxlength:100 },
        allowButtonColor: { required:true, pattern: hexColor },
        allowButtonTextColor: { required:true, pattern: hexColor },
        denyButtonColor: { required:true, pattern: hexColor },
        denyButtonTextColor: { required:true, pattern: hexColor },
        customPromptDesktop: { required:true },
        customPromptMobile: { required:true },
        promptLocationMobile: { required:true },
        bottomBell: { required:true },
        promptDelay: { number:true, min:0 },
        reappearIfDeny: { number:true, min:0 },
        status: { required:true }
      },
      messages: {
        allowButtonColor: { pattern: 'Use a valid HEX color like #fff or #ffffff' },
        allowButtonTextColor: { pattern: 'Use a valid HEX color like #000 or #000000' },
        denyButtonColor: { pattern: 'Use a valid HEX color like #fff or #ffffff' },
        denyButtonTextColor: { pattern: 'Use a valid HEX color like #000 or #000000' },
      },
      errorElement: 'div',
      errorClass: 'invalid-feedback',
      highlight: el => $(el).addClass('is-invalid'),
      unhighlight: el => $(el).removeClass('is-invalid'),
      errorPlacement: function(error, element){
        if (element.parent('.input-group').length) error.insertAfter(element.parent());
        else error.insertAfter(element);
      },
      submitHandler: function(form) {
        const $form = $(form);
        const payload = $form.serialize();

        $('#submitBtn').addClass('d-none');
        $('#processingBtn').removeClass('d-none');

        const $inputs = $form.find('input, select, textarea, button').not('[name="_token"]');
        $inputs.prop('disabled', true);

        $.ajax({
          type: 'POST',
          url: $form.attr('action'),
          data: payload,
          success: function(resp) {
            if (window.iziToast) {
              iziToast.success({
                title: 'Success!',
                message: resp && resp.message ? resp.message : 'Custom Prompt saved.',
                position: 'topRight'
              });
            }
            const redirectUrl = "{{ $customPromptIntegration }}";
            window.location.href = redirectUrl;
          },
          error: function(xhr) {
            let msg = 'There was an error submitting the form. Please try again.';
            if (xhr.status === 422 && xhr.responseJSON) {
              const errors = xhr.responseJSON.errors || {};
              const list = Object.values(errors).flat().map(e => `<li>${e}</li>`).join('');
              msg = `<ul class="mb-0">${list || '<li>Validation failed.</li>'}</ul>`;
              if (window.iziToast) iziToast.error({ title:'Validation Error', message: msg, position:'topRight', timeout: 7000 });
            } else {
              if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
              if (window.iziToast) iziToast.error({ title:'Error!', message: msg, position:'topRight', timeout: 7000 });
            }
          },
          complete: function() {
            $inputs.prop('disabled', false);
            $('#submitBtn').removeClass('d-none');
            $('#processingBtn').addClass('d-none');
          }
        });

        return false; // prevent native submit
      }
    });

    // Guard double-binding
    $('#customPromptForm').on('submit', function(e){
      if(!$('#customPromptForm').valid()){
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }
    });
  });
</script>
@endpush