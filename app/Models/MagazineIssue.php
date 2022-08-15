<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MagazineIssue extends Model
{
    use HasFactory;

    protected $fillable = ['issue', 'archiveorg_url', 'published', 'imgext'];

    protected $casts = [
        'published' => 'date',
    ];

    public function magazine()
    {
        return $this->belongsTo(Magazine::class);
    }

    public function indices()
    {
        return $this->hasMany(MagazineIndex::class);
    }

    public function getImageFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->imgext);
    }

    public function getImagePathAttribute()
    {
        return 'images/magazine_scans';
    }

    public function getImageAttribute()
    {
        if ($this->image_file != null) {
            return asset('storage/' . $this->image_path . '/' . $this->image_file);
        } else {
            return asset('images/no-cover.svg');
        }
    }

    public function getLabelAttribute()
    {
        $label = [
            $this->magazine->name,
            $this->issue,
        ];
        if ($this->published) {
            $label[] = '(' . $this->published->format('M Y') . ')';
        }

        return collect($label)->filter()->join(' ');
    }

    public function getReadUrlAttribute()
    {
        if ($this->archiveorg_url) {
            return Str::replace('/details/', '/stream/', $this->archiveorg_url);
        } else {
            return null;
        }
    }
}
