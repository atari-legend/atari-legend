<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Magazine extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function issues()
    {
        return $this->hasMany(MagazineIssue::class);
    }

    public function getCoverUrlAttribute()
    {
        $firstIssue = $this->issues
            ->sortBy('issue')
            ->filter(function ($issue) {
                return $issue->imgext != null;
            })
            ->first();

        return $firstIssue->image ?? asset('images/no-cover.svg');
    }
}
