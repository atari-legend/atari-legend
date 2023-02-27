<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

class GameReleaseResourcesController extends Controller
{
    public function boxscan(Release $release, int $id)
    {
        $boxscan = $release->boxscans
            ->first(function ($s) use ($id) {
                return $s->getKey() === $id;
            });
        if ($boxscan) {
            $image = ImageManagerStatic::make(Storage::disk('public')->get($boxscan->path));

            return response()->stream(function () use ($image) {
                echo $image->resize(500, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upSize();
                })->stream('webp');
            }, 200, [
                'Cache-Control' => 'max-age=31536000',
                'Content-Type'  => 'image/webp',
            ]);
        } else {
            abort('404');
        }
    }
}
