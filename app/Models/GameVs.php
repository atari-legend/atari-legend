<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameVs extends Model
{
    protected $table = 'game_vs';
    public $timestamps = false;
    protected $fillable = ['atari_id', 'amiga_id', 'lemon64_slug'];

    public function game()
    {
        return $this->belongsTo(Game::class, 'atari_id');
    }

    public function getLemonAmigaUrlAttribute()
    {
        return 'https://www.lemonamiga.com/games/details.php?id=' . $this->amiga_id;
    }

    public function getLemon64UrlAttribute()
    {
        return 'https://www.lemon64.com/game/' . $this->lemon64_slug;
    }
}
