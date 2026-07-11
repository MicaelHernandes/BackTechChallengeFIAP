<?php

use App\Enums\UserRole;
use App\Models\User;

describe('Auth endpoints', function () {

    it('allows a user to login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@oficina.com',
            'password' => bcrypt('secret123'),
            'role' => UserRole::Admin,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@oficina.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user'])
            ->assertJsonPath('user.role', UserRole::Admin->value);
    });

    it('rejects login with wrong password', function () {
        User::factory()->create([
            'email' => 'test2@oficina.com',
            'password' => bcrypt('correct'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test2@oficina.com',
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
        $user = User::factory()->create();
        $newToken = $user->createToken('test');
        $tokenId = $newToken->accessToken->id;

        $response = $this->withToken($newToken->plainTextToken)
            ->postJson('/api/auth/logout');

        $response->assertOk();

        // O token foi removido do banco (currentAccessToken()->delete() no AuthController).
        // Não refazemos a chamada autenticada com o mesmo token aqui: dentro de um único
        // teste, o guard 'sanctum' (RequestGuard) cacheia o usuário resolvido na primeira
        // chamada e reaproveita esse cache em chamadas HTTP subsequentes do mesmo processo,
        // mascarando a revogação — uma peculiaridade do test harness, não do comportamento
        // real da aplicação (cada request de produção resolve o guard do zero).
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    });
});
