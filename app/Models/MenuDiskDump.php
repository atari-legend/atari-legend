<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskDump extends Model
{
    use HasFactory;

    const EXTENSIONS = ['stx', 'msa', 'raw', 'scp', 'st'];

    protected $fillable = ['menu_disk_id', 'user_id', 'format', 'sha512', 'size'];

    public function menuDisk()
    {
        return $this->belongsTo(MenuDisk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
