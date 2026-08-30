<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'website_name',
        'website_url',
        'description',
        'website_date',
        'user_id',
        'inactive',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(WebsiteCategory::class, 'website_category_cross');
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->website_imgext);
    }

    public function getPathAttribute()
    {
        return 'images/website_images/' . $this->file;
    }
}
