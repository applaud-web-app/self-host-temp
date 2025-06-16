<?php /* Segmentation – create page with Add More button above geo rows */ ?>
@extends('layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
@endpush

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2 class="mb-0">Add New Segment</h2>
      <a href="{{ route('segmentation.index') }}" class="btn btn-secondary ms-auto">
        <i class="fas fa-arrow-left me-1"></i>Back to List
      </a>
    </div>
    <div class="row">
      <!-- FORM COLUMN -->
      <div class="col-lg-8">
        <form id="segmentationForm" method="POST" action="#">
          @csrf
          <input type="hidden" name="segment_type" id="segment_type">

          <!-- SEGMENT BASICS -->
          <div class="card mb-3">
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Segment Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="segment_name" placeholder="Enter segment name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Select Domain <span class="text-danger">*</span></label>
                <select class="form-select" id="domain-select" name="domain_name">
                  <option value="">Choose domain…</option>
                  <option>example.com</option>
                  <option>shop.com</option>
                  <option>saas.io</option>
                  <option>events.io</option>
                </select>
              </div>
            </div>
          </div>

          <!-- CONDITION MENU -->
          <div class="card mb-3" id="condition-screen">
            <div class="card-body">
              <h4 class="mb-3">Subscribers who match the following condition</h4>
              <div class="list-group">
                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                  <div>
                    <i class="fal fa-desktop pe-1"></i> <strong>Device-based Targeting</strong><p class="mb-0 small">Target subscribers coming in from Mobile, Desktop or Tablet.</p>
                  </div>
                  <button type="button" class="btn btn-outline-secondary show-card" data-target="device-card">+</button>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                  <div>
                    <i class="fal fa-map-marker-alt pe-1"></i> <strong>Geo-Based Targeting</strong><p class="mb-0 small">Location from which the user subscribed.</p>
                  </div>
                  <button type="button" class="btn btn-outline-secondary show-card" data-target="geo-card">+</button>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                  <div>
                    <i class="fal fa-link pe-1"></i> <strong>URL-Based Segmentation</strong><p class="mb-0 small">Segment subscribers based on the URL.</p>
                  </div>
                  <button type="button" class="btn btn-outline-secondary show-card" data-target="url-card">+</button>
                </div>
              </div>
            </div>
          </div>

          <!-- DEVICE CARD -->
          <div class="card mb-3 card-targeting" id="device-card" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Device-based Targeting</h5>
              <button type="button" class="btn btn-link text-danger delete-card"><i class="fas fa-trash-alt"></i></button>
            </div>
            <div class="card-body">
              <select class="form-select" id="devicetype" name="devicetype[]" multiple>
                <option>Desktop</option>
                <option>Tablet</option>
                <option>Mobile</option>
                <option>Other</option>
              </select>
            </div>
          </div>

          <!-- GEO CARD -->
          <div class="card mb-3 card-targeting" id="geo-card" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Geo-based Targeting</h5>
              <button type="button" class="btn btn-link text-danger delete-card"><i class="fas fa-trash-alt"></i></button>
            </div>
            <div class="card-body">
             
              <div  id="geo-container">

            

              <!-- Hidden template -->
              <template id="geo-template">
                <div class="row g-3 geo-row">
                  <div class="col-lg-3 mb-3">
                    <select class="form-select" name="geo_type[]">
                      <option value="equals">Only</option>
                      <option value="not_equals">Without</option>
                    </select>
                  </div>
                  <div class="col-lg-4 mb-3">
                    <select class="form-select" name="country[]">
                      <option value="">Country…</option>
                      <option>India</option>
                      <option>United States</option>
                      <option>United Kingdom</option>
                      <option>Canada</option>
                    </select>
                  </div>
                  <div class="col-lg-4 mb-3">
                    <select class="form-select" name="state[]">
                      <option value="">State (optional)…</option>
                      <option>Maharashtra</option>
                      <option>California</option>
                      <option>Ontario</option>
                    </select>
                  </div>
                  <div class="col-lg-1 mb-3">
                    <button type="button" class="btn btn-danger remove-geo-row">&times;</button>
                  </div>
                </div>
              </template>

              <!-- Initial geo-row -->
              <div class="row g-3 geo-row">
                <div class="col-lg-3 mb-3">
                  <select class="form-select" name="geo_type[]">
                    <option value="equals">Only</option>
                    <option value="not_equals">Without</option>
                  </select>
                </div>
                <div class="col-lg-4 mb-3">
                  <select class="form-select" name="country[]">
                    <option value="">Country…</option>
                    <option>India</option>
                    <option>United States</option>
                    <option>United Kingdom</option>
                    <option>Canada</option>
                  </select>
                </div>
                <div class="col-lg-4 mb-3">
                  <select class="form-select" name="state[]">
                    <option value="">State (optional)…</option>
                    <option>Maharashtra</option>
                    <option>California</option>
                    <option>Ontario</option>
                  </select>
                </div>
                <div class="col-lg-1 mb-3">
                  <button type="button" class="btn btn-danger remove-geo-row">&times;</button>
                </div>
              </div>

              </div>
               <!-- Add More button ABOVE rows -->
              <button type="button" class="btn btn-secondary btn-sm " id="add-geo-row">
               + Add More
              </button>

              
            </div>
          </div>

          <!-- URL CARD -->
          <div class="card mb-3 card-targeting" id="url-card" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">URL-based Segmentation</h5>
              <button type="button" class="btn btn-link text-danger delete-card"><i class="fas fa-trash-alt"></i></button>
            </div>
            <div class="card-body">
              <select class="form-select" id="url_segment" name="url_segment[]" multiple>
                <option value="/">/ (Homepage)</option>
                <option value="/blog/*">/blog/*</option>
                <option value="/product/*">/product/*</option>
                <option value="/checkout">/checkout</option>
                <option value="/about">/about</option>
                <option value="/contact">/contact</option>
              </select>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100">Create Segment</button>
        </form>
      </div>

      <!-- SIDEBAR COLUMN -->
      <div class="col-lg-4">
        <div class="card h-auto mb-3 text-center">
          <div class="card-header bg-primary "><h4 class="mb-0 text-white">Total Audience</h4></div>
          <div class="card-body">
            <p class="display-5 fw-bold mb-3" id="count">0</p>
            <button id="checkInfo" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-redo-alt"></i> Refresh Data</button>
            <p class="text-success mt-2 mb-0 small">Segmentation demo counts are static.</p>
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
$(function(){
  // Init Select2
  $('#domain-select, #devicetype, #url_segment, #geo-container select').select2({ placeholder:'Choose…', allowClear:true });

  // Show/hide cards
  $('.show-card').click(function(){
    const tgt = $(this).data('target');
    $('#condition-screen, .card-targeting').hide();
    $('#' + tgt).show();
    $('#segment_type').val(tgt.replace('-card',''));  
  });
  $('.delete-card').click(function(){
    $('#condition-screen').show();
    $(this).closest('.card-targeting').hide();
    $('#segment_type').val('');
  });

  // Add More Geo
  $('#add-geo-row').click(function(){
    const rows = $('#geo-container .geo-row');
    if(rows.length >= 5) return alert('Max 5 conditions');
    const tpl = document.getElementById('geo-template').content.cloneNode(true);
    $('#geo-container').append(tpl);
    $('#geo-container .geo-row:last select').select2({ placeholder:'Choose…', allowClear:true });
  });

  // Remove Geo row
  $(document).on('click','.remove-geo-row', function(){
    const rows = $('#geo-container .geo-row');
    if(rows.length>1) $(this).closest('.geo-row').remove();
    else rows.find('select').val(null).trigger('change');
  });

  // Refresh Demo
  $('#checkInfo').click(function(){
    $(this).prop('disabled',true);
    $('#count').text('…');
    setTimeout(()=>{
      $('#count').text(Math.floor(Math.random()*5000)+100);
      $(this).prop('disabled',false);
    },1000);
  });

  // Submit Demo
  $('#segmentationForm').submit(function(e){
    e.preventDefault();
    alert('Segment saved (demo)!');
    window.location='{{ route("segmentation.index") }}';
  });
});
</script>
@endpush
