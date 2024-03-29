<?php

namespace App\Http\Controllers;

use App\Models\Individual;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class IndividualResourcesController extends Controller
{
    public function avatar(Individual $individual)
    {
        if ($individual->text->file) {
            $image = ImageManagerStatic::make(Storage::disk('public')->get($individual->text->path));

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
