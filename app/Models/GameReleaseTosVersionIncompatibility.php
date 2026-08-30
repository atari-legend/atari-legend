<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReleaseTosVersionIncompatibility extends Model
{
    public $timestamps = false;
    protected $fillable = ['tos_id', 'language_id', 'game_release_id'];

    public function tos()
    {
        return $this->belongsTo(Tos::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
