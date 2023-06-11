<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Dump extends Model
{
    const FORMATS = ['MSA', 'SCP', 'ST', 'STX'];

    const TRACKPICTURES_DIRECTORY = 'images/dump_trackpictures';

    protected $table = 'dump';
    public $timestamps = false;

    protected $casts = [
        'date'          => 'datetime:timestamp',
        'track_picture' => 'boolean',
    ];

    protected $fillable = ['format', 'sha512', 'date', 'size', 'track_picture'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return string Filename to use when downloading this dump, based on the
     *                game name, release year and media label
     */
    public function getDownloadFilenameAttribute()
    {
        $name = [
            $this->media->release->game->game_name,
            '(' . $this->media->release->year . ')',
        ];

        if ($this->media->label) {
            $name[] = $this->media->label;
        }

        return collect($name)
            ->join(' ')
            . '.zip';
    }

    public function getDownloadUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public function getPathAttribute()
    {
        return 'zips/games/' . $this->getKey() . '.zip';
    }

    public function getTrackPictureUrlAttribute()
    {
        if (Storage::disk('public')->exists(Dump::TRACKPICTURES_DIRECTORY . '/' . $this->getKey() . '.png')) {
            return asset('storage/' . Dump::TRACKPICTURES_DIRECTORY . '/' . $this->getKey() . '.png');
        } else {
            return null;
        }
    }
}
