<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualText extends Model
{
    protected $table = 'individual_text';
    protected $primaryKey = 'ind_text_id';
    public $timestamps = false;

    public function getFileAttribute()
    {
        $id = $this->ind_id;
        $ext = $this->ind_imgext;

        if (isset($id) && isset($ext) && $ext != '') {
            return "${id}.${ext}";
        } else {
            return null;
        }
    }
}
