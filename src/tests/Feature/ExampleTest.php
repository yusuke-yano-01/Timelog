<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        // ログインしていない場合はリダイレクトされるため、302を期待
        $response = $this->get('/');

        // 認証が必要な場合は302リダイレクト、認証不要の場合は200
        $response->assertStatus(302);
    }
}
