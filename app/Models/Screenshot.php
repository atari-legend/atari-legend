<?php

namespace App\Models;

use App\Helpers\Helper;
use GuzzleHttp\Psr7\MimeType;
use Illuminate\Database\Eloquent\Model;

class Screenshot extends Model
{
    protected $table = 'screenshot_main';
    protected $primaryKey = 'screenshot_id';
    protected $fillable = ['imgext'];
    public $timestamps = false;

    const PATHS = [
        'game'            => 'game_screenshots',
        'game_fact'       => 'game_fact_screenshots',
        'article'         => 'article_screenshots',
        'interview'       => 'interview_screenshots',
        'game_submission' => 'game_submit_screenshots',
        'spotlight'       => 'spotlight_screens',
    ];

    public function getFileAttribute()
    {
        return Helper::filename($this->screenshot_id, $this->imgext);
    }

    public function getMimeTypeAttribute()
    {
        return MimeType::fromExtension($this->imgext);
    }

    /**
     * @param  string  $type  Type of screenshot (game, interview, ...).
     * @return string URL of the image, depending on the screenshot type.
     */
    public function getUrl(string $type): string
    {
        return asset('storage/images/' . Screenshot::PATHS[$type] . '/' . $this->file);
    }

    /**
     * Get the URL of a screenshot using a slug route for an entity.
     *
     * @param  string  $type  Type of entity (e.g. 'game').
     * @param  object  $model  Entity to get the screenshot URL for
     * @return string URL of the image.
     */
    public function getUrlRoute(string $type, object $model)
    {
        return route("{$type}s.screenshot", [
            $type => $model,
            'id'  => $this->getKey(),
            'ext' => $this->imgext,
        ]);
    }

    public function getFolder(string $type): string
    {
        return 'images/' . Screenshot::PATHS[$type];
    }

    public function getPath(string $type): string
    {
        return $this->getFolder($type) . '/' . $this->file;
    }

    public function reviewScreenshots()
    {
        return $this->hasMany(ScreenshotReview::class, 'screenshot_review_id');
    }
}
