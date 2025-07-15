<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class IconController extends Controller
{
    private $iconPath;

    public function __construct()
    {
        $this->iconPath = public_path('images/push/icons');
        $this->ensureDirectoryExists();
    }

    private function ensureDirectoryExists()
    {
        if (!File::exists($this->iconPath)) {
            File::makeDirectory($this->iconPath, 0755, true);
        }
    }

    public function index()
    {
        return view('icons.index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'icons' => 'required',
            'icons.*' => 'image|mimes:png,jpg,jpeg|max:1024',
        ]);

        $uploadedCount = 0;
        
        foreach ($request->file('icons') as $icon) {
            $filename = $this->generateUniqueFilename($icon);
            $this->processAndSaveIcon($icon, $filename);
            $uploadedCount++;
        }

        return back()->with('success', "{$uploadedCount} icons uploaded successfully!");
    }

    private function generateUniqueFilename($icon)
    {
        return uniqid() . '.' . $icon->getClientOriginalExtension();
    }

    private function processAndSaveIcon($icon, $filename)
    {
        // Just move the image without resizing or compressing
        $icon->move($this->iconPath, $filename);
    }

    public function delete($filename)
    {
        $filePath = $this->iconPath . '/' . $filename;
        
        if (File::exists($filePath)) {
            File::delete($filePath);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['error' => 'Icon not found'], 404);
    }

    public function list(Request $request)
    {
        $search = strtolower($request->input('search', ''));
        
        $icons = collect(File::files($this->iconPath))
            ->when($search, function($collection) use ($search) {
                return $collection->filter(function($file) use ($search) {
                    return str_contains(strtolower($file->getFilename()), $search);
                });
            })
            ->map(function ($file) {
                return [
                    'url' => asset('images/push/icons/' . $file->getFilename()),
                    'filename' => $file->getFilename(),
                    'delete_url' => route('icons.delete', $file->getFilename()),
                ];
            });

        return response()->json([
            'icons' => $icons,
            'total' => $icons->count()
        ]);
    }
}