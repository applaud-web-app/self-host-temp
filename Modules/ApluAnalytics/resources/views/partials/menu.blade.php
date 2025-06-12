@php
    $apluAnalyticsLicensed = env('APLUANALYTICS_LICENSE_KEY') === 'YOUR_VALID_LICENSE_KEY';
@endphp
@if ($apluAnalyticsLicensed)
    <li>
        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
            <i class="fal fa-chart-line"></i>
            <span class="nav-text">Aplu Analytics</span>
        </a>
        <ul aria-expanded="false">
            <li>
                <a href="{{ url('aplu-analytics/site-monitoring') }}">
                    
                    <span class="nav-text">Site Monitoring</span>
                </a>
            </li>
            <li>
                <a href="{{ url('aplu-analytics/url') }}">
                   
                    <span class="nav-text">URL</span>
                </a>
            </li>
            <li>
                <a href="{{ url('aplu-analytics/status-tracker') }}">
                    
                    <span class="nav-text">Status Tracker</span>
                </a>
            </li>
            <li>
                <a href="{{ url('aplu-analytics/user-activity') }}">
                    
                    <span class="nav-text">User Activity</span>
                </a>
            </li>
        </ul>
    </li>
@endif
