@extends('layouts.master')

@push('styles')
    <style>
        .sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 100px;
        }

        #randomFeed {
            display: none;
        }

        .feed-item {
            display: flex;
            align-items: flex-start;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #dcdcdc;
            transition: 1s;
        }

        .feed-item:hover {
            background: #dcdcdc;
            transition: 1s;
        }

        .feed-item .preview-image {
            flex: 0 0 80px;
            margin-right: 12px;
        }

        .feed-item .preview-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .feed-item .feed-content {
            flex: 1;
        }

        .feed-item .feed-content h5 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .feed-item .feed-content p {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
            color: #555;
        }

        .error {
            font-size: 14px;
            color: #ff0000;
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="mb-sm-3 d-flex flex-wrap align-items-center text-head">
                <h2 class="me-auto">RSS Feed</h2>
            </div>
            <form action="#" id="rss_create_static" method="post">
                <div class="row">
                    <div class="col-lg-7 col-md-7 col-12">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title fs-20">Get Feed</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="rssfeedname" class="form-label">Feed Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="rssfeedname" id="rssfeedname" placeholder="Enter Feed Name" maxlength="100" value="Sample Feed" required>
                                            <div class="invalid-feedback">Please provide Feed Name.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="rssFeedUrl" class="form-label">Feed URL <small class="text-muted">(URL with https or http)</small><span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="rssFeedUrl" id="rssFeedUrlLink" placeholder="Enter the Feed Url" value="https://example.com/sample-feed.xml" required>
                                            <div id="message" class="error"></div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary w-100" id="fetchRssData">Fetch Feed</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12" id="feedPreview">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title fs-20 mb-0">Feed Preview</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="feed-container"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12" id="feedNotifyData">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="campaign_name">Feed Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control emoji_picker notification" name="campaign_name" id="campaign_name" value="" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label for="description">Notification Message <span class="text-danger">*</span></label>
                                            <textarea class="form-control emoji_picker notification" name="description" id="notification_description" disabled style="height: 100px;"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Landing Page URL <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="notification_link" value="" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12" id="notifyImages">
                                <div class="card h-auto">
                                    <div class="card-header">
                                        <h4 class="card-title fs-20 mb-0">Notifications Image</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12 mb-3">
                                                <h5 class="fs-18">Banner Image</h5>
                                                <div class="userprofile">
                                                    <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="banner_image" alt="" class="img-fluid upimage">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="image_input" name="bannerimage" placeholder="e.g: https://example.com/image.jpg" readonly value="" onchange="changeBanner(this.value)">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 mb-3">
                                                <h5 class="fs-18">Banner Icon</h5>
                                                <div class="userprofile">
                                                    <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="banner_icon" alt="" class="img-fluid upimage">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="icons" id="target" placeholder="Select Icon" value="" onchange="prv_icons(this.value)">
                                                        <button class="input-group-text d-none" type="button" id="button2-reset" onclick="resetIcon()">Reset</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-12">
                        <div id="stickyElement" class="sticky">
                            <div class="card h-auto">
                                <div class="card-body p-3">
                                    <div class="custom-radio justify-content-start mb-3">
                                        <label class="me-3"><input type="radio" name="preview_type" id="preview_windows" value="preview_windows" checked> <span>Windows</span></label>
                                        <label><input type="radio" name="preview_type" id="preview_android" value="preview_android"> <span>Android</span></label>
                                    </div>
                                    <div class="windows_view">
                                        <img src="" id="message_image" class="feat_img message_image img-fluid mb-3" alt="" style="display: none;">
                                        <div class="windows_body">
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="{{ asset('images/chrome.png') }}" class="me-2" alt="Chrome">
                                                <span>Google Chrome</span>
                                                <i class="far fa-window-close ms-auto"></i>
                                            </div>
                                            <div class="preview_content d-flex align-items-start mb-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <img src="" id="icon_prv" class="img-fluid" alt="Icon Preview">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-bold fs-13" id="prv_title"></span>
                                                    <p class="card-text mb-2" id="prv_desc"></p>
                                                    <span class="fw-light text-primary" id="prv_link"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="android_view" style="display: none;">
                                        <div class="android_body mt-3">
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="{{ asset('images/chrome.png') }}" class="me-2" alt="Chrome">
                                                <span>Google Chrome</span>
                                                <span class="ms-auto"><i class="far fa-chevron-circle-down fa-lg"></i></span>
                                            </div>
                                            <div class="preview_content d-flex align-items-center mb-3">
                                                <div class="flex-grow-1">
                                                    <span class="fs-16 text-black prv_title" id="prv_title"></span>
                                                    <p class="card-text fs-14 prv_desc" id="prv_desc"></p>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <img src="" id="icon_prv" class="img-fluid" alt="Icon Preview">
                                                </div>
                                            </div>
                                            <img src="" id="message_image" class="feat_img message_image img-fluid mt-3" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" disabled><i class="far fa-save pe-2"></i>Save & Exit</button>
                    <button type="reset" class="btn btn-secondary ms-2"><i class="far fa-window-close pe-2"></i>Reset</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-20" id="staticBackdropLabel">Select Icons</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap" id="iconpreview"></div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const fetchButton = document.getElementById('fetchRssData');
            const feedUrlInput = document.getElementById('rssFeedUrlLink');
            const messageDiv = document.getElementById('message');
            const feedContainer = document.getElementById('feed-container');
            const feedPreviewSection = document.getElementById('feedPreview');
            const feedNotifyDataSection = document.getElementById('feedNotifyData');
            const notifyImagesSection = document.getElementById('notifyImages');
            const prvTitle = document.getElementById('prv_title');
            const prvDesc = document.getElementById('prv_desc');
            const prvLink = document.getElementById('prv_link');
            const messageImage = document.getElementById('message_image');
            const bannerImage = document.getElementById('banner_image');
            const iconPrv = document.getElementById('icon_prv');
            const bannerIcon = document.getElementById('banner_icon');

            fetchButton.addEventListener('click', function() {
                const url = feedUrlInput.value.trim();
                if (!url) {
                    messageDiv.textContent = 'Feed URL cannot be empty!';
                    return;
                }
                messageDiv.textContent = '';
                fetchButton.disabled = true;
                fetchButton.textContent = 'Loading...';

                fetch(url)
                    .then(response => response.text())
                    .then(str => {
                        const parser = new DOMParser();
                        const xml = parser.parseFromString(str, 'application/xml');
                        const items = xml.querySelectorAll('item');
                        feedContainer.innerHTML = '';

                        if (items.length === 0) {
                            messageDiv.textContent = 'No items found or invalid RSS feed.';
                        } else {
                            items.forEach(function(item, index) {
                                const title = item.querySelector('title') ? item.querySelector('title').textContent : 'No title';
                                const descriptionNode = item.querySelector('description');
                                const descText = descriptionNode ? descriptionNode.textContent : '';
                                const linkNode = item.querySelector('link');
                                const link = linkNode ? linkNode.textContent : '';
                                let image = '';
                                const enclosure = item.querySelector('enclosure[url]');
                                if (enclosure) {
                                    image = enclosure.getAttribute('url');
                                }
                                if (!image) {
                                    const mediaContent = item.querySelector('media\:content, content[url]');
                                    if (mediaContent) {
                                        image = mediaContent.getAttribute('url');
                                    }
                                }
                                if (!image) {
                                    image = 'https://via.placeholder.com/80';
                                }
                                const plainDesc = descText.replace(/<[^>]+>/g, '');
                                const truncated = plainDesc.length > 100 ? plainDesc.substring(0, 100) + '…' : plainDesc;
                                const div = document.createElement('div');
                                div.classList.add('feed-item');
                                div.innerHTML = `
                                    <div class="preview-image"><img src="${image}" alt="Feed image"></div>
                                    <div class="feed-content"><h5>${title}</h5><p>${truncated}</p></div>
                                `;
                                feedContainer.appendChild(div);

                                if (index === 0) {
                                    // first item fills preview
                                    prvTitle.textContent = title;
                                    prvDesc.textContent = descText;
                                    prvLink.textContent = link;
                                    messageImage.src = image;
                                    bannerImage.src = image;
                                    iconPrv.src = image;
                                    bannerIcon.src = image;
                                }
                            });
                            feedPreviewSection.style.display = '';
                            feedNotifyDataSection.style.display = '';
                            notifyImagesSection.style.display = '';
                        }
                    })
                    .catch(err => {
                        messageDiv.textContent = 'Failed to load RSS feed. Please try again.';
                        console.error('Error parsing RSS:', err);
                    })
                    .finally(() => {
                        fetchButton.disabled = false;
                        fetchButton.textContent = 'Fetch Feed';
                    });
            });

            // Preview toggle
            document.querySelectorAll('input[name="preview_type"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'preview_windows') {
                        document.querySelector('.windows_view').style.display = '';
                        document.querySelector('.android_view').style.display = 'none';
                    } else {
                        document.querySelector('.windows_view').style.display = 'none';
                        document.querySelector('.android_view').style.display = '';
                    }
                });
            });
        });
    </script>
@endpush
