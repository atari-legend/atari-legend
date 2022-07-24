<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagazineIndexType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name'];

    public function magazineIndices()
    {
        return $this->hasMany(MagazineIndex::class);
    }
}
