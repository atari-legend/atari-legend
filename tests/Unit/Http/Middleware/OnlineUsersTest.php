<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\OnlineUsers;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OnlineUsersTest extends TestCase
{
    use RefreshDatabase;

    public function testNoUsers()
    {
        $request = new Request();
        $middleware = new OnlineUsers();

        $middleware->handle($request, function ($req) {
            $this->assertTrue($req->attributes->get('onlineUsers')->isEmpty());
            $this->assertTrue($req->attributes->get('pastDayUsers')->isEmpty());
        });
    }

    public function testUsers()
    {
        $user = User::factory()->create();

        $request = new Request();
        $middleware = new OnlineUsers();

        $middleware->handle($request, function ($req) use ($user) {
            $this->assertEquals(1, $req->attributes->get('onlineUsers')->count());
            $this->assertEquals(1, $req->attributes->get('pastDayUsers')->count());

            $this->assertEquals($user->user_id, $req->attributes->get('onlineUsers')->first()->user_id);
            $this->assertEquals($user->user_id, $req->attributes->get('pastDayUsers')->first()->user_id);
        });
    }
}
