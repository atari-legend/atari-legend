<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Changelog extends Model implements Feedable
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

    protected $casts = [
        'timestamp' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => $this->action . ' in ' . $this->section . ': ' . $this->section_name,
            'summary'    => $this->action . ' in ' . $this->section . ': ' . $this->section_name
                . ', sub-section' . $this->sub_section . ': ' . $this->sub_section_name,
            'updated'    => $this->timestamp,
            // Use an ID so that items in the feed have different IDs
            // The ID is effectively ignored in the Changelog page
            'link'       => route('changelog.index', ['id' => $this->getKey()]),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
