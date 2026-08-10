<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TOS extends Model
{
    use HasFactory;

    protected $table = 'tos';
    public $timestamps = false;
}
