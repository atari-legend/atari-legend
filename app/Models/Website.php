<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $table = 'website';
    protected $primaryKey = 'website_id';
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        return $this->belongsToMany(WebsiteCategory::class, 'website_category_cross', 'website_id', 'website_category_id');
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->website_id, $this->website_imgext);
    }

    public function getPathAttribute()
    {
        return 'images/website_images/' . $this->file;
    }
}
