<?php

namespace App\Providers\Auth;

use App\Helpers\UserHelper;
use App\Models\User;
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
     * @return bool true if the user is authenticated, false otherwise
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (! ($user instanceof User)) {
            throw new \Exception("Unsupported Authenticatable type: $user");
        }

        $hashedPassword = UserHelper::hashPassword($credentials['password'], $user->salt);

        return $user->inactive === User::ACTIVE && $user->sha512_password === $hashedPassword;
    }
}
