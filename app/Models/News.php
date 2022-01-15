<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $primaryKey = 'news_id';
    public $timestamps = false;

    protected $fillable = [
        'news_headline', 'user_id', 'news_date', 'news_text',
    ];

    protected $casts = [
        'news_date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function image()
    {
        return $this->belongsTo(NewsImage::class, 'news_image_id');
    }

    public function getNewsImageAttribute()
    {
        if ($this->image?->file) {
            return asset('storage/images/news_images/'.$this->image->file);
        } else {
            return null;
        }
    }
}
