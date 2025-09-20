<li>
    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
        <i class="fal fa-rss"></i>
        <span class="nav-text">Migrate</span>
    </a>
    <ul aria-expanded="false">
        <li>
            <a href="{{ route('migrate.index') }}">
                <span class="nav-text">Import</span>
            </a>
        </li>
        <li>
            <a href="{{ route('migrate.send-notification') }}">
                <span class="nav-text">Send Notification</span>
            </a>
        </li>
        <li>
            <a href="{{ route('migrate.report') }}">
                <span class="nav-text">Report</span>
            </a>
        </li>
    </ul>
</li>
