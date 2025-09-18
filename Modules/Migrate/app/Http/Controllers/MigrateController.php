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
}
