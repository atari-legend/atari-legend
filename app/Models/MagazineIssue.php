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

    protected $fillable = [
        'issue', 'label', 'archiveorg_url', 'alternate_url',
        'published', 'imgext', 'page_count', 'circulation',
    ];

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

    /**
     * @return string URL to the cover of this issue, or a placeholder
     *                image if it has no cover.
     */
    public function getImageAttribute()
    {
        if ($this->image_file != null) {
            return asset('storage/' . $this->image_path . '/' . $this->image_file);
        } else {
            return asset('images/no-cover.svg');
        }
    }

    /**
     * @return string Label for this issue (magazine name + issue + date).
     */
    public function getDisplayLabelAttribute()
    {
        $label = [
            $this->magazine->name,
            $this->issue,
            $this->label,
        ];

        return collect($label)->filter()->join(' ');
    }

    /**
     * @return string Label for this issue with the publication date
     */
    public function getDisplayLabelWithDateAttribute()
    {
        $label = $this->display_label;
        if ($this->published) {
            $label .= ' (' . $this->published->format('M Y') . ')';
        }

        return $label;
    }

    /**
     * @return string URL to archive.org to read the magazine in full screen.
     */
    public function getReadUrlAttribute()
    {
        if ($this->archiveorg_url) {
            return Str::replace('/details/', '/stream/', $this->archiveorg_url);
        } elseif ($this->alternate_url) {
            return $this->alternate_url;
        } else {
            return null;
        }
    }

    /**
     * @return number Number of the page containing this magazine, when viewing
     *                all the issues of the magazine.
     */
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
