<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomeLandingTest extends TestCase
{
    public function test_guest_home_renders_chronologic_landing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Rotacje pracowników')
            ->assertSee('nie w Excelu')
            ->assertSee('Zaloguj się')
            ->assertDontSee('Poznaj boty')
            ->assertDontSee('MK TECHNIC');
    }

    public function test_login_screen_uses_chronologic_branding(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Zaloguj się')
            ->assertSee('E-mail')
            ->assertSee('Wejście do systemu')
            ->assertDontSee('MK TECHNIC');
    }
}
