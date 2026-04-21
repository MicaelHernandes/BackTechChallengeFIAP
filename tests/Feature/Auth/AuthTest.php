<?php

use App\Enums\UserRole;
use App\Models\User;

describe('Auth endpoints', function () {

    it('allows a user to login with valid credentials', function () {
        $user = User::factory()->create([
            'email'    => 'test@oficina.com',
            'password' => bcrypt('secret123'),
            'role'     => UserRole::Admin,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@oficina.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user'])
            ->assertJsonPath('user.role', UserRole::Admin->value);
    });

    it('rejects login with wrong password', function () {
        User::factory()->create([
            'email'    => 'test2@oficina.com',
            'password' => bcrypt('correct'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test2@oficina.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    });

    it('returns authenticated user on /me', function () {
        $user = User::factory()->create(['role' => UserRole::Mechanic]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('role', UserRole::Mechanic->value);
    });

    it('returns 401 on /me without token', function () {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    });

    it('revokes token on logout', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/logout');

        $response->assertOk();

        // Token should no longer work
        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    });
});
