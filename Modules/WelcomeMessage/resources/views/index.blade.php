@extends('layouts.master')

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/themes/prism-okaidia.min.css" rel="stylesheet">
    <style>
        pre {
            background: #1e1e1e !important;
            border: 1px solid rgba(0, 0, 0, .1);
            max-height: 420px;
            overflow: auto
        }

        .preview {
            border: 1px solid #e7e7e7;
            border-radius: 14px;
            padding: 16px;
            background: #fff
        }

        .preview .rowx {
            display: flex;
            gap: 12px;
            align-items: center
        }

        .preview img {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            object-fit: cover
        }

        .title {
            font-weight: 700;
            margin: 0
        }

        .body {
            color: #666;
            margin: 2px 0 0 0
        }
    </style>
@endpush

@section('content')
    <section class="content-body" id="push_welcome_generator">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center text-head mb-3">
                <h2 class="me-auto mb-0">Welcome Notification Script</h2>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Form -->
                    <div class="card h-auto" id="formCard">
                        <div class="card-header bg-white py-3">
                            <h4 class="mb-0">Configure</h4>
                        </div>
                        <div class="card-body">
                            <form id="cfgForm">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="mb-2">Title</label>
                                        <input type="text" id="title" class="form-control"
                                            value="Welcome to Our Site!"  maxlength="70">
                                        <small class="text-muted">Notification title</small>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="mb-2">Description</label>
                                        <input type="text" id="body" class="form-control"
                                            value="Thanks for subscribing — welcome aboard!" maxlength="90">
                                        <small class="text-muted">Notification body</small>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <label class="mb-2">Icon URL</label>
                                        <input type="text" id="icon" class="form-control" value="{{ asset('/images/push/icons/alarm-1.png') }}">
                                        <small class="text-muted">Icon shown in the notification</small>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" id="genBtn" class="btn btn-primary px-5">
                                        <i class="fas fa-code me-2"></i>Generate
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Output -->
                    <div class="card shadow-sm" id="outCard" style="display:none;">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0">Your Script</h4>
                                <p class="small text-muted mb-0">Paste before <code>&lt;/body&gt;</code></p>
                            </div>
                            <button type="button" id="backBtn" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                        </div>
                        <div class="card-body">
                            <pre class="rounded p-3 mb-3 line-numbers"><code class="language-html" id="scriptCode"></code></pre>
                            <button id="copyBtn" class="btn btn-primary">
                                <i class="fas fa-copy me-2"></i>Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="col-lg-4">
                    <div class="sticky-top" style="top:80px;">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="fs-20 mb-3">Preview</h4>
                                <div class="preview">
                                    <div class="rowx">
                                        <img id="pvIcon" src="{{ asset('/images/push/icons/alarm-1.png') }}" alt="icon">
                                        <div>
                                            <p class="title" id="pvTitle">Welcome to Our Site!</p>
                                            <p class="body" id="pvBody">Thanks for subscribing — welcome aboard!</p>
                                        </div>
                                    </div>
                                    {{-- <p class="small text-muted mt-2 mb-0"><i class="far fa-bell me-1"></i>Simulated preview
                                    </p> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /preview -->
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/plugins/line-numbers/prism-line-numbers.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>

    <script>
        (function() {
            // live preview
            function syncPreview() {
                document.getElementById('pvTitle').textContent = document.getElementById('title').value ||
                    'Welcome to Our Site!';
                document.getElementById('pvBody').textContent = document.getElementById('body').value ||
                    'Thanks for subscribing — welcome aboard!';
                document.getElementById('pvIcon').src = document.getElementById('icon').value || '{{ asset('/images/push/icons/alarm-1.png') }}';
            }
            ['title', 'body', 'icon'].forEach(id => {
                document.getElementById(id).addEventListener('input', syncPreview);
            });
            syncPreview();

            // build script with ONLY the 3 changeable values
            function buildScript() {
                const title = document.getElementById('title').value || 'Welcome to Our Site!';
                const body = document.getElementById('body').value || 'Thanks for subscribing — welcome aboard!';
                const icon = document.getElementById('icon').value || '{{ asset('/images/push/icons/alarm-1.png') }}';

                return `
<script>
(function () {
  const TOKEN_KEY = 'push_token';
  const FLAG_KEY  = 'push_welcome_shown';
  const _setItem  = localStorage.setItem;
  localStorage.setItem = function (k, v) {
    const hadToken = !!localStorage.getItem(TOKEN_KEY);
    const ret = _setItem.apply(this, arguments);
    if (k === TOKEN_KEY && !hadToken && v && Notification.permission === 'granted' && !localStorage.getItem(FLAG_KEY)) {
      new Notification(${JSON.stringify(title)}, {
        body: ${JSON.stringify(body)},
        icon: ${JSON.stringify(icon)}
      });
      localStorage.setItem(FLAG_KEY, '1');
    }
    return ret;
  };
})();
<\/script>`.trim();
            }

            // handlers
            document.getElementById('genBtn').addEventListener('click', function() {
                document.getElementById('scriptCode').textContent = buildScript();
                document.getElementById('formCard').style.display = 'none';
                document.getElementById('outCard').style.display = 'block';
                Prism.highlightAll();
            });
            document.getElementById('backBtn').addEventListener('click', function() {
                document.getElementById('outCard').style.display = 'none';
                document.getElementById('formCard').style.display = 'block';
            });

            // copy
            const clipboard = new ClipboardJS('#copyBtn', {
                text: function() {
                    return document.getElementById('scriptCode').textContent;
                }
            });
            clipboard.on('success', function() {
                const btn = document.getElementById('copyBtn');
                const old = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied';
                setTimeout(() => btn.innerHTML = old, 1500);
            });
            clipboard.on('error', function() {
                alert('Copy failed — select & copy manually.');
            });
        })();
    </script>
@endpush