<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReleaseMemoryEnhanced extends Model
{
    protected $table = 'game_release_memory_enhanced';
    public $timestamps = false;

    public function memory()
    {
        return $this->belongsTo(Memory::class, 'memory_id');
    }

    public function enhancement()
    {
        return $this->belongsTo(Enhancement::class, 'enhancement_id');
    }
}
