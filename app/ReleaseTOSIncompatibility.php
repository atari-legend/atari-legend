<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReleaseTOSIncompatibility extends Model
{
    protected $table = 'game_release_tos_version_incompatibility';
    public $timestamps = false;

    public function tos()
    {
        return $this->belongsTo(TOS::class, 'tos_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
