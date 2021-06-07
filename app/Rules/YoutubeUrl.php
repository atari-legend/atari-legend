<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Waynestate\Youtube\ParseId;

/**
 * Check if a passed URL is a valid YouTube URL, by trying to
 * extract the video ID from it.
 */
class YoutubeUrl implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ParseId::fromUrl($value) !== '';
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid YouTube URL.';
    }
}
