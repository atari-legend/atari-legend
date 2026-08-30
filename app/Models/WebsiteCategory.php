<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'website_category_name',
    ];

    public function websites()
    {
        return $this->belongsToMany(Website::class, 'website_category_cross');
    }
}
