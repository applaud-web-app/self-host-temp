@extends('layouts.master')

@section('content')
<style>
    .addon-card:hover { border-color: var(--primary); }
    .addon-icon-bg {
        width: 60px; height: 60px; display: flex; align-items: center;
        justify-content: center; margin: 0 auto 16px auto; border-radius: 50%;
        background: linear-gradient(135deg, #63e, #42a5f5 60%);
    }
    .addon-card .addon-badge {
        position: absolute; top: 14px; right: 18px; font-size: .85em;
    }
    .addon-title { font-size: 1.18rem; font-weight: 600; margin-bottom: .35rem; }
    .addon-desc { font-size: .95rem; color: #666; min-height: 32px; }
    .addon-actions {
        border-top: 1px solid #f1f1f1; padding-top: 15px; display: flex;
        gap: 10px; margin-top: 18px; justify-content: center;
    }
</style>

<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-4">
            <h2 class="me-auto mb-0">Addons & Modules</h2>
        </div>
        <div class="row">
            @php
               $modules = [
    [
        'name'=>'ApluAnalytics',
        'desc'=>'Powerful website analytics and monitoring suite.',
        'status'=>'not_installed',
        'icon'=>'fa-chart-bar',
        'color'=>'#673ab7',
        'version'=>'2.1.0',
        'compatible'=>'Laravel 10+',
        'price'=>1499,
        'features'=>['Uptime Monitoring','Real-time Notifications','User Heatmaps','Report Exports'],
        'last_updated'=>'2024-06-15',
        'author'=>'Aplu Team',
        'requirements'=>'Requires PHP 8.1+, Laravel 10+',
    ],
    [
        'name'=>'Analytics Pro',
        'desc'=>'Advanced analytics for better insights. Includes segment comparison, goal tracking, and live views.',
        'status'=>'installed',
        'icon'=>'fa-chart-line',
        'color'=>'#42a5f5',
        'version'=>'1.8.2',
        'compatible'=>'Laravel 10+',
        'price'=>1199,
        'features'=>['Goal Tracking','Segments','Live Analytics','Export CSV'],
        'last_updated'=>'2024-05-11',
        'author'=>'Pro Analytics Devs',
        'requirements'=>'Requires PHP 8.0+, Laravel 10+',
    ],
    [
        'name'=>'Social Connect',
        'desc'=>'Connect social accounts easily. One-click login, Facebook, Twitter, Google, LinkedIn supported.',
        'status'=>'not_installed',
        'icon'=>'fa-share-alt',
        'color'=>'#3b5998',
        'version'=>'2.3.0',
        'compatible'=>'Laravel 9+',
        'price'=>699,
        'features'=>['Social Login','OAuth2 Support','User Linking'],
        'last_updated'=>'2024-05-19',
        'author'=>'Connectify',
        'requirements'=>'Requires Laravel Socialite',
    ],
    [
        'name'=>'Email Booster',
        'desc'=>'Email campaigns automation. Design, schedule, and send personalized email campaigns at scale.',
        'status'=>'installed',
        'icon'=>'fa-envelope-open-text',
        'color'=>'#ff7043',
        'version'=>'1.2.4',
        'compatible'=>'Laravel 8+',
        'price'=>899,
        'features'=>['Campaign Scheduler','Personalization','Template Gallery','Open Tracking'],
        'last_updated'=>'2024-03-08',
        'author'=>'MailerX',
        'requirements'=>'SMTP Setup Required',
    ],
    [
        'name'=>'SMS Sender',
        'desc'=>'Bulk SMS notification module. Send instant alerts and reminders to your subscribers worldwide.',
        'status'=>'not_installed',
        'icon'=>'fa-sms',
        'color'=>'#43a047',
        'version'=>'1.0.6',
        'compatible'=>'Laravel 10+',
        'price'=>599,
        'features'=>['Bulk SMS','Delivery Reports','Schedule SMS','Contact Groups'],
        'last_updated'=>'2024-02-19',
        'author'=>'BulkSend',
        'requirements'=>'Twilio or MSG91 API',
    ],
    [
        'name'=>'Reports Advanced',
        'desc'=>'Download detailed reports. Export and analyze every event, subscriber, and notification.',
        'status'=>'not_installed',
        'icon'=>'fa-file-alt',
        'color'=>'#8e24aa',
        'version'=>'3.1.0',
        'compatible'=>'Laravel 9+',
        'price'=>799,
        'features'=>['Event Exports','Custom Filters','PDF Reports','CSV Download'],
        'last_updated'=>'2024-05-01',
        'author'=>'DataHub',
        'requirements'=>'No extra requirements',
    ],
    [
        'name'=>'Backup Wizard',
        'desc'=>'Schedule and restore backups with one click. Automatic and manual database and file backups.',
        'status'=>'installed',
        'icon'=>'fa-database',
        'color'=>'#fbc02d',
        'version'=>'2.0.0',
        'compatible'=>'Laravel 10+',
        'price'=>599,
        'features'=>['Auto Backup','Restore Point','Cloud Support','Email Alerts'],
        'last_updated'=>'2024-03-16',
        'author'=>'SafeStore',
        'requirements'=>'Cloud API for offsite backups',
    ],
    [
        'name'=>'Push Magic',
        'desc'=>'Push notification integration for web and mobile users. Re-engage your users with ease.',
        'status'=>'not_installed',
        'icon'=>'fa-bell',
        'color'=>'#00bcd4',
        'version'=>'1.4.2',
        'compatible'=>'Laravel 10+',
        'price'=>1099,
        'features'=>['Web Push','Mobile Push','Segmented Audience','Analytics'],
        'last_updated'=>'2024-04-10',
        'author'=>'MagicPush',
        'requirements'=>'Firebase Cloud Messaging',
    ],
    [
        'name'=>'Theme Customizer',
        'desc'=>'Customize your dashboard UI. Change colors, fonts, and layout with drag & drop interface.',
        'status'=>'installed',
        'icon'=>'fa-paint-brush',
        'color'=>'#ffca28',
        'version'=>'2.8.9',
        'compatible'=>'Laravel 9+',
        'price'=>499,
        'features'=>['Live Preview','Color Picker','Font Switcher','Layout Presets'],
        'last_updated'=>'2024-04-25',
        'author'=>'ThemeX',
        'requirements'=>'No extra requirements',
    ],
    [
        'name'=>'Security Shield',
        'desc'=>'Extra layer of security. Firewall, login alerts, brute force protection and 2FA ready.',
        'status'=>'not_installed',
        'icon'=>'fa-shield-alt',
        'color'=>'#388e3c',
        'version'=>'1.1.1',
        'compatible'=>'Laravel 10+',
        'price'=>899,
        'features'=>['Brute Force Block','2FA','IP Whitelisting','Login Alerts'],
        'last_updated'=>'2024-02-10',
        'author'=>'SecureNet',
        'requirements'=>'PHP 8.0+, Laravel 10+',
    ],
    [
        'name'=>'Form Builder',
        'desc'=>'Drag & drop form creator. Build beautiful forms without writing any code.',
        'status'=>'not_installed',
        'icon'=>'fa-wpforms',
        'color'=>'#e53935',
        'version'=>'2.5.5',
        'compatible'=>'Laravel 8+',
        'price'=>999,
        'features'=>['Drag & Drop','Validation Rules','File Uploads','Form Analytics'],
        'last_updated'=>'2024-03-12',
        'author'=>'Formo',
        'requirements'=>'No extra requirements',
    ],
    [
        'name'=>'Subscriber Export',
        'desc'=>'Export subscribers to CSV and Excel. Use advanced filters for segmentation.',
        'status'=>'installed',
        'icon'=>'fa-user-friends',
        'color'=>'#0097a7',
        'version'=>'1.0.0',
        'compatible'=>'Laravel 9+',
        'price'=>299,
        'features'=>['Export CSV','Export Excel','Advanced Filters'],
        'last_updated'=>'2024-01-18',
        'author'=>'ExportPro',
        'requirements'=>'No extra requirements',
    ],
    [
        'name'=>'Workflow Engine',
        'desc'=>'Automate your workflow. Trigger emails, SMS, and actions on any event.',
        'status'=>'not_installed',
        'icon'=>'fa-cogs',
        'color'=>'#5e35b1',
        'version'=>'3.0.0',
        'compatible'=>'Laravel 10+',
        'price'=>1499,
        'features'=>['Multi-Step Workflows','Email Triggers','Webhook Support','Drag & Drop'],
        'last_updated'=>'2024-05-30',
        'author'=>'AutoFlow',
        'requirements'=>'PHP 8.1+, Laravel 10+',
    ],
];

            @endphp

            @foreach ($modules as $module)
            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card addon-card  position-relative">
                    <span class="addon-badge badge 
                        @if($module['status']=='installed') bg-success
                        @else bg-secondary
                        @endif">
                        {{ $module['status']=='installed' ? 'Installed' : 'Not Installed' }}
                    </span>
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <div>
                            <div class="addon-icon-bg mb-3" style="background:linear-gradient(135deg,{{ $module['color'] }},#63e);">
                                <i class="fas {{ $module['icon'] }} fa-2x text-white"></i>
                            </div>
                            <div class="addon-title">
                                {{ $module['name'] }}
                              
                            </div>
                           <div class="addon-desc mb-2">{!! $module['desc'] !!}</div>

                            @if(isset($module['price']))
                            <div class="mb-3">
                                <span class="fw-bold fs-4 text-success">₹{{ number_format($module['price']) }}</span>
                                <span class="text-muted fs-7"> one time</span>
                            </div>
                            @endif
                        </div>
                        <div class="addon-actions mt-auto">
                            @if($module['status']=='installed')
                                <button class="btn btn-outline-danger btn-sm w-100 btn-revoke" data-name="{{ $module['name'] }}">
                                    <i class="fas fa-times me-1"></i> Revoke
                                </button>
                            @else
                                <button 
                                    class="btn btn-outline-info btn-sm w-100 btn-preview" 
                                    data-name="{{ $module['name'] }}"
                                    data-desc="{{ htmlentities($module['desc']) }}"
                                    data-version="{{ $module['version'] }}"
                                    data-compatible="{{ $module['compatible'] }}"
                                    data-price="{{ $module['price'] }}"
                                    data-features="{{ implode('|', $module['features'] ?? []) }}"
                                    data-last_updated="{{ $module['last_updated'] ?? '' }}"
                                    data-author="{{ $module['author'] ?? '' }}"
                                    data-requirements="{{ $module['requirements'] ?? '' }}"
                                >
                                    <i class="fas fa-eye me-1"></i> Preview
                                </button>
                                <button class="btn btn-outline-primary btn-sm w-100 btn-purchase" data-name="{{ $module['name'] }}">
                                    <i class="fas fa-shopping-cart me-1"></i> Purchase
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewModalLabel">Module Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table mb-0">
            <tbody>
                <tr><th scope="row" style="width:130px;">Title</th><td id="modalModuleTitle"></td></tr>
                <tr>
                    <th scope="row">Description</th>
                    <td style="max-width: 480px;">
                        <div id="modalModuleDesc" style="max-height:160px;overflow:auto;line-height:1.7"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Features</th>
                    <td>
                        <ul id="modalModuleFeatures" class="mb-0 ps-3"></ul>
                    </td>
                </tr>
                <tr><th scope="row">Version</th><td id="modalModuleVersion"></td></tr>
                <tr><th scope="row">Compatible</th><td id="modalModuleCompatible"></td></tr>
                <tr><th scope="row">Last Updated</th><td id="modalModuleUpdated"></td></tr>
                <tr><th scope="row">Author</th><td id="modalModuleAuthor"></td></tr>
                <tr><th scope="row">Requirements</th><td id="modalModuleRequirements"></td></tr>
                <tr><th scope="row">Price</th><td id="modalModulePrice"></td></tr>
            </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="modalPurchaseBtn" style="display:none;">
            <i class="fas fa-shopping-cart me-1"></i> Purchase
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="purchaseForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="purchaseModalLabel">Activate Module</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="purchaseModuleName" name="module">
          <div class="mb-3">
            <label for="purchaseCode" class="form-label">Purchase Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="purchaseCode" name="purchase_code" placeholder="Enter your purchase code" required>
          </div>
          <div class="mb-3">
            <label for="licenseKey" class="form-label">License Key <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="licenseKey" name="license_key" placeholder="Enter your license key" required>
          </div>
          <div class="mb-3">
            <label for="licenseKey" class="form-label">Installation Path <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="installation_path" name="installation_path" placeholder="Enter your installation path" value="{{ base_path() }}" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary w-100"><i class="fas fa-unlock me-1"></i> Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Handle purchase button on card and preview modal
    function openPurchaseModal(moduleName) {
        $('#purchaseModuleName').val(moduleName);
        $('#purchaseCode').val('');
        $('#licenseKey').val('');
        $('#purchaseModalLabel').text('Activate ' + moduleName);
        $('#purchaseModal').modal('show');
    }

    $('.btn-purchase').click(function() {
        let name = $(this).data('name');
        openPurchaseModal(name);
    });

    $('.btn-revoke').click(function() {
        let name = $(this).data('name');
        Swal.fire('Revoke', `Revoke module: <b>${name}</b>`, 'warning');
    });

    $('.btn-preview').click(function() {
        let name = $(this).data('name');
        let desc = $(this).data('desc');
        let version = $(this).data('version');
        let compatible = $(this).data('compatible');
        let price = $(this).data('price');
        let features = $(this).data('features');
        let last_updated = $(this).data('last_updated');
        let author = $(this).data('author');
        let requirements = $(this).data('requirements');
        let status = $(this).closest('.addon-card').find('.addon-badge').text().trim().toLowerCase();

        $('#modalModuleTitle').text(name);
        $('#modalModuleDesc').html($('<div/>').html(desc).text()); // decode & render HTML

        // Features
        let featuresArr = features ? features.split('|') : [];
        let featuresList = '';
        for(let i=0; i<featuresArr.length; i++) {
            if(featuresArr[i]) featuresList += `<li>${featuresArr[i]}</li>`;
        }
        $('#modalModuleFeatures').html(featuresList);

        $('#modalModuleVersion').text(version);
        $('#modalModuleCompatible').text(compatible);
        $('#modalModuleUpdated').text(last_updated || '-');
        $('#modalModuleAuthor').text(author || '-');
        $('#modalModuleRequirements').text(requirements || '-');
        $('#modalModulePrice').text(price ? `₹${parseInt(price).toLocaleString()}` : '-');

        // Show/hide the purchase button
        if (status !== 'installed') {
            $('#modalPurchaseBtn').show().data('name', name);
        } else {
            $('#modalPurchaseBtn').hide();
        }

        $('#previewModal').modal('show');
    });

    // Handle purchase button in preview modal
    $('#modalPurchaseBtn').click(function() {
        let name = $(this).data('name');
        $('#previewModal').modal('hide');
        setTimeout(function() {
            openPurchaseModal(name);
        }, 350);
    });

    // Handle purchase form submit
    $('#purchaseForm').submit(function(e) {
        e.preventDefault();
        let module = $('#purchaseModuleName').val();
        let purchaseCode = $('#purchaseCode').val();
        let licenseKey = $('#licenseKey').val();

        // Demo: Show entered values (replace with your backend logic!)
        $('#purchaseModal').modal('hide');
        setTimeout(function() {
            Swal.fire(
                'Submitted!',
                `<b>${module}</b> activated with:<br>Purchase Code: <code>${purchaseCode}</code><br>License Key: <code>${licenseKey}</code>`,
                'success'
            );
        }, 350);
    });
});
</script>
@endpush
