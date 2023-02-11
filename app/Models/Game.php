<?php

namespace App\Models;

use App\Helpers\GameHelper;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'game';
    protected $primaryKey = 'game_id';

    const MULTIPLAYER_TYPES = ['Simultaneous', 'Turn by turn'];
    const MULTIPLAYER_HARDWARE = ['Cartridge', 'Midi-Link'];

    protected $fillable = [
        'game_name', 'slug', 'port_id', 'progress_system_id', 'game_series_id',
        'number_players_on_same_machine', 'number_players_multiple_machines',
        'multiplayer_type', 'multiplayer_hardware',
    ];

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_game', 'game_id', 'screenshot_id');
    }

    public function developers()
    {
        return $this->belongsToMany(PublisherDeveloper::class, 'game_developer', 'game_id', 'dev_pub_id')
            ->withPivot('developer_role_id')
            ->using(GameDeveloper::class);
    }

    public function sndhs()
    {
        return $this->belongsToMany(Sndh::class, 'game_sndh', 'game_id', 'sndh_id');
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
        return $this->hasMany(GameVote::class, 'game_id');
    }

    public function magazineIndices()
    {
        return $this->hasMany(MagazineIndex::class, 'game_id');
    }

    public function getNonMenuReleasesAttribute()
    {
        return $this
            ->releases
            ->filter(function ($release) {
                return $release->menuDiskContents->isEmpty();
            });
    }

    public function getScoreAttribute()
    {
        $score = $this->votes->avg('score');
        if ($score) {
            return GameHelper::normalizeScore($score);
        } else {
            return null;
        }
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
