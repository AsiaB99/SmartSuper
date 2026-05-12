<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_shows_public_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('SmartSuper');
        $response->assertSeeText('Empieza tu lista');
    }
}
