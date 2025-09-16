{{-- resources/views/newshub/roll.blade.php --}}
@extends('layouts.master')

@push('styles')
@php
    $color = old('theme_color', $roll->theme_color ?? '#fd683e');
    $icon  = old('icon', $roll->icon ?? 'fa fa-bell');

    // Show only PATH in the input for edit mode too
    $existingFull = $roll->feed_url ?? '';
    $existingPath = $existingFull
        ? '/' . ltrim(preg_replace('#^https?://domain\.in#i', '', $existingFull), '/')
        : '';
    $feedPath = old('feed_path', $existingPath);
@endphp

<style>
    /* Preview widget */
    .wid-fixed-container{
        position:absolute;
        bottom:20px; right:20px;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji";
        z-index: 10;
    }
    .wid-fixed-icon{
        background-color: {{ $color }};
        color:#fff;
        padding:10px;
        border-radius:50%;
        box-shadow:0 4px 8px rgba(0,0,0,.2);
        cursor:pointer;
        width:56px; height:56px;
        line-height:36px;
        font-size:24px;
        text-align:center;
        display:block;
    }
    .wid-fixed-icon:hover{ opacity:.9;color: #ffffff; }

    .wid-fixed-card{
        background:#fff;
        width:350px;
        border:1px solid {{ $color }};
        border-radius:12px;
        overflow:hidden;
        margin-bottom:10px;
        display:none; /* closed by default */
    }
    .wid-fixed-card .wid-fixed-header{
        background-color: {{ $color }};
        color:#fff;
        padding:10px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }
    .wid-fixed-card .wid-fixed-header h5{
        color:#fff; font-size:18px; margin:0;
    }
    .wid-fixed-card .close-button{
        background:none; border:none; color:#fff; cursor:pointer;
        font-size:16px; padding:0; margin:0;
    }
    .wid-fixed-card .wid-fixed-body{ padding:14px; }
    .smallnote{ font-size:10px; color:#ffffff; }
</style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center text-head mb-3">
            <h2 class="mb-0 me-3">{{ $roll ? 'Edit News Roll' : 'Create News Roll' }}</h2>
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
            <div class="col-lg-8">
                <form method="POST" action="{{ route('news-hub.roll.save') }}" autocomplete="off" id="rollForm">
                    @csrf
                    {{-- send eq instead of trusting domain_id --}}
                    <input type="hidden" name="eq" value="{{ $eq }}"/>

                    <div class="card">
                        <div class="card-body row g-4">
                            <div class="col-lg-12">
                                <label class="form-label">Feed Path (after base) <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <div class="input-group">
                                        <span class="input-group-text bg-primary text-white">https://{{$domain->name}}</span>
                                        <input type="text" name="feed_path" class="form-control" placeholder="/feed.xml" pattern="\/.*" value="{{ $feedPath }}" required>
                                    </div>
                                    <button type="button" id="fetchFeedBtn" class="btn btn-primary">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Type only the path beginning with “/”. We’ll prefix <code>https://domain.in</code>.</small>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control"
                                       placeholder="News Updates"
                                       value="{{ old('title', $roll->title ?? '') }}" required>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Icon</label>
                                @php $iconValue = old('icon', $roll->icon ?? 'fa fa-bell'); @endphp
                                <select name="icon" id="widget_icon" class="form-control form-select">
                                    <option value="fa fa-bell"      {{ $iconValue==='fa fa-bell' ? 'selected' : '' }}>Bell</option>
                                    <option value="fa fa-bullhorn"  {{ $iconValue==='fa fa-bullhorn' ? 'selected' : '' }}>Bullhorn</option>
                                    <option value="fa fa-newspaper" {{ $iconValue==='fa fa-newspaper' ? 'selected' : '' }}>Newspaper</option>
                                    <option value="fa fa-star"      {{ $iconValue==='fa fa-star' ? 'selected' : '' }}>Star</option>
                                    <option value="fa fa-heart"     {{ $iconValue==='fa fa-heart' ? 'selected' : '' }}>Heart</option>
                                </select>
                                <small class="text-muted">Saved as a Font Awesome class.</small>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Widget Placement <span class="text-danger">*</span></label>
                                @php $wp = old('widget_placement', $roll->widget_placement ?? 'bottom-right'); @endphp
                                <select name="widget_placement" id="placement" class="form-control" required>
                                    <option value="top-left"     {{ $wp==='top-left' ? 'selected' : '' }}>Top Left</option>
                                    <option value="top-right"    {{ $wp==='top-right' ? 'selected' : '' }}>Top Right</option>
                                    <option value="bottom-left"  {{ $wp==='bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                                    <option value="bottom-right" {{ $wp==='bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Theme Color <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="colorCode" class="form-control bg-white"
                                           placeholder="#fd683e"
                                           value="{{ old('theme_color', $roll->theme_color ?? '#fd683e') }}">
                                    <input type="color" id="colorPicker" name="theme_color"
                                           class="form-control p-1"
                                           value="{{ old('theme_color', $roll->theme_color ?? '#fd683e') }}">
                                </div>
                                <small class="text-muted">Use hex or named colors; both will sync.</small>
                            </div>

                            <div class="col-lg-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_on_desktop" name="show_on_desktop"
                                           {{ old('show_on_desktop', ($roll->show_on_desktop ?? 1)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_on_desktop">Show on Desktop</label>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_on_mobile" name="show_on_mobile"
                                           {{ old('show_on_mobile', ($roll->show_on_mobile ?? 1)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_on_mobile">Show on Mobile</label>
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

            {{-- Live Preview --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Live Preview</h4>
                    </div>
                    <div class="card-body position-relative">
                        <div class="wid-fixed-container" id="wid-fixedContainer">
                            <div class="wid-fixed-card" id="wid-fixedCard">
                                <div class="wid-fixed-header">
                                    <div></div>
                                    <div class="text-center">
                                        <h5 class="wid-fixed-title" id="widgetTitle">{{ old('title', $roll->title ?? 'News Roll') }}</h5>
                                        <small class="smallnote">Powered by Aplu.io</small>
                                    </div>
                                    <button class="close-button" id="closeButton" type="button" aria-label="Close">&times;</button>
                                </div>
                                <div class="wid-fixed-body">
                                    <div id="feedItems">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="{{ $icon }} mt-1"></i>
                                            <div>
                                                <div class="fw-semibold">Latest headlines</div>
                                                <div class="text-muted small">Your feed items will appear here…</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="javascript:void(0)" class="wid-fixed-icon {{ $icon }}" id="wid-fixedIcon" aria-label="Open widget"></a>
                        </div>
                    </div>
                </div>
            </div>
            {{-- /Live Preview --}}
        </div>
    </div>
</section>
@endsection

@push('scripts')
{{-- Make sure CSRF token meta exists in your base layout: <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
<script>
(function(){
    // Open/close preview card
    const card  = document.getElementById('wid-fixedCard');
    const icon  = document.getElementById('wid-fixedIcon');
    const close = document.getElementById('closeButton');
    icon.addEventListener('click', () => { card.style.display = (card.style.display === 'block') ? 'none' : 'block'; });
    close.addEventListener('click', () => card.style.display = 'none');
})();

(function(){
    // Color sync (hex or named -> hex)
    const picker = document.getElementById('colorPicker');
    const code   = document.getElementById('colorCode');
    function apply(colorHex){
        code.value   = colorHex;
        picker.value = colorHex;
        document.querySelector('.wid-fixed-icon').style.backgroundColor = colorHex;
        document.querySelector('.wid-fixed-card').style.borderColor     = colorHex;
        document.querySelector('.wid-fixed-card .wid-fixed-header').style.backgroundColor = colorHex;
    }
    function toHex(maybe){
        if (/^#[0-9A-F]{6}$/i.test(maybe)) return maybe;
        if (/^#[0-9A-F]{3}$/i.test(maybe)) {
            return '#' + maybe[1]+maybe[1] + maybe[2]+maybe[2] + maybe[3]+maybe[3];
        }
        const tmp = document.createElement('span');
        tmp.style.color = maybe;
        document.body.appendChild(tmp);
        const rgb = getComputedStyle(tmp).color;
        document.body.removeChild(tmp);
        const m = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!m) return null;
        const r = (+m[1]).toString(16).padStart(2,'0');
        const g = (+m[2]).toString(16).padStart(2,'0');
        const b = (+m[3]).toString(16).padStart(2,'0');
        return '#'+r+g+b;
    }
    picker.addEventListener('input', e => apply(e.target.value));
    code.addEventListener('input', e => {
        const hex = toHex(e.target.value.trim());
        if (hex) apply(hex);
    });
})();

(function(){
    // Title live update
    const input = document.getElementById('title');
    const out   = document.getElementById('widgetTitle');
    if (input) input.addEventListener('input', e => out.textContent = e.target.value || 'News Roll');
})();

(function(){
    // Icon live update
    const select = document.getElementById('widget_icon');
    const iconBtn = document.getElementById('wid-fixedIcon');
    if (select && iconBtn) {
        select.addEventListener('change', e => {
            iconBtn.className = 'wid-fixed-icon ' + e.target.value;
        });
    }
})();

(function(){
    // Placement live update
    const placement = document.getElementById('placement');
    const wrap      = document.getElementById('wid-fixedContainer');
    function setPos(val){
        wrap.style.top = wrap.style.right = wrap.style.bottom = wrap.style.left = '';
        switch(val){
            case 'top-left':     wrap.style.top='20px';    wrap.style.left='20px';   break;
            case 'top-right':    wrap.style.top='20px';    wrap.style.right='20px';  break;
            case 'bottom-left':  wrap.style.bottom='20px'; wrap.style.left='20px';   break;
            case 'bottom-right': wrap.style.bottom='20px'; wrap.style.right='20px';  break;
        }
    }
    if (placement) {
        placement.addEventListener('change', e => setPos(e.target.value));
        setPos(placement.value);
    }
})();

// ---- Fetch button logic (AJAX) ----
(function(){
    const fetchBtn = document.getElementById('fetchFeedBtn');
    const form     = document.getElementById('rollForm');
    const itemsBox = document.getElementById('feedItems');
    const card     = document.getElementById('wid-fixedCard');

    function esc(s){
        return (s || '').toString()
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    }

    function render(items){
        if (!items || !items.length) {
            itemsBox.innerHTML = '<div class="text-muted small">No items found.</div>';
            return;
        }
        let html = '';
        items.slice(0,5).forEach(it => {
            html += `
            <div class="d-flex align-items-start gap-2 mb-3">
                <img src="${esc(it.image)}" alt="" style="width:28px;height:28px;border-radius:6px;object-fit:cover;">
                <div>
                    <a href="${esc(it.link)}" target="_blank" rel="noopener" class="fw-semibold d-block">${esc(it.title)}</a>
                    <div class="text-muted small">${esc(it.description).slice(0,140)}${it.description.length>140?'…':''}</div>
                </div>
            </div>`;
        });
        itemsBox.innerHTML = html;
    }

    fetchBtn?.addEventListener('click', async () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const fd = new FormData(form);
        const payload = new URLSearchParams({
            eq: fd.get('eq'),
            feed_path: fd.get('feed_path') || '',
            feed_type: 'all'
        });

        fetchBtn.disabled = true;
        fetchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>';

        try {
            const res = await fetch(`{{ route('news-hub.fetch.feed') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: payload.toString()
            });
            const data = await res.json();
            if (data.status) {
                render(data.items || []);
                card.style.display = 'block'; // open preview
            } else {
                itemsBox.innerHTML = `<div class="text-danger small">${esc(data.message || 'Failed to fetch feed')}</div>`;
                card.style.display = 'block';
            }
        } catch (e) {
            itemsBox.innerHTML = `<div class="text-danger small">Network error while fetching feed.</div>`;
            card.style.display = 'block';
        } finally {
            fetchBtn.disabled = false;
            fetchBtn.innerHTML = '<i class="fas fa-undo"></i>';
        }
    });
})();
</script>
@endpush