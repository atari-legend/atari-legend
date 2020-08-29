<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScreenshotGame extends Model
{
    protected $table = "screenshot_game";
    protected $primaryKey = "screenshot_game_id";
    public $timestamps = false;

    public function screenshots()
    {
        return $this->belongsTo(Screenshot::class, 'screenshot_id');
    }

    /**
     * Helper function to get the actual image file name of a screenshot
     * Unfortunately the DB model is a bit wrong around screenshots,
     * (should not be many-to-many between screenshot_main and screenshot_game)
     * so we always take the first entry
     *
     * @return string Screenshot file name
     */
    public function getFileAttribute()
    {
        $screenshots = $this->screenshots();
        return $screenshots->first()->screenshot_id
            .'.'
            .$screenshots->first()->imgext;
    }
}
