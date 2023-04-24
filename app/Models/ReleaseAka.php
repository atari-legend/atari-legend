<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseAka extends Model
{
    protected $table = 'game_release_aka';
    public $timestamps = false;

    protected $fillable = ['game_release_id', 'name', 'language_id'];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function release()
    {
        return $this->belongsTo(Release::class, 'game_release_id');
    }
}
