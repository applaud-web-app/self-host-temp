<?php

namespace Modules\Migrate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MigrateController extends Controller
{
    public function index()
    {
        return view('migrate::index');
    }

    public function report()
    {
        // You can pass real migration results later
        return view('migrate::report');
    }
    public function sendNotification()
    {
        // Later you can implement sending logic here
        return view('migrate::send-notification');
    }
}
