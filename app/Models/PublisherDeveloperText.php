<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublisherDeveloperText extends Model
{
    protected $table = 'pub_dev_text';
    protected $primaryKey = 'pub_dev_text_id';
    public $timestamps = false;

    public function getFileAttribute()
    {
        $id = $this->pub_dev_id;
        $ext = $this->pub_dev_imgext;

        if (isset($id) && isset($ext) && $ext != '') {
            return "${id}.${ext}";
        } else {
            return null;
        }
    }
}
