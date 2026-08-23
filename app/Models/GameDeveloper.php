<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GameDeveloper extends Pivot
{
    protected $table = 'game_developer';
    public $incrementing = true;

    public function developerRole()
    {
        return $this->belongsTo(DeveloperRole::class);
    }
}
