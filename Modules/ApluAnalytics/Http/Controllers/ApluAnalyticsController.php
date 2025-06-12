<?php

namespace Modules\ApluAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;

class ApluAnalyticsController extends Controller
{
    public function siteMonitoring()
    {
        return view('apluanalytics::site_monitoring');
    }

    public function url()
    {
        return view('apluanalytics::url');
    }

    public function statusTracker()
    {
        return view('apluanalytics::status_tracker');
    }

    public function userActivity()
    {
        return view('apluanalytics::user_activity');
    }
}
