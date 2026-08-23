<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseTOSIncompatibility extends Model
{
    protected $table = 'game_release_tos_version_incompatibility';
    public $timestamps = false;
    protected $fillable = ['tos_id', 'language_id', 'game_release_id'];

    public function tos()
    {
        return $this->belongsTo(TOS::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
