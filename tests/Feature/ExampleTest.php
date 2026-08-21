<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * '/' bukan lagi halaman welcome bawaan Laravel — guest diarahkan ke
     * /login (lihat routes/web.php), supaya link apa pun yg mengarah ke
     * '/' tidak pernah mendarat di halaman dev "Let's get started".
     */
    public function test_root_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
