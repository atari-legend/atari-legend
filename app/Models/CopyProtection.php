<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyProtection extends Model
{
    use HasFactory;

    protected $table = 'copy_protection';
    public $timestamps = false;
}
