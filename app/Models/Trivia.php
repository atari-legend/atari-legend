<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trivia extends Model
{
    use HasFactory;

    protected $primaryKey = 'trivia_id';
    public $timestamps = false;

    protected $fillable = ['trivia_text'];
}
