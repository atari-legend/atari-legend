<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'game';
    protected $primaryKey = 'game_id';
    public $timestamps = false;

    public function screenshots()
    {
        return $this->hasMany(ScreenshotGame::class, 'game_id');
    }

    public function developers()
    {
        return $this->belongsToMany(PublisherDeveloper::class, 'game_developer', 'game_id', 'dev_pub_id');
    }

    public function musics()
    {
        return $this->belongsToMany(Music::class, 'game_music', 'music_id', 'game_id');
    }

    public function boxscans()
    {
        return $this->hasMany(BoxScan::class, 'game_id');
    }

    public function reviews()
    {
        return $this->belongsToMany(Review::class, 'review_game', 'game_id', 'review_id');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'game_genre_cross', 'game_genre_id', 'game_id');
    }

    public function releases()
    {
        return $this->hasMany(Release::class, 'game_id', 'game_id');
    }
}
