<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GlobalController extends Controller
{
   public function subsStore(){
    return view('widget.notification');
   }
}
