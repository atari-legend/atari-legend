<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    // The key is an ISO code the data ships with, not a generated integer.
    // Without this, inserting a language would overwrite the code in memory
    // with whatever the driver reports as the last insert id.
    public $incrementing = false;
    public $timestamps = false;
}
