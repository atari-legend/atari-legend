<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class IndividualText extends Model
{
    protected $table = 'individual_text';
    protected $primaryKey = 'ind_text_id';
    public $timestamps = false;

    protected $fillable = ['ind_email', 'ind_profile', 'ind_imgext'];

    public function getFileAttribute()
    {
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
