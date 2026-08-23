<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class IndividualText extends Model
{
    protected $table = 'individual_text';
    public $timestamps = false;

    protected $fillable = ['ind_email', 'ind_profile', 'ind_imgext'];

    public function getFileAttribute()
    {
        // ind_id, not getKey(): this model's key is ind_text_id, but the
        // avatar is stored under the individual's id -- see
        // GameIndividualController::update(), which names the file with
        // $individual->getKey().
        return Helper::filename($this->ind_id, $this->ind_imgext);
    }

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public function getPathAttribute()
    {
        return 'images/individual_screenshots/' . $this->file;
    }
}
