<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskDump extends Model
{
    use HasFactory;

    const EXTENSIONS = ['STX', 'MSA', 'RAW', 'SCP', 'ST'];

    protected $fillable = ['user_id', 'format', 'sha512', 'size'];

    public function menuDisk()
    {
        return $this->hasOne(MenuDisk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
