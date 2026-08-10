<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiskProtection extends Model
{
    use HasFactory;

    protected $table = 'disk_protection';
    public $timestamps = false;
}
