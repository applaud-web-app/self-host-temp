<?php

namespace Modules\RssAutomation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RssAutomationController extends Controller
{
    public function report()
    {
        return view('rssautomation::report');
    }

    public function add()
    {
        return view('rssautomation::add');
    }

    public function store(Request $request)
    {
        // You can save to DB later
        return redirect()->route('rssautomation.report')->with('success', 'RSS Feed submitted!');
    }
}
