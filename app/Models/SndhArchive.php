<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SndhArchive extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $keyType = 'string';

    // The key is the archive's name, not a generated integer - see the same
    // note on Sndh.
    public $incrementing = false;
}
