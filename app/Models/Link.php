<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'url',
        'description',
        'user_id',
        'inactive',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'link_category');
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->imgext);
    }

    public function getPathAttribute()
    {
        return 'images/website_images/' . $this->file;
    }
}
