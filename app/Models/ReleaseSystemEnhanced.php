<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseSystemEnhanced extends Model
{
    protected $table = 'game_release_system_enhanced';
    public $timestamps = false;
    protected $fillable = ['enhancement_id', 'system_id', 'game_release_id'];

    public function system()
    {
        return $this->belongsTo(System::class, 'system_id');
    }

    public function enhancement()
    {
        return $this->belongsTo(Enhancement::class, 'enhancement_id');
    }
}
