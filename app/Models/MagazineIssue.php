<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagazineIssue extends Model
{
    use HasFactory;

    protected $fillable = ['issue', 'archiveorg_url', 'published', 'barcode'];

    protected $casts = [
        'published' => 'date',
    ];

    public function magazine()
    {
        return $this->belongsTo(Magazine::class);
    }
}
