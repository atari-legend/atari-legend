<?php

namespace App\Providers\Auth;

use App\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class UserProvider extends EloquentUserProvider
{
    /**
     * Validate the credentials.
     *
     * Unfortunately the DB is using a byzantine scheme for credentials that
     * we have to respect for now.
     *
     * @param Authenticatable $user        User attempting authentication
     * @param array           $credentials User credentials
     *
     * @return boolean true if the user is authenticated, false otherwise
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (!($user instanceof User)) {
            throw new \Exception("Unsupported Authenticatable type: $user");
        }

        // Hash the password with sha512. This was initially done client-side
        // so that only the hashed password would get sent. With HTTPS is this
        // not needed anymore
        $sha512Password = hash('sha512', $credentials['password']);
        // Then hash it again, with the user salt from the database
        $hashedPassword = hash('sha512', $sha512Password.$user->salt);

        return $user->inactive === 0 && $user->sha512_password === $hashedPassword;
    }
}
