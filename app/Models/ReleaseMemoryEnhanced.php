<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseMemoryEnhanced extends Model
{
    protected $table = 'game_release_memory_enhanced';
    public $timestamps = false;
    protected $fillable = ['release_id', 'memory_id', 'enhancement_id'];

    public function memory()
    {
        return $this->belongsTo(Memory::class, 'memory_id');
    }

    public function enhancement()
    {
        return $this->belongsTo(Enhancement::class, 'enhancement_id');
    }
}
