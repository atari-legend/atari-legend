<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class HelperUserTest extends TestCase
{
    public function testNullUser()
    {
        $this->assertEquals(
            'Former user',
            Helper::user(null)
        );
    }

    public function testNonNullUser()
    {
        $user = new User();
        $user->userid = 'John';

        $this->assertEquals(
            'John',
            Helper::user($user)
        );
    }
}
