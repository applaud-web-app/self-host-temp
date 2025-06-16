<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SegmentationController extends Controller
{
    /**
     * Display the list-all-segments page.
     */
    public function index()
    {
        // resources/views/segmentation/index.blade.php
        return view('segmentation.index');
    }

    /**
     * Display the “create segment” form.
     */
    public function create()
    {
        // resources/views/segmentation/create.blade.php
        return view('segmentation.create');
    }
}
