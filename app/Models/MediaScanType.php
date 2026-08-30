<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaScanType extends Model
{
    use HasFactory;

    const TYPE_OTHER = 'Other';

    public $timestamps = false;
}
