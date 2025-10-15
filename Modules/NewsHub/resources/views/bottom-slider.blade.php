@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" />

    @php
        $color = old('theme_color', $slider->theme_color ?? '#000000');

        // Show only PATH in edit mode too — strip scheme/www/current domain
        $existingFull = $slider->feed_url ?? '';
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

        $mode = old('mode', $slider->mode ?? 'latest');
        $postsCount = old('posts_count', $slider->posts_count ?? 8);
    @endphp

    <style>
        .news-slider {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            border-top: 2px solid {{ $color }};
            z-index: 1111;
            padding: 10px 30px;
            box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.1);
        }

        .slider-item {
            background: #F9F9F9;
            border-radius: 6px;
            padding: 5px;
            display: flex !important;
            align-items: center;
            min-height: 60px;
            margin: 0;
            position: relative;
            border: 1px solid #ddd;
        }

        .slider-item img {
            width: 120px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }

        .slider-text {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
        }

        .slider-text a{
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
        }

        .badge-tag {
            position: absolute;
            bottom: 2px;
            right: 2px;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 3px;
            color: #fff;
        }

        .bottom-close-btn {
            position: absolute;
            top: 2px;
            right: 10px;
            font-size: 22px;
            cursor: pointer;
            color: #666;
            z-index: 10000;
        }

        /* slick arrows */
        .slick-prev,
        .slick-next {
            width: 32px;
            height: 32px;
        }

        .slick-prev:before,
        .slick-next:before {
            display: none;
        }

        .custom-arrow {
            background: {{ $color }};
            color: #fff;
            border: 1px solid {{ $color }};
            width: 25px;
            height: 25px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            transition: all .25s ease;
        }

        .custom-arrow:hover {
            background: transparent;
            color: {{ $color }};
            border: 1px solid {{ $color }};
        }

        @media (max-width:768px) {
            .slider-item img {
                width: 90px;
            }

            .slider-text {
                font-size: 13px;
            }

            .slider-text a{
                font-size: 13px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center text-head mb-3">
                <h2 class="mb-0 me-3">{{ $slider ? 'Edit Bottom Slider' : 'Create Bottom Slider' }}</h2>
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
                    <form id="bottomSliderForm" method="POST" action="{{ route('news-hub.bottom-slider.save') }}"
                        autocomplete="off">
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
                                                placeholder="/feed.xml" pattern="\/.*" value="{{ $feedPath }}"
                                                required>
                                        </div>
                                        <button type="button" id="fetchBtn" class="btn btn-primary" title="Fetch feed">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Type only the path beginning with “/”. Prefixed with
                                        <code>https://{{ $domain->name }}</code>.</small>
                                </div>

                                {{-- Posts --}}
                                <div class="col-lg-4">
                                    <label class="form-label">Posts <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" min="1" max="50" name="posts_count"
                                            id="posts_count" class="form-control" value="{{ $postsCount }}" required>
                                    </div>
                                </div>

                                {{-- Theme color (text + picker) --}}
                                <div class="col-lg-4">
                                    <label class="form-label">Theme Color <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" id="colorCode" class="form-control bg-white"
                                            placeholder="#000000" value="{{ $color }}">
                                        <input type="color" id="colorPicker" name="theme_color" class="form-control p-1"
                                            value="{{ $color }}" required>
                                    </div>
                                </div>

                                {{-- Mode + Count --}}
                                <div class="col-lg-4">
                                    <label class="form-label d-block">Mode <span class="text-danger">*</span></label>
                                    <select class="form-control" name="mode" id="mode">
                                        <option value="latest" {{ $mode === 'latest' ? 'selected' : '' }}>Latest</option>
                                        <option value="random" {{ $mode === 'random' ? 'selected' : '' }}>Random</option>
                                    </select>
                                </div>


                                {{-- desktop/mobile --}}
                                <div class="col-lg-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_desktop"
                                            name="enable_desktop"
                                            {{ old('enable_desktop', $slider->enable_desktop ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enable_desktop">Enable Desktop</label>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_mobile"
                                            name="enable_mobile"
                                            {{ old('enable_mobile', $slider->enable_mobile ?? true) ? 'checked' : '' }}>
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
                <div class="position-relative">
                    <div class="news-slider" id="newsSlider" style="display:none;">
                        <span class="bottom-close-btn"
                            onclick="document.getElementById('newsSlider').style.display='none'">&times;</span>
                        <div class="slick-carousel">
                            <div class="text-muted small">Click “Fetch” to preview {{ (int) $postsCount }}
                                {{ $mode === 'random' ? 'random' : 'latest' }} item(s).</div>
                        </div>
                    </div>
                </div>
                {{-- /Live Preview --}}
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script>
        $(function() {
            // ===== Color sync (hex ↔ picker) + repaint preview border/arrows =====
            const $picker = $('#colorPicker'),
                $code = $('#colorCode'),
                $slider = $('.news-slider');

            function applyColor(hex) {
                $code.val(hex);
                $picker.val(hex);
                $slider.css('border-top-color', hex);
                $('.custom-arrow').css({
                    background: hex,
                    borderColor: hex,
                    color: '#fff'
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

            // ===== jQuery Validate =====
            $.validator.addMethod("startsWithSlash", v => /^\/.+/.test(v), "Must start with '/'.");
            $.validator.addMethod("hexcolor", v => /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(v),
                "Enter a valid hex color.");

            $('#bottomSliderForm').validate({
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
                    mode: {
                        required: true
                    },
                    posts_count: {
                        required: true,
                        number: true,
                        min: 1,
                        max: 50
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
                    $('#saveBtn').prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
                    form.submit();
                }
            });

            // ===== Fetch preview (mode + count) =====
            const $carousel = $('.slick-carousel');

            function destroySlickIfNeeded() {
                if ($carousel.hasClass('slick-initialized')) {
                    $carousel.slick('unslick');
                }
            }

            function initSlick() {
                $carousel.slick({
                    infinite: true,
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    autoplay: false,
                    arrows: true,
                    dots: false,
                    prevArrow: '<button class="slick-prev custom-arrow"><i class="fas fa-chevron-left"></i></button>',
                    nextArrow: '<button class="slick-next custom-arrow"><i class="fas fa-chevron-right"></i></button>',
                    responsive: [{
                            breakpoint: 992,
                            settings: {
                                slidesToShow: 2
                            }
                        },
                        {
                            breakpoint: 576,
                            settings: {
                                slidesToShow: 1
                            }
                        }
                    ]
                });

                $('.slick-carousel .slick-slide').css('padding', '0 6px');
                // tint arrows
                applyColor($('#colorPicker').val());
            }

            $('#fetchBtn').on('click', function() {
              const feedPath = $('#feed_path').val().trim();
              if (!feedPath) {
                  alert('Please enter a feed path (starts with "/").');
                  return;
              }

              const mode = $('#mode').val() || 'latest'; // <-- fix: use select value
              const count = $('#posts_count').val() || 8;

              const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
              const params = new URLSearchParams({
                  _token: '{{ csrf_token() }}',
                  eq: '{{ $eq ?? request('eq') }}',
                  feed_path: feedPath,
                  mode: mode,
                  count: count
              });

              fetch(`{{ route('news-hub.fetch.feed') }}`, {
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
                      const items = data.items || [];
                      if (!items.length) {
                          html = '<div class="text-muted small">No items found for this feed.</div>';
                      } else {
                          const colors = ["#007BFF", "#28A745", "#E83E8C", "#FD7E14", "#6F42C1"];
                          const rColor = () => colors[Math.floor(Math.random() * colors.length)];
                          items.forEach(it => {
                                const img = it.image || 'https://via.placeholder.com/120x80?text=No+Image';
                                let t = it.title || 'Untitled';
                                if (t.length > 80) {
                                    t = t.substring(0, 80) + '…';
                                }
                              html += `
                                <div class="slider-item">
                                  <img src="${esc(img)}" alt="news">
                                  <div class="slider-text">
                                    <a href="${esc(it.link||'#')}" target="_blank" rel="noopener">${esc(t)}</a>
                                  </div>
                                  <span class="badge-tag" style="background:${rColor()}">${esc(it.category||'NEWS')}</span>
                                </div>`;
                          });
                      }
                  } else {
                      html = `<div class="text-danger small">${esc(data.message || 'Could not fetch feed items.')}</div>`;
                  }

                  destroySlickIfNeeded();
                  $('.slick-carousel').html(html);

                  // ✅ show preview only if data loaded
                  $('#newsSlider').show();

                  if (data.status && (data.items || []).length) initSlick();
              })
              .catch(() => {
                  destroySlickIfNeeded();
                  $('.slick-carousel').html('<div class="text-danger small">Network error while fetching feed.</div>');
                  $('#newsSlider').show(); // still show with error
              })
              .finally(() => {
                  $btn.prop('disabled', false).html('<i class="fas fa-undo"></i>');
              });
          });


            function esc(s) {
                return (s || '').toString()
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            // Initial tint
            applyColor($('#colorPicker').val());
        });
    </script>
@endpush