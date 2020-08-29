<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class HelperUserTest extends TestCase
{
    public function testNullUser()
    {
        $this->assertEquals(
            "Former user",
            Helper::user(NULL)
        );
    }

    public function testNonNullUser()
    {
        $user = new \App\User;
        $user->userid = "John";

        $this->assertEquals(
            "John",
            Helper::user($user)
        );
    }

}
