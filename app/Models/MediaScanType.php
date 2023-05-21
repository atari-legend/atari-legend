<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaScanType extends Model
{
    const TYPE_OTHER = 'Other';

    protected $table = 'media_scan_type';
    public $timestamps = false;
}
