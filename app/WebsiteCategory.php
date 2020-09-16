<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebsiteCategory extends Model
{
    protected $table = 'website_category';
    protected $primaryKey = 'website_category_id';
    public $timestamps = false;

    public function websites()
    {
        return $this->belongsToMany(Website::class, 'website_category_cross', 'website_category_id', 'website_id');
    }
}
