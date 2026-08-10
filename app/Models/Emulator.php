<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emulator extends Model
{
    use HasFactory;

    protected $table = 'emulator';
    public $timestamps = false;
}
