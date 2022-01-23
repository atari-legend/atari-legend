<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'game';
    protected $primaryKey = 'game_id';
    public $timestamps = false;

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_game', 'game_id', 'screenshot_id');
    }

    public function developers()
    {
        return $this->belongsToMany(PublisherDeveloper::class, 'game_developer', 'game_id', 'dev_pub_id');
    }

    public function sndhs()
    {
        return $this->belongsToMany(Sndh::class, 'game_sndh', 'game_id', 'sndh_id');
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
        return $this->belongsToMany(Genre::class, 'game_genre_cross', 'game_id', 'game_genre_id');
    }

    public function releases()
    {
        return $this->hasMany(Release::class, 'game_id', 'game_id');
    }

    public function individuals()
    {
        return $this->belongsToMany(Individual::class, 'game_individual', 'game_id', 'individual_id')
            ->withPivot('individual_role_id')
            ->using(GameIndividual::class);
    }

    public function programmingLanguages()
    {
        return $this->belongsToMany(ProgrammingLanguage::class, 'game_programming_language', 'game_id');
    }

    public function engines()
    {
        return $this->belongsToMany(Engine::class, 'game_engine', 'game_id');
    }

    public function akas()
    {
        return $this->hasMany(GameAka::class, 'game_id');
    }

    public function port()
    {
        return $this->belongsTo(Port::class);
    }

    public function progressSystem()
    {
        return $this->belongsTo(ProgressSystem::class);
    }

    public function soundHardwares()
    {
        return $this->belongsToMany(SoundHardware::class, 'game_sound_hardware', 'game_id');
    }

    public function controls()
    {
        return $this->belongsToMany(Control::class, 'game_control', 'game_id');
    }

    public function vs()
    {
        return $this->hasMany(GameVs::class, 'atari_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'game_user_comments', 'game_id', 'comment_id');
    }

    public function similarGames()
    {
        return $this->belongsToMany(Game::class, 'game_similar', 'game_id', 'game_similar_cross');
    }

    public function facts()
    {
        return $this->hasMany(GameFact::class, 'game_id');
    }

    public function series()
    {
        return $this->belongsTo(GameSeries::class, 'game_series_id');
    }

    public function infoSubmissions()
    {
        return $this->hasMany(GameSubmitInfo::class, 'game_id');
    }

    public function menuDiskContents()
    {
        return $this->hasMany(MenuDiskContent::class, 'game_id');
    }

    public function videos()
    {
        return $this->hasMany(GameVideo::class, 'game_id');
    }

    public function votes()
    {
        return $this->belongsToMany(GameVote::class, 'game_vote');
    }

    public function getNonMenuReleasesAttribute()
    {
        return $this
            ->releases
            ->filter(function ($release) {
                return $release->menuDiskContents->isEmpty();
            });
    }
}
