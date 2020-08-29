<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PublisherDeveloper extends Model
{
    protected $table = "pub_dev";
    protected $primaryKey = "pub_dev_id";
    public $timestamps = false;
}
