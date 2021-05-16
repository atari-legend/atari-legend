<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sndh extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'composer',
        'ripper',
        'subtunes',
        'default_subtune',
        'year',
    ];
}
