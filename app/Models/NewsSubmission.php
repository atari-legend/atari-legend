<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsSubmission extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
