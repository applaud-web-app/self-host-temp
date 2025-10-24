@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
    <style>
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .card-footer {
            text-align: center;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 0 0 10px 10px;
        }
        /* .pie-chart-container {
            width: 100%;
            height: 350px;
            margin-top: 30px;
        } */
    </style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="text-head mb-3 d-flex align-items-center">
            <h2 class="me-auto mb-0">Migrate Subscribers Overview</h2>
            <a href="{{ route('mig.task-tracker') }}" target="_blank" class="btn btn-primary btn-sm">Task Tracker</a>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Domain Dropdown -->
                        <div class="mb-4">
                            <label for="domain-select" class="form-label fw-semibold">Domain</label>
                            <select id="domain-select" class="form-control" name="domain_id" required>
                                <option value="">Select Domain</option>
                            </select>
                            <div class="form-text">Choose the domain to associate the subscribers with.</div>
                        </div>

                        <!-- Subscriber Count Cards -->
                        <div class="row">
                            <div class="col-lg-12 text-end mb-3">
                                <button class="btn btn-primary" id="validateSubscriber">Validate Subscriber</button>
                                <button class="btn btn-secondary" id="refreshButton" style="display: none;">Refresh</button>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Subscribers</h5>
                                        <p id="total-subscribers">0</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Migrated Subscribers</h5>
                                        <p id="migrated-subscribers">0</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Failed Subscribers</h5>
                                        <p id="failed-subscribers">0</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie Chart -->
                        {{-- <div class="pie-chart-container">
                            <canvas id="migrationPieChart"></canvas>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // --------- Select2 Domain Dropdown with AJAX ----------
        $('#domain-select').select2({
            placeholder: 'Search for Domain…',
            allowClear: true,
            ajax: {
                url: "{{ route('domain.domain-list') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term || '' }),
                processResults: r => ({
                    results: r.data.map(i => ({
                        id: i.id,
                        text: i.text
                    }))
                }),
                cache: true
            },
            templateResult: d => d.loading ? d.text : $(`<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
            escapeMarkup: m => m
        });

        // Handle Domain Selection Change and Fetch Data for Subscribers
        $('#domain-select').on('change', function() {
            const domainId = $(this).val();
            if (!domainId) return;

            // Make an AJAX call to fetch subscriber data
            $.ajax({
                url: "{{ route('mig.fetch-migrate-data') }}", // Route for fetching data
                method: 'GET',
                data: { domain_id: domainId },
                success: function(response) {
                    if(response.success) {
                        // Update text values in the cards
                        $('#total-subscribers').text(response.data.totalSubscribers);
                        $('#migrated-subscribers').text(response.data.migratedSubscribers);
                        $('#failed-subscribers').text(response.data.failedSubscribers);

                        // Prepare data for Pie Chart
                        // const pieChartData = {
                        //     labels: ['Migrated', 'Failed', 'Pending'],
                        //     datasets: [{
                        //         data: [
                        //             response.data.migratedSubscribers,
                        //             response.data.failedSubscribers,
                        //             response.data.pendingSubscribers
                        //         ],
                        //         backgroundColor: ['#4CAF50', '#FF5722', '#FFC107'],
                        //         hoverBackgroundColor: ['#388E3C', '#F44336', '#FF9800'],
                        //     }]
                        // };

                        // // Create the Pie Chart
                        // const ctx = document.getElementById('migrationPieChart').getContext('2d');
                        // new Chart(ctx, {
                        //     type: 'pie',
                        //     data: pieChartData,
                        //     options: {
                        //         responsive: true,
                        //         plugins: {
                        //             legend: {
                        //                 position: 'top',
                        //             }
                        //         }
                        //     }
                        // });
                    } else {
                        iziToast.error({ title: 'Error', message: 'Failed to fetch subscriber data.', position: 'topRight' });
                    }
                },
                error: function(xhr, status, error) {
                    iziToast.error({ title: 'Error', message: 'Failed to fetch subscriber data.', position: 'topRight' });
                }
            });
        });

        // Validate Subscribers - Trigger Job for Validation
        $('#validateSubscriber').on('click', function() {
            const domainId = $('#domain-select').val();
            if (!domainId) {
                iziToast.error({ title: 'Error', message: 'Please select a domain first.', position: 'topRight' });
                return;
            }

            // Disable the button and start validation
            $(this).attr('disabled', true).text('Validating...');

            // Trigger validation job
            $.ajax({
                url: "{{ route('mig.validate-migrate-subs') }}",
                method: 'POST',
                data: {
                    domain_id: domainId,
                    _token: '{{ csrf_token() }}'  // Adding CSRF token here
                },
                success: function(response) {
                    if(response.success) {
                        iziToast.success({ title: 'Success', message: 'Subscriber validation started.', position: 'topRight' });
                        $('#refreshButton').show(); // Show Refresh button
                    } else {
                        // Show error message returned from controller if validation failed
                        iziToast.error({ title: 'Error', message: response.message || 'Failed to validate subscribers.', position: 'topRight' });
                    }
                },
                error: function(xhr, status, error) {
                    // Show the error message returned from the controller
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred while validating subscribers.';
                    iziToast.error({ title: 'Error', message: errorMessage, position: 'topRight' });
                },
                complete: function() {
                    // Enable the Validate button again after 5 minutes
                    setTimeout(() => {
                        $('#validateSubscriber').attr('disabled', false).text('Validate Subscriber');
                    }, 1000);
                }
            });
        });


        // Refresh button to reload the data
        $('#refreshButton').on('click', function() {
            const domainId = $('#domain-select').val();
            if (!domainId) return;

            // Trigger the same logic to refresh subscriber data
            $.ajax({
                url: "{{ route('mig.fetch-migrate-data') }}",
                method: 'GET',
                data: { domain_id: domainId },
                success: function(response) {
                    if(response.success) {
                        // Update text values in the cards
                        $('#total-subscribers').text(response.data.totalSubscribers);
                        $('#migrated-subscribers').text(response.data.migratedSubscribers);
                        $('#failed-subscribers').text(response.data.failedSubscribers);
                    }
                }
            });
        });
    </script>
@endpush