<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Helper for User.
 */
class UserHelper
{
    /**
     * Get the standard validation rules to create or modify a user profile.
     *
     * @param  \App\Models\User  $currentUser  User to get the validation rules for. Allows
     *                                         ignoring that user in the uniqueness constraint
     *                                         for the email field
     */
    public static function validationRules(User $currentUser = null)
    {
        $rules = [
            'avatar'   => ['nullable', 'image'],
            'website'  => ['nullable', 'url'],
            'facebook' => ['nullable', 'url', 'starts_with:https://www.facebook.com/'],
            'twitter'  => ['nullable', 'url', 'starts_with:https://twitter.com/'],
            'af'       => ['nullable', 'url', 'starts_with:https://www.atari-forum.com/'],
        ];

        if ($currentUser !== null) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($currentUser->user_id, 'user_id')];
        } else {
            $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users'];
        }

        return $rules;
    }

    /**
     * Hash a password with the given salt.
     *
     * @param  string  $password  Plain-text password to hash
     * @param  string  $salt  Salt to use in the hashing
     * @return string Hashed password
     */
    public static function hashPassword(string $password, string $salt)
    {
        // Hash the password with sha512. This was initially done client-side
        // so that only the hashed password would get sent. With HTTPS is this
        // not needed anymore
        $sha512Password = hash('sha512', $password);
        // Then hash it again, with the user salt
        return hash('sha512', $sha512Password.$salt);
    }

    /**
     * Generate a random salt for password hashing.
     *
     * @return string Random salt
     */
    public static function salt()
    {
        return hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
    }
}
