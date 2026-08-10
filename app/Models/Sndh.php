<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sndh extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $keyType = 'string';

    // The key is the tune's path inside the archive, not a generated integer.
    // Without this, inserting a tune would overwrite the path in memory with
    // whatever the driver reports as the last insert id.
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sndh_archive_id',
        'title',
        'composer',
        'ripper',
        'subtunes',
        'default_subtune',
        'year',
    ];
}
