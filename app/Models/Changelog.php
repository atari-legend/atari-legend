<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    const INSERT = 'Insert';
    const UPDATE = 'Update';
    const DELETE = 'Delete';

    protected $table = 'change_log';
    protected $primaryKey = 'change_log_id';
    public $timestamps = false;

    protected $fillable = [
        'section', 'section_id', 'section_name',
        'sub_section', 'sub_section_id', 'sub_section_name',
        'user_id', 'action', 'timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
