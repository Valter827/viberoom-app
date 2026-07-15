<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_no_longer_forces_server_creation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Друзья');
    }

    public function test_user_can_send_a_friend_request(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['username' => 'other_user']);

        $response = $this->actingAs($user)->post('/friends', ['username' => 'other_user']);

        $response->assertRedirect();
        $this->assertEquals('outgoing', $user->fresh()->relationshipStatusWith($other));
        $this->assertEquals('incoming', $other->fresh()->relationshipStatusWith($user));
    }

    public function test_user_can_accept_a_friend_request(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['username' => 'accept_target']);

        $this->actingAs($user)->post("/friends", ['username' => $other->username]);
        $response = $this->actingAs($other)->post("/friends/{$user->id}/accept");

        $response->assertRedirect();
        $this->assertEquals('friends', $user->fresh()->relationshipStatusWith($other));
        $this->assertEquals('friends', $other->fresh()->relationshipStatusWith($user));
    }

    public function test_user_can_view_another_users_profile_card(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['bio' => 'Hello there']);

        $response = $this->actingAs($user)->get("/users/{$other->id}/card");

        $response->assertOk();
        $response->assertJsonFragment(['bio' => 'Hello there']);
    }
}
