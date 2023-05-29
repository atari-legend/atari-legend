<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dump extends Model
{
    const FORMATS = ['MSA', 'SCP', 'ST', 'STX'];

    protected $table = 'dump';
    public $timestamps = false;

    protected $casts = [
        'date' => 'datetime:timestamp',
    ];

    protected $fillable = ['format', 'sha512', 'date', 'size'];

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
}
