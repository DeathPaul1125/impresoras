<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Sin portada: la raíz manda al dashboard y, sin sesión, termina en el login.
        $this->get('/')->assertRedirect('/dashboard');
        $this->followingRedirects()->get('/')->assertOk()->assertSee('Iniciar sesión');
    }
}
