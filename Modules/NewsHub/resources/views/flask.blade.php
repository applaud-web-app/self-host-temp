@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">
                {{ $flask ? 'Edit News Flask' : 'Create News Flask' }}
                <small class="text-muted">— {{ $domain->name }}</small>
            </h2>
            <a href="{{ route('news-hub.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('news-hub.flask.save') }}" autocomplete="off">
            @csrf
            <input type="hidden" name="domain_id" value="{{ $domain->id }}">

            <div class="card">
                <div class="card-body row g-4">
                    <div class="col-lg-6">
                        <label class="form-label">Feed URL <span class="text-danger">*</span></label>
                        <input type="url" name="feed_url" class="form-control" placeholder="https://example.com/feed.xml"
                               value="{{ old('feed_url', $flask->feed_url ?? '') }}" required>
                        <small class="text-muted">Enter a valid RSS/Atom feed URL.</small>
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Enter title"
                               value="{{ old('title', $flask->title ?? '') }}" required>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Theme Color <span class="text-danger">*</span></label>
                        <input type="color" name="theme_color" class="form-control form-control-color"
                               value="{{ old('theme_color', $flask->theme_color ?? '#fd683e') }}">
                    </div>

                    @php
                        $selectedTrigger = old('trigger_timing',
                            ($flask && $flask->exit_intent) ? 'exit_intent' :
                            (($flask && $flask->after_seconds) ? 'after_seconds' :
                            (($flask && $flask->scroll_down) ? 'after_scroll' : 'exit_intent'))
                        );
                    @endphp

                    <div class="col-lg-8">
                        <label class="form-label d-block">Trigger Timing <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input trigger-radio" type="radio" name="trigger_timing" id="trigger_exit" value="exit_intent"
                                    {{ $selectedTrigger === 'exit_intent' ? 'checked' : '' }}>
                                <label class="form-check-label" for="trigger_exit">Exit Intent</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input trigger-radio" type="radio" name="trigger_timing" id="trigger_seconds" value="after_seconds"
                                    {{ $selectedTrigger === 'after_seconds' ? 'checked' : '' }}>
                                <label class="form-check-label" for="trigger_seconds">After user spends N seconds</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input trigger-radio" type="radio" name="trigger_timing" id="trigger_scroll" value="after_scroll"
                                    {{ $selectedTrigger === 'after_scroll' ? 'checked' : '' }}>
                                <label class="form-check-label" for="trigger_scroll">After user scrolls to next screen</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4" id="afterSecondsWrap" style="display:none;">
                        <label class="form-label">After user spends <small class="text-muted">(seconds)</small></label>
                        <input type="number" min="1" step="1" class="form-control" name="after_seconds"
                               value="{{ old('after_seconds', $flask->after_seconds ?? 20) }}">
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Show Again After <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="0" step="1" class="form-control" name="show_again_after_minutes"
                                   value="{{ old('show_again_after_minutes', $flask->show_again_after_minutes ?? 5) }}">
                            <span class="input-group-text">Minutes</span>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="enable_desktop" name="enable_desktop"
                                   {{ old('enable_desktop', ($flask->enable_desktop ?? true)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_desktop">Enable For Desktop</label>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="enable_mobile" name="enable_mobile"
                                   {{ old('enable_mobile', ($flask->enable_mobile ?? true)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_mobile">Enable For Mobile</label>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="status" name="status"
                                   {{ old('status', ($flask->status ?? true)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">Active</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                    <a href="{{ route('news-hub.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    function toggleSeconds() {
        const selected = document.querySelector('input[name="trigger_timing"]:checked')?.value;
        const wrap = document.getElementById('afterSecondsWrap');
        if (selected === 'after_seconds') {
            wrap.style.display = '';
        } else {
            wrap.style.display = 'none';
        }
    }
    document.querySelectorAll('.trigger-radio').forEach(r => r.addEventListener('change', toggleSeconds));
    toggleSeconds(); // on load
})();
</script>
@endpush