<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class LinkResourcesController extends Controller
{
    public function screenshot(Link $link)
    {
        if ($link->file && Storage::disk('public')->exists($link->path)) {
            $image = ImageManagerStatic::make(Storage::disk('public')->get($link->path));

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
