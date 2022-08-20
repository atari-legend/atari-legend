<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Http\Controllers\MagazineController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    public function getMagazinePageNumberAttribute()
    {
        // Select all issues from the magazine, order them properly
        // and find the index of the current magazine

        // Unfortunately MySQL does not support a way to compute row
        // numbers automatically, otherwise we could also ask it to
        // return the index. So we have to get the whole list, and
        // search the index in PHP

        $index = DB::table('magazine_issues')
            ->select('magazine_issues.id')
            ->join('magazines', 'magazines.id', '=', 'magazine_issues.magazine_id')
            ->where('magazine_id', '=', $this->magazine->id)
            ->orderBy('issue', 'asc')
            ->get()
            ->pluck('id')
            ->search($this->id);

        return ceil(($index + 1) / MagazineController::PAGE_SIZE);
    }
}
