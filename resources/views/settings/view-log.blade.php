@extends('layouts.master')

@section('content')
<section class="content-body" id="view_log_page">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2 class="me-auto">View Logs</h2>
    </div>
    
    <div class="row justify-content-center">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <!-- Display the log content -->
            <pre style="white-space: pre-wrap; word-wrap: break-word;">{{ $logContent }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
