<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomWidgetController extends Controller
{
   public function blogger(){
    return view('widget.blogger');
   }

   public function amp(){
    return view('widget.amp');
   }
}
