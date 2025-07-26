@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3 flex-shrink-0 me-3">
                {{ $customPrompt ? 'Update' : 'Create' }} Custom Prompt: 
                <small class="fs-16 text-primary">[{{ $domainData->name }}]</small>
            </h2>
        </div>

        <!-- Form to create or update the custom prompt -->
        <form method="POST" action="{{ $action }}" id="customPromptForm">
           @csrf
            @if($customPrompt)
                @method('PUT')  
            @endif

            <div class="row">
                <div class="col-lg-8 col-md-8 col-12">
                    <!-- Custom Prompt Fields -->
                    <div class="card h-auto">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label for="title">Title</label>
                                        <input type="text" name="title" id="title" class="form-control" 
                                               value="{{ old('title', $customPrompt->title ?? '') }}" required>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <input type="text" name="description" id="description" class="form-control" 
                                               value="{{ old('description', $customPrompt->description ?? '') }}">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select name="status" id="status" class="form-control" required>
                                            <option value="active" {{ old('status', $customPrompt->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $customPrompt->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label for="widget_icon">Icon URL</label>
                                        <div class="input-group">
                                            <input type="url" class="form-control" name="widget_icon" id="widget_icon" 
                                                   value="{{ old('widget_icon', $customPrompt->icon ?? '') }}" required>
                                            <button class="btn btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#iconModal">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Allow Button Settings -->
                    <div class="card h-auto">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="allowButtonText">Allow Button Text</label>
                                        <input type="text" name="allowButtonText" id="allowButtonText" class="form-control" 
                                               value="{{ old('allowButtonText', $customPrompt->allow_btn_text ?? '') }}" required>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="allowButtonColor">Allow Button Background Color</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="allowButtonColorText" placeholder="Color code" readonly style="width: auto;">
                                            <input type="color" name="allowButtonColor" id="allowButtonColor" class="form-control p-1" 
                                                   value="{{ old('allowButtonColor', $customPrompt->allow_btn_color ?? '#ffffff') }}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="allowButtonTextColor">Allow Button Text Color</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="allowButtonTextColorText" placeholder="Color code" readonly style="width: auto;">
                                            <input type="color" name="allowButtonTextColor" id="allowButtonTextColor" class="form-control p-1" 
                                                   value="{{ old('allowButtonTextColor', $customPrompt->allow_btn_text_color ?? '#000000') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deny Button Settings -->
                    <div class="card h-auto">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="denyButtonText">Deny Button Text</label>
                                        <input type="text" name="denyButtonText" id="denyButtonText" class="form-control" 
                                               value="{{ old('denyButtonText', $customPrompt->deny_btn_text ?? '') }}" required>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="denyButtonColor">Deny Button Background Color</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="denyButtonColorText" placeholder="Color code"  style="width: auto;">
                                            <input type="color" name="denyButtonColor" id="denyButtonColor" class="form-control p-1" 
                                                   value="{{ old('denyButtonColor', $customPrompt->deny_btn_color ?? '#ffffff') }}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="denyButtonTextColor">Deny Button Text Color</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="denyButtonTextColorText" placeholder="Color code"  style="width: auto;">
                                            <input type="color" name="denyButtonTextColor" id="denyButtonTextColor" class="form-control p-1" 
                                                   value="{{ old('denyButtonTextColor', $customPrompt->deny_btn_text_color ?? '#000000') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Configuration Fields -->
                    <div class="card h-auto">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="customPromptDesktop">Custom Prompt on Desktop</label>
                                        <select name="customPromptDesktop" id="customPromptDesktop" class="form-control">
                                            <option value="enable" {{ old('customPromptDesktop', $customPrompt->custom_prompt_desktop ?? 'enable') == 'enable' ? 'selected' : '' }}>Enable</option>
                                            <option value="disable" {{ old('customPromptDesktop', $customPrompt->custom_prompt_desktop ?? 'disable') == 'disable' ? 'selected' : '' }}>Disable</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="customPromptMobile">Custom Prompt on Mobile</label>
                                        <select name="customPromptMobile" id="customPromptMobile" class="form-control">
                                            <option value="enable" {{ old('customPromptMobile', $customPrompt->custom_prompt_mobile ?? 'enable') == 'enable' ? 'selected' : '' }}>Enable</option>
                                            <option value="disable" {{ old('customPromptMobile', $customPrompt->custom_prompt_mobile ?? 'disable') == 'disable' ? 'selected' : '' }}>Disable</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="promptDelay">Prompt Delay (in Seconds)</label>
                                        <input type="number" name="promptDelay" id="promptDelay" class="form-control" value="{{ old('promptDelay', $customPrompt->delay ?? 0) }}">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="reappearIfDeny">Reappear if Deny (in Seconds)</label>
                                        <input type="number" name="reappearIfDeny" id="reappearIfDeny" class="form-control" value="{{ old('reappearIfDeny', $customPrompt->reappear ?? 0) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">{{ $customPrompt ? 'Update' : 'Save' }} Changes</button>
                    <button type="button" class="btn btn-primary w-100 d-none" id="processingBtn">
                        <i class="fas fa-spinner fa-spin me-2"></i> Processing...
                    </button>
                </div>

                <!-- Notification Preview (Static, for demonstration) -->
                <div class="col-lg-4 col-md-4 col-12">
                    <div class="notification-card">
                        <div class="d-flex align-items-center">
                            <img class="notification-icon" id="notification-icon" src="{{ old('widget_icon', $customPrompt->widget_icon ?? 'https://cdn-icons-png.flaticon.com/128/2058/2058148.png') }}" alt="Icon" class="img-fluid">
                            <div class="ms-3">
                                <h5 class="mb-1 notification-title" id="notification-title">{{ old('title', $customPrompt->title ?? 'Online Journal') }}</h5>
                                <p class="mb-0 notification-description" id="notification-description">{{ old('description', $customPrompt->description ?? 'Want to notify you about the latest updates') }}</p>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <button class="btn btn-secondary btn-sm" id="deny-button">Deny</button>
                            <button class="btn btn-primary btn-sm" id="allow-button">Allow</button>
                        </div>
                        <p class="powered-by">Powered by <span class="text-primary">Aplu Push</span></p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Modal: Select Icons -->
<div class="modal fade" id="iconModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="iconModalLabel" aria-hidden="true">
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

@endsection

@push('scripts')
<script>
    const ICON_URLS = {!! json_encode($iconUrls) !!};

    // Function to render icons inside the modal
    function loadIcons() {
        const iconContainer = $('#icon-container');
        iconContainer.empty();
        ICON_URLS.forEach(url => {
            const iconElement = $('<div class="m-1">')
                .append($('<img>').attr({
                    src: url,
                    width: 52,
                    height: 52,
                    class: 'img-thumbnail p-2',
                    alt: 'icon'
                })
                .css('cursor', 'pointer')
                .click(() => setIcon(url))
            );
            iconContainer.append(iconElement);
        });
    }

    // Set the selected icon URL to the input field and close the modal
    function setIcon(url) {
        $('#widget_icon').val(url); 
        $('#notification-icon').attr('src', url); // Update the icon preview
        $('#iconModal').modal('hide'); 
    }

    // Load icons when the modal is shown
    $('#iconModal').on('show.bs.modal', function () {
        loadIcons(); 
    });

    $(document).ready(function () {
        // Update the notification card's button color and text dynamically
        $('#allowButtonText').on('input', function() {
            $('#allow-button').text($(this).val()); // Update Allow button text
        });

        $('#allowButtonColor').on('input', function() {
            $('#allow-button').css('background-color', $(this).val()); // Update Allow button background color
            $('#allowButtonColorText').val($(this).val()); // Update color code text
        });

        $('#allowButtonTextColor').on('input', function() {
            $('#allow-button').css('color', $(this).val()); // Update Allow button text color
            $('#allowButtonTextColorText').val($(this).val()); // Update color code text
        });

        $('#denyButtonText').on('input', function() {
            $('#deny-button').text($(this).val()); // Update Deny button text
        });

        $('#denyButtonColor').on('input', function() {
            $('#deny-button').css('background-color', $(this).val()); // Update Deny button background color
            $('#denyButtonColorText').val($(this).val()); // Update color code text
        });

        $('#denyButtonTextColor').on('input', function() {
            $('#deny-button').css('color', $(this).val()); // Update Deny button text color
            $('#denyButtonTextColorText').val($(this).val()); // Update color code text
        });

        // Update other preview elements dynamically
        $('#title').on('input', function() {
            $('#notification-title').text($(this).val());
        });

        $('#description').on('input', function() {
            $('#notification-description').text($(this).val());
        });

        $('#widget_icon').on('input', function() {
            $('#notification-icon').attr('src', $(this).val());
        });

        // Submit Form with Processing Button Logic
        $('#customPromptForm').submit(function(e) {
            e.preventDefault(); // Prevent the default form submission

            // Show the processing button and hide the submit button
            $('#submitBtn').addClass('d-none');
            $('#processingBtn').removeClass('d-none');

            // Submit the form via AJAX
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response) {
                    // Show success toast
                    iziToast.success({
                        title: 'Success!',
                        message: 'Custom Prompt has been updated successfully.',
                        position: 'topRight'
                    });

                    // Redirect to the view page after success
                    window.location.href = "{{ route('customprompt.index') }}";
                },
                error: function(xhr, status, error) {
                    // Show error toast
                    iziToast.error({
                        title: 'Error!',
                        message: 'There was an error submitting the form. Please try again.',
                        position: 'topRight'
                    });

                    // Show the submit button again and hide the processing button
                    $('#submitBtn').removeClass('d-none');
                    $('#processingBtn').addClass('d-none');
                }
            });
        });
    });
</script>
@endpush
