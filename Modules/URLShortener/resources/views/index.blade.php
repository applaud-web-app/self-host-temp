@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <style>
        .content-body {
            padding-top: 1rem;
        }

        .card {
            border-radius: 0.75rem;
        }

        .preview-box {
            position: sticky;
            top: 1rem;
        }

        .required-asterisk::after {
            content: " *";
            color: #dc3545;
            margin-left: 2px;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            padding-bottom: 3px;
            border-color: #ced4da;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            border-color: #ced4da;
        }

        .select2-selection__rendered {
            line-height: 36px !important;
        }

        .select2-selection__arrow {
            height: 36px !important;
        }

        .invalid-feedback {
            display: block;
        }

        .preview-video {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
        }

        .preview-video iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center text-head mb-3">
                <h2 class="me-auto mb-0">YouTube Push</h2>
            </div>

            <div class="row g-3">
                <!-- LEFT -->
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form id="ytChannelForm" novalidate>
                                @csrf

                                <!-- Audience -->
                                <div class="mb-4">
                                    <label class="form-label required-asterisk">Audience Targeting</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="audience_type"
                                                id="audAll" value="all" checked>
                                            <label class="form-check-label" for="audAll">All</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="audience_type"
                                                id="audManual" value="manual">
                                            <label class="form-check-label" for="audManual">Manual</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="audience_type"
                                                id="audSegment" value="segment">
                                            <label class="form-check-label" for="audSegment">Segment</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Manual domains -->
                                <div id="domainWrapper" class="mb-4" style="display:none;">
                                    <label class="form-label required-asterisk">Domains</label>
                                    <select id="domain-select" name="domains[]" class="form-control" multiple></select>
                                    <div class="invalid-feedback" id="domainError" style="display:none;">Please select at
                                        least one domain.</div>
                                </div>

                                <!-- Segment -->
                                <div id="segmentWrapper" class="mb-4" style="display:none;">
                                    <label for="segmentSelect" class="form-label required-asterisk">Segment</label>
                                    <select id="segmentSelect" name="segment" class="form-control"></select>
                                    <div class="invalid-feedback" id="segmentError" style="display:none;">Please select a
                                        segment.</div>
                                </div>

                                <!-- YouTube URL -->
                                <div class="mb-4">
                                    <label class="form-label required-asterisk">YouTube URL</label>
                                    <input type="url" class="form-control" name="video_url" id="video_url"
                                        placeholder="Paste a YouTube video / playlist / channel / @handle URL">
                                    <div class="form-text">
                                        Works best with a direct <strong>video</strong> or <strong>playlist</strong> URL.
                                        For channel: use <code>/channel/UC…</code> or an <code>@handle</code> URL.
                                    </div>
                                </div>

                                <div class="mb-4 text-end">
                                    <button type="button" id="validateBtn" class="btn btn-primary">Validate</button>
                                </div>

                                <!-- Video Type -->
                                <div class="mb-4">
                                    <label class="form-label required-asterisk">Video Type</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="video_type" id="vtAll"
                                                value="all" checked>
                                            <label class="form-check-label" for="vtAll">All</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="video_type" id="vtLong"
                                                value="long">
                                            <label class="form-check-label" for="vtLong">Long Videos (≥4 min)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="col-lg-5">
                    <div class="card shadow-sm preview-box">
                        <div class="card-body">
                            <h5 class="mb-3">Preview</h5>
                            <div id="previewArea"
                                class="ratio ratio-16x9 border rounded d-flex align-items-center justify-content-center bg-light">
                                <div
                                    class="text-center px-3 text-muted d-flex align-items-center justify-content-center w-100">
                                    No preview yet
                                </div>
                            </div>
                            <div id="previewMeta" class="mt-3 small text-muted"></div>
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
        /* ========== CONSTANTS ========== */
        window.YT_API_KEY = 'AIzaSyDDDs3NwYF7_oNRNq6vIW6IByYMFM3QnDc';

        function getYTApiKey() {
            return (window.YT_API_KEY || '').trim();
        }

        function isLongSelected() {
            return $('input[name="video_type"]:checked').val() === 'long';
        }

        /* ========== PREVIEW HELPERS ========== */
        const $previewArea = $('#previewArea');
        const $previewMeta = $('#previewMeta');

        function showLoading() {
            $previewArea.html(
                '<div class="text-center text-muted d-flex align-items-center justify-content-center w-100">Validating…</div>'
                );
            $previewMeta.empty();
        }

        function showMsg(msg) {
            $previewArea.html(
                `<div class="text-center text-muted d-flex align-items-center justify-content-center w-100">${msg}</div>`
                );
            $previewMeta.empty();
        }

        function truncate(str, n = 120) {
            if (!str) return '';
            return str.length > n ? str.substring(0, n - 3) + '…' : str;
        }

        function setVideoIframe(videoId, title = '', description = '') {
            title = truncate(title);
            description = truncate(description);
            $previewArea.html(`
      <div class="preview-video">
          <iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen frameborder="0"></iframe>
      </div>
  `);
            $previewMeta.html(`<strong class="d-block">${title}</strong><small>${description}</small>`);
        }

        /* ========== YOUTUBE HELPERS ========== */
        function isoDurationToSeconds(iso) {
            const re = /PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/;
            const m = re.exec(iso || '');
            if (!m) return 0;
            return (parseInt(m[1] || 0) * 3600) + (parseInt(m[2] || 0) * 60) + parseInt(m[3] || 0);
        }

        function fetchVideoDetails(ids) {
            const key = getYTApiKey();
            const url =
                `https://www.googleapis.com/youtube/v3/videos?part=contentDetails,snippet&id=${ids.join(',')}&key=${key}`;
            return $.getJSON(url);
        }

        function resolveHandleToChannelId(handle) {
            const key = getYTApiKey();
            const clean = handle.replace(/^@/, '');
            return $.getJSON(`https://www.googleapis.com/youtube/v3/channels?part=id&forHandle=@${clean}&key=${key}`)
                .then(r => r.items?.[0]?.id);
        }

        function fetchLatestVideoFromChannel(channelId, longOnly = false) {
            const key = getYTApiKey();
            const searchUrl =
                `https://www.googleapis.com/youtube/v3/search?key=${key}&channelId=${channelId}&order=date&part=snippet&type=video&maxResults=25`;
            $.getJSON(searchUrl).then(d => {
                const ids = d.items.map(i => i.id.videoId);
                fetchVideoDetails(ids).then(v => {
                    const vids = v.items.map(x => ({
                        id: x.id,
                        dur: isoDurationToSeconds(x.contentDetails.duration),
                        t: x.snippet.title,
                        d: x.snippet.description
                    }));
                    let vid = longOnly ? vids.find(v => v.dur >= 240) : vids[0];
                    if (!vid) {
                        showMsg(longOnly ? 'No long video found.' : 'No videos found.');
                        return;
                    }
                    setVideoIframe(vid.id, vid.t, vid.d);
                });
            });
        }

        /* ========== PARSER ========== */
        function parseYouTube(url) {
            try {
                const u = new URL(url);
                const host = u.hostname.replace(/^www\./, '').toLowerCase();
                const params = Object.fromEntries(u.searchParams.entries());
                const path = u.pathname;
                let videoId = null;
                if (host === 'youtu.be') videoId = path.slice(1).split('/')[0];
                if (!videoId && params.v) videoId = params.v;
                const playlistId = params.list || null;
                const mCh = path.match(/\/channel\/(UC[\w-]{22})/);
                const channelId = mCh ? mCh[1] : null;
                const mH = path.match(/^\/@([\w.\-_]+)/);
                const handle = mH ? mH[1] : null;
                return {
                    videoId,
                    playlistId,
                    channelId,
                    handle
                };
            } catch {
                return {};
            }
        }

        /* ========== VALIDATE BUTTON ========== */
        $(document).on('click', '#validateBtn', function() {
            const url = $('#video_url').val().trim();
            if (!url) {
                showMsg('Please paste a YouTube URL first.');
                return;
            }
            showLoading();
            const {
                videoId,
                playlistId,
                channelId,
                handle
            } = parseYouTube(url);
            if (videoId) {
                fetchVideoDetails([videoId]).then(v => {
                    const item = v.items[0];
                    const dur = isoDurationToSeconds(item.contentDetails.duration);
                    if (isLongSelected() && dur < 240) {
                        showMsg('This is a short video (<4 min).');
                        return;
                    }
                    setVideoIframe(item.id, item.snippet.title, item.snippet.description);
                });
                return;
            }
            if (playlistId) {
                setVideoIframe(`videoseries?list=${playlistId}`);
                return;
            }
            const long = isLongSelected();
            if (channelId) {
                fetchLatestVideoFromChannel(channelId, long);
                return;
            }
            if (handle) {
                resolveHandleToChannelId(handle).then(cid => fetchLatestVideoFromChannel(cid, long));
                return;
            }
            showMsg('Unsupported URL.');
        });
    </script>
@endpush