<?php
 
namespace App\Helpers;
 
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
 
class ImageHelper
{
    public static function uploadImage(UploadedFile $file, $path)
    {
        // Generate a unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
 
        // Move the file to the specified path
        $file->move(public_path($path), $filename);
 
        // Construct the relative path
        $relativePath = $path . '/' . $filename;
 
        // Return the full URL with the domain
        return url($relativePath);
    }
 
    public static function deleteImage($url)
    {
        // Remove domain part if full URL was stored
        $relativePath = str_replace(url('/'), '', $url);
 
        // Build full path to delete
        $fullPath = public_path($relativePath);
 
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}