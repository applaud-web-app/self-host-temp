<?php

namespace Modules\WelcomeMessage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WelcomeMessageController extends Controller
{
    public function index()
    {
        return view('welcomemessage::index');
    }
}
