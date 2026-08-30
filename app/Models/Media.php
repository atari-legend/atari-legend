<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(MediaType::class, 'media_type_id');
    }

    public function dumps()
    {
        return $this->hasMany(Dump::class);
    }

    public function scans()
    {
        return $this->hasMany(MediaScan::class);
    }

    public function release()
    {
        // belongsTo derives its key from the *method* name, so this one wants
        // release_id whatever the related class is called, and the column is
        // game_release_id. Renaming the method to gameRelease() would close
        // that, and is declined on pricing: ->release is 77 lines repo-wide.
        return $this->belongsTo(GameRelease::class, 'game_release_id');
    }
}
