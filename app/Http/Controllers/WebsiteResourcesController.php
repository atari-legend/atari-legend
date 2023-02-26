<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class WebsiteResourcesController extends Controller
{
    public function screenshot(Website $website)
    {
        if ($website->file) {
            $image = ImageManagerStatic::make(Storage::disk('public')->get($website->path));

            return response()->stream(function () use ($image) {
                echo $image->resize(500, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upSize();
                })->stream('webp');
            }, 200, ['Content-Type' => 'image/webp']);
        } else {
            abort('404');
        }
    }
}
