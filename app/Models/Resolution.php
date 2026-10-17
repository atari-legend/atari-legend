<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resolution extends Model
{
    use HasFactory;

    const ST_LOW = 'ST Low';
    const ST_MEDIUM = 'ST Medium';
    const ST_HIGH = 'ST High';

    public $timestamps = false;
}
