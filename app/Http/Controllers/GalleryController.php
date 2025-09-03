<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class GalleryController extends Controller
{
   
    private $iconPath;
    private $bannerPath;

    public function __construct()
    {
        $this->iconPath = public_path('images/push/icons');
        $this->bannerPath = public_path('uploads/banner');
        $this->ensureDirectoryExists();
    }

    private function ensureDirectoryExists()
    {
        if (!File::exists($this->iconPath)) {
            File::makeDirectory($this->iconPath, 0755, true);
        }
    }

    public function iconIndex()
    {
        return view('gallery.icons.index');
    }

    public function iconUpload(Request $request)
    {
        $request->validate([
            'icons' => 'required',
            'icons.*' => 'image|mimes:png,jpg,jpeg|max:50', // 50KB
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

    public function iconDelete($filename)
    {
        $filePath = $this->iconPath . '/' . $filename;
        
        if (File::exists($filePath)) {
            File::delete($filePath);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['error' => 'Icon not found'], 404);
    }

    public function iconList(Request $request)
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
                    'delete_url' => route('gallery.icons.delete', $file->getFilename()),
                ];
            });

        return response()->json([
            'icons' => $icons,
            'total' => $icons->count()
        ]);
    }

    public function bannerIndex()
    {
        return view('gallery.banners.index');
    }

    private function processAndSaveBanner($banner, $filename)
    {
        $banner->move($this->bannerPath, $filename);
    }

    public function bannerUpload(Request $request)
    {
        $request->validate([
            'banners' => 'required',
            'banners.*' => 'image|mimes:png,jpg,jpeg|max:50', // 50KB
        ]);

        $uploadedCount = 0;

        foreach ($request->file('banners') as $banner) {
            $filename = $this->generateUniqueFilename($banner);
            $this->processAndSaveBanner($banner, $filename);
            $uploadedCount++;
        }

        return back()->with('success', "{$uploadedCount} banners uploaded successfully!");
    }

    public function bannerDelete($filename)
    {
        $filePath = $this->bannerPath . '/' . $filename;

        if (File::exists($filePath)) {
            File::delete($filePath);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Banner not found'], 404);
    }

    public function bannerList(Request $request)
    {
        $search = strtolower($request->input('search', ''));

        $banners = collect(File::files($this->bannerPath))
            ->when($search, function($collection) use ($search) {
                return $collection->filter(function($file) use ($search) {
                    return str_contains(strtolower($file->getFilename()), $search);
                });
            })
            ->map(function ($file) {
                return [
                    'url' => asset('uploads/banner/' . $file->getFilename()),
                    'filename' => $file->getFilename(),
                    'delete_url' => route('gallery.banners.delete', $file->getFilename()),
                ];
            });

        return response()->json([
            'banners' => $banners,
            'total' => $banners->count()
        ]);
    }

}
