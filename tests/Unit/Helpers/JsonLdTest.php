<?php

namespace Tests\Unit\Helpers;

use App\Helpers\JsonLd;
use PHPUnit\Framework\TestCase;

class JsonLdTest extends TestCase
{
    public function test()
    {
        $ld = (new JsonLd('type', 'url'))
            ->add('first', '1')
            ->add('second', ['a', 'b']);

        $this->assertEquals(
            [
                '@context' => 'http://schema.org',
                '@type'    => 'type',
                'url'      => 'url',
                'first'    => '1',
                'second'   => ['a', 'b'],
            ],
            json_decode($ld->json(), true)
        );
    }
}
