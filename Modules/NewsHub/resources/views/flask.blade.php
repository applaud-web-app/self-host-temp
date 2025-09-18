@extends('layouts.master')

@push('styles')
    @php
        $color = old('theme_color', $flask->theme_color ?? '#fd683e');

        // show only PATH in edit mode too — strip scheme/www/current domain
        $existingFull = $flask->feed_url ?? '';
        $existingPath = $existingFull
            ? '/' .
                ltrim(
                    preg_replace(
                        '#^(?:https?://)?(?:www\.)?' . preg_quote($domain->name, '#') . '#i',
                        '',
                        $existingFull,
                    ),
                    '/',
                )
            : '';
        $feedPath = old('feed_path', $existingPath);

        // trigger selection
        $selectedTrigger = old(
            'trigger_timing',
            $flask && $flask->exit_intent
                ? 'exit_intent'
                : ($flask && $flask->after_seconds
                    ? 'after_seconds'
                    : ($flask && $flask->scroll_down
                        ? 'after_scroll'
                        : 'exit_intent')),
        );
    @endphp

    <style>
        .flaskbox-content {
            border: 1px solid {{ $color }};
            border-radius: 10px;
            padding: 15px;
            width: 100%;
            margin: auto;
            border-top: 4px solid {{ $color }};
        }

        .flaskbox {
            display: block;
            width: 100%;
        }

        .flaskbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .flaskbox-item {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e3e3e3;
            text-align: center;
            width: calc(50% - 10px);
            box-sizing: border-box;
            padding: 8px;
        }

        .flaskbox-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 8px;
        }

        .flaskbox-item h3 {
            margin: 5px 0;
            font-size: 13px;
            line-height: 1.3;
            word-break: break-word;
        }

        .flaskbox-item p {
            margin: 0;
            font-size: 11px;
            line-height: 1.3;
            color: #666;
            word-break: break-word;
        }

        .flaskbox-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .flaskbox-header h3 {
            font-size: 16px;
            margin: 0;
            flex-grow: 1;
        }

        .flaskbox-close-btn {
            cursor: pointer;
            font-size: 18px;
            color: #999;
        }

        @media (max-width:992px) {
            .flaskbox-item {
                width: calc(50% - 10px);
            }
        }

        @media (max-width:576px) {
            .flaskbox-item img {
                height: 80px;
            }

            .flaskbox-item h3 {
                font-size: 12px;
            }

            .flaskbox-item p {
                font-size: 10px;
            }

            .flaskbox-header h3 {
                font-size: 14px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center text-head mb-3">
                <h2 class="mb-0 me-3">{{ $flask ? 'Edit News Flask' : 'Create News Flask' }}</h2>
                <small class="badge badge-secondary">{{ $domain->name }}</small>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-5">
                    <form id="newsFlaskForm" method="POST" action="{{ route('news-hub.flask.save') }}" autocomplete="off">
                        @csrf
                        <input type="hidden" name="eq" value="{{ request('eq') }}">

                        <div class="card">
                            <div class="card-body row g-4">

                                {{-- Feed PATH + Fetch --}}
                                <div class="col-lg-12">
                                    <label class="form-label">Feed Path <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div class="input-group">
                                            <span
                                                class="input-group-text bg-primary text-white">https://{{ $domain->name }}</span>
                                            <input type="text" name="feed_path" id="feed_path" class="form-control"
                                                placeholder="/feed.xml" pattern="\/.*" value="{{ $feedPath }}" required>
                                        </div>
                                        <button type="button" id="fetchFlaskBtn" class="btn btn-primary"
                                            title="Fetch feed">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Type only the path beginning with “/”. Prefixed with
                                        <code>https://{{ $domain->name }}</code>.</small>
                                </div>

                                {{-- Title --}}
                                <div class="col-lg-12">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="flask_title" class="form-control"
                                        placeholder="Enter title" value="{{ old('title', $flask->title ?? '') }}" required>
                                </div>

                                {{-- Theme color (text + picker) --}}
                                <div class="col-lg-12">
                                    <label class="form-label">Theme Color <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" id="colorCode2" class="form-control bg-white"
                                            placeholder="#fd683e"
                                            value="{{ old('theme_color', $flask->theme_color ?? '#fd683e') }}">
                                        <input type="color" id="colorPicker2" name="theme_color" class="form-control p-1"
                                            value="{{ old('theme_color', $flask->theme_color ?? '#fd683e') }}" required>
                                    </div>
                                    <small class="text-muted">Type a hex code or use the picker. Both stay in sync.</small>
                                </div>

                                {{-- Trigger Timing (checkboxes) --}}
                                <div class="col-lg-12">
                                    <label class="form-label d-block">Trigger Timing <span
                                            class="text-danger">*</span></label>

                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="exit_intent" id="trigger_exit"
                                            value="1"
                                            {{ old('exit_intent', $flask->exit_intent ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="trigger_exit">Exit Intent</label>
                                    </div>

                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="after_seconds_toggle"
                                            id="trigger_seconds" value="1" style="margin-top: 11px;"
                                            {{ old('after_seconds_toggle', $flask->after_seconds ?? null ? true : false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="trigger_seconds">
                                            After user spends
                                            <select name="after_seconds" id="after_seconds"
                                                class="form-select d-inline-block w-auto ms-1">
                                                @for ($i = 10; $i <= 100; $i += 10)
                                                    <option value="{{ $i }}"
                                                        {{ old('after_seconds', $flask->after_seconds ?? 20) == $i ? 'selected' : '' }}>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select> seconds
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="scroll_down"
                                            id="trigger_scroll" value="1"
                                            {{ old('scroll_down', $flask->scroll_down ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="trigger_scroll">After user scrolls to next
                                            screen</label>
                                    </div>
                                </div>

                                {{-- Show again --}}
                                <div class="col-lg-12">
                                    <label class="form-label">Show Again After <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" min="0" step="1" class="form-control"
                                            name="show_again_after_minutes" id="show_again_after_minutes"
                                            value="{{ old('show_again_after_minutes', $flask->show_again_after_minutes ?? 5) }}"
                                            required>
                                        <span class="input-group-text">Minutes</span>
                                    </div>
                                </div>

                                {{-- desktop/mobile/status --}}
                                <div class="col-lg-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_desktop"
                                            name="enable_desktop"
                                            {{ old('enable_desktop', $flask->enable_desktop ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enable_desktop">Enable Desktop</label>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_mobile"
                                            name="enable_mobile"
                                            {{ old('enable_mobile', $flask->enable_mobile ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enable_mobile">Enable Mobile</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex gap-2">
                                <button type="submit" id="saveBtn" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save
                                </button>
                                <a href="{{ route('news-hub.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Live Preview --}}
                <div class="col-lg-7">
                    <div class="card h-auto">
                        <div class="card-header text-center">
                            <h5 class="card-title mb-0"><b>Preview</b></h5>
                        </div>
                        <div class="card-body">
                            <div id="myFlaskBox" class="flaskbox">
                                <div class="flaskbox-content">
                                    <div class="flaskbox-header">
                                        <h3 id="flaskTitle">{{ old('title', $flask->title ?? 'News Flask Title') }}</h3>
                                        <span class="flaskbox-close-btn">×</span>
                                    </div>
                                    <div class="flaskbox-body">
                                        <div class="flaskbox-grid" id="flaskboxGrid">
                                            <div class="flaskbox-item">
                                                <img src="{{ asset('images/default-flask.png') }}" alt="News image">
                                                <h3>News Headline 1</h3>
                                                <p>Short description of the first news item</p>
                                            </div>
                                            <div class="flaskbox-item">
                                                <img src="{{ asset('images/default-flask.png') }}" alt="News image">
                                                <h3>News Headline 2</h3>
                                                <p>Short description of the second news item</p>
                                            </div>
                                            <div class="flaskbox-item">
                                                <img src="{{ asset('images/default-flask.png') }}" alt="News image">
                                                <h3>News Headline 3</h3>
                                                <p>Short description of the third news item</p>
                                            </div>
                                            <div class="flaskbox-item">
                                                <img src="{{ asset('images/default-flask.png') }}" alt="News image">
                                                <h3>News Headline 4</h3>
                                                <p>Short description of the fourth news item</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- /flaskbox -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Enable/disable seconds dropdown based on checkbox
            function syncSecondsSelect() {
                const isSeconds = $('#trigger_seconds').is(':checked');
                $('#after_seconds').prop('disabled', !isSeconds);
            }
            $('#trigger_seconds').on('change', syncSecondsSelect);
            syncSecondsSelect();

            // Color sync
            const $picker = $('#colorPicker2'),
                $code = $('#colorCode2');

            function applyColor(hex) {
                $code.val(hex);
                $picker.val(hex);
                $('.flaskbox-content').css({
                    'border-color': hex,
                    'border-top-color': hex
                });
            }

            function toHex(c) {
                if (/^#[0-9A-F]{6}$/i.test(c)) return c;
                if (/^#[0-9A-F]{3}$/i.test(c)) return '#' + c[1] + c[1] + c[2] + c[2] + c[3] + c[3];
                return null;
            }
            $picker.on('input', e => applyColor(e.target.value));
            $code.on('input', e => {
                const h = toHex(e.target.value.trim());
                if (h) applyColor(h);
            });

            // Title live update
            $('#flask_title').on('input', function() {
                $('#flaskTitle').text($(this).val() || 'News Flask Title');
            });

            // ===== jQuery Validate =====
            $.validator.addMethod("startsWithSlash", v => /^\/.+/.test(v), "Must start with '/'.");
            $.validator.addMethod("hexcolor", v => /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(v),
                "Enter a valid hex color.");

            // At least one trigger checkbox
            $.validator.addMethod("oneTrigger", function() {
                return $('#trigger_exit').is(':checked') ||
                    $('#trigger_seconds').is(':checked') ||
                    $('#trigger_scroll').is(':checked');
            }, "Select at least one trigger option.");

            $('#newsFlaskForm').validate({
                rules: {
                    feed_path: {
                        required: true,
                        maxlength: 512,
                        startsWithSlash: true
                    },
                    title: {
                        required: true,
                        maxlength: 255
                    },
                    theme_color: {
                        required: true,
                        hexcolor: true
                    },

                    // apply the group validator on one of the checkboxes
                    exit_intent: {
                        oneTrigger: true
                    },

                    // seconds only when toggle checked
                    after_seconds: {
                        required: function() {
                            return $('#trigger_seconds').is(':checked');
                        },
                        number: true,
                        min: 1,
                        max: 86400
                    },

                    show_again_after_minutes: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 10080
                    }
                },
                errorElement: 'div',
                errorClass: 'invalid-feedback',
                highlight: el => el.classList.add('is-invalid'),
                unhighlight: el => el.classList.remove('is-invalid'),
                errorPlacement: function(error, element) {
                    if (element.parent('.input-group').length) error.insertAfter(element.parent());
                    else error.insertAfter(element);
                },
                submitHandler: function(form) {
                    const $btn = $('#saveBtn');
                    $btn.prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
                    form.submit();
                }
            });

            // ===== Fetch preview (4 items) — uses your common feed endpoint =====
            $('#fetchFlaskBtn').on('click', function() {
                const feedPath = $('#feed_path').val().trim();
                if (!feedPath){
                    iziToast.error({
                        title: 'error',
                        message: 'Please enter a feed path (starts with "/").',
                        position: 'topRight'
                    });
                    return;
                }

                const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                const params = new URLSearchParams({
                    _token: '{{ csrf_token() }}',
                    eq: '{{ $eq ?? request('eq') }}',
                    feed_path: feedPath
                });

                fetch(`{{ route('news-hub.fetch.feed') }}`, { // same endpoint you used for News Roll
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: params.toString()
                    })
                    .then(r => r.json())
                    .then(data => {
                        let html = '';
                        if (data.status) {
                            const items = (data.items || []).slice(0, 4);
                            if (!items.length) {
                                html =
                                    '<p class="text-center w-100 text-muted">No items found for this feed.</p>';
                            } else {
                                items.forEach(it => {
                                    const img = it.image ||
                                        '{{ asset('images/default-flask.png') }}';
                                    const t = (it.title || 'Untitled');
                                    const sTitle = t.length > 70 ? t.substring(0, 70) + '…' : t;
                                    const d = (it.description || '');
                                    const sDesc = d.length > 70 ? d.substring(0, 70) + '…' : d;

                                    html += `
                        <div class="flaskbox-item">
                            <img src="${escapeHtml(img)}" alt="News image">
                            <h3>${escapeHtml(sTitle)}</h3>
                            <p>${escapeHtml(sDesc)}</p>
                        </div>`;
                                });
                            }
                        } else {
                            html = `<p class="text-center text-danger w-100">Could not fetch feed items. Please enter a valid feed URL.</p>`;
                        }
                        $('#flaskboxGrid').html(html);
                    })
                    .catch(() => {
                        iziToast.error({
                            title: 'error',
                            message: 'Could not fetch the RSS/Atom feed. Please check the path and try again.',
                            position: 'topRight'
                        });
                    })
                    .finally(() => {
                        $btn.prop('disabled', false).html('<i class="fas fa-undo"></i>');
                    });
            });

            function escapeHtml(s) {
                return (s || '').toString()
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }
        });
    </script>
@endpush