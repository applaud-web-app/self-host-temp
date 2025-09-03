@extends('layouts.master')
@push('styles')
<style>
    .banner-grids {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 1.5rem;
        padding: 1rem;
    }

    .banner-card {
        position: relative;
        border: 1px dashed #b1b1b1;
        border-radius: 4px;
        transition: transform 0.2s;
        background-color: #f8f9fa;
        padding: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100px;
    }

    .banner-card:hover {
        transform: translateY(-5px);
    }

    .banner-img {
        max-height: 70px;
        max-width: 100%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .banner-img.loaded {
        opacity: 1;
    }

    .btn-danger {
        position: absolute;
        top: -10px;
        right: -10px;
        padding: 5px;
        border-radius: 50%;
        height: 25px;
        width: 25px;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
    }

    #search-banner {
        transition: all 0.3s ease;
    }

    #search-banner:focus {
        width: 300px !important;
    }
</style>
@endpush

@section('content')
    <section class="content-body" id="banners_page">
        <div class="container-fluid position-relative">
            <div class="card">
                <div class="card-header">
                    <h2>Upload Banner</h2>
                </div>
                <div class="card-body">
                    <form id="upload-form" action="{{ route('gallery.banners.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="banners">Select banners (PNG, JPG, JPEG, max 50KB each)</label>
                            <input type="file" class="form-control" name="banners[]" multiple required onchange="validateFileInput(event)">
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="upload-text">Upload Banners</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                    </form>

                    <div class="alerts mt-3">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">All Banners</h2> <span id="banner-count" class="ms-2 badge badge-primary"></span>
                </div>
                <div class="card-body p-4">
                    <div class="banner-grids" id="banners-container"></div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    function validateFileInput(event) {
        const fileInput = event.target;
        const files = fileInput.files;
        let valid = true;
        let errorMessage = '';

        // Loop through each file and check the validation
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileType = file.type;
            const fileSize = file.size;

            // Check if the file is an image and is one of the allowed types
            if (!fileType.match('image.*') || !['image/png', 'image/jpg', 'image/jpeg'].includes(fileType)) {
                valid = false;
                errorMessage = 'Only PNG, JPG, or JPEG files are allowed.';
                break;
            }

            // Check if the file size is less than or equal to 50KB (50 * 1024 bytes)
            if (fileSize > 50 * 1024) {
                valid = false;
                errorMessage = 'File size must be less than 50KB.';
                break;
            }
        }

        // Display an error message with Izitoast if validation fails
        if (!valid) {
            iziToast.error({
                title: 'Error',
                message: errorMessage,
                position: 'topRight', // Position of the toast notification
                timeout: 3000, // Time in milliseconds before the toast disappears
            });

            fileInput.value = ''; // Clear the input to let the user select files again
        }
    }

    let loading = false;
    let observer;

    // Load all banners initially
    loadBanners();

    // Search functionality with debounce
    $('#search-banner').on('input', debounce(function() {
        loadBanners();
    }, 300));

    // Upload form submission with AJAX
    $('#upload-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const $btn = $(this).find('button[type="submit"]');
        
        $btn.prop('disabled', true);
        $btn.find('.upload-text').addClass('d-none');
        $btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('.alerts').html(`<div class="alert alert-success">${response}</div>`);
                loadBanners();
            },
            error: function(xhr) {
                $('.alerts').html(`<div class="alert alert-danger">${xhr.responseText || 'Upload failed'}</div>`);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.upload-text').removeClass('d-none');
                $btn.find('.spinner-border').addClass('d-none');
                $('#upload-form')[0].reset();
            }
        });
    });

    // Delete banner handler
    $(document).on('submit', 'form.delete-banner', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this banner?')) return;

        const $form = $(this);
        const $card = $form.closest('.banner-card');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: $form.find('input[name="_token"]').val()
            },
            success: function() {
                $card.fadeOut(300, function() {
                    $(this).remove();
                    updateBannerCount();
                });
            },
            error: function() {
                alert('Failed to delete banner');
            }
        });
    });

    // Load banners function
    function loadBanners() {
        if (loading) return;
        loading = true;
        
        const searchTerm = "";
        
        $.ajax({
            url: '{{ route("gallery.banners.list") }}',
            type: 'GET',
            data: { search: searchTerm },
            success: function(response) {
                $('#banners-container').empty();

                if (response.banners.length > 0) {
                    response.banners.forEach(function(banner) {
                        const bannerCard = `
                            <div class="banner-card">
                                <img data-src="${banner.url}" class="banner-img" alt="Banner" loading="lazy">
                                <form action="${banner.delete_url}" method="POST" class="delete-banner">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                        $('#banners-container').append(bannerCard);
                    });
                    
                    initLazyLoading();
                } else {
                    $('#banners-container').html('<div class="col-12 text-center py-4"><p>No banners found</p></div>');
                }

                $('#banner-count').text("Total banners : " + response.total);
            },
            complete: function() {
                loading = false;
            }
        });
    }

    // Initialize lazy loading
    function initLazyLoading() {
        if (observer) {
            observer.disconnect();
        }
        
        const lazyImages = document.querySelectorAll('.banner-img');
        
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.onload = () => img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, { rootMargin: '100px' });
            
            lazyImages.forEach(img => observer.observe(img));
        } else {
            // Fallback for older browsers
            lazyImages.forEach(img => {
                img.src = img.dataset.src;
                img.onload = () => img.classList.add('loaded');
            });
        }
    }

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Update banner count
    function updateBannerCount() {
        $('#banner-count').text("Total banners : " + $('#banners-container .banner-card').length);
    }
});
</script>
@endpush