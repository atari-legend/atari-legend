<?php

namespace App\Http\Controllers;

use App\Models\Spotlight;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class SpotlightResourcesController extends Controller
{
    public function screenshot(Spotlight $spotlight)
    {
        if ($spotlight->screenshot) {
            $image = ImageManagerStatic::make(Storage::disk('public')->get($spotlight->screenshot->getPath('spotlight')));

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
