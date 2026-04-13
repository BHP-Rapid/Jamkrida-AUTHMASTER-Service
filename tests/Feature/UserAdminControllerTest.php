<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('users', 'user_id')) {
                $table->string('user_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable();
            }
            if (! Schema::hasColumn('users', 'mitra_id')) {
                $table->string('mitra_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->nullable();
            }
            if (! Schema::hasColumn('users', 'status_approval')) {
                $table->string('status_approval')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_delete')) {
                $table->boolean('is_delete')->default(false);
            }
            if (! Schema::hasColumn('users', 'deleted_by')) {
                $table->string('deleted_by')->nullable();
            }
        });

        if (! Schema::hasTable('tenant_mitra')) {
            Schema::create('tenant_mitra', function (Blueprint $table): void {
                $table->id();
                $table->string('mitra_id')->unique();
                $table->string('alias')->nullable();
                $table->string('name_mitra')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_store_register_creates_admin_user(): void
    {
        $response = $this->postJson('/api/public/admin-users/register', [
            'role' => 'admin',
            'email' => 'new-admin@example.com',
            'name' => 'New Admin',
            'phone' => '08123456789',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.mitra_id', 'JMKRD');
    }

    public function test_get_users_by_role_returns_paginated_data_for_super_admin(): void
    {
        User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin_mitra',
            'mitra_id' => 'MTR001',
            'status' => 'active',
            'status_approval' => 'approved',
            'is_delete' => false,
        ]);

        $actor = User::factory()->create([
            'user_id' => 'SUP001',
            'role' => 'super_admin',
            'mitra_id' => 'JMKRD',
        ]);

        $token = JWTAuth::fromUser($actor);

        $response = $this->withToken($token)
            ->getJson('/api/public/admin-users/list');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.role', 'admin_mitra');
    }

    public function test_change_password_updates_current_user_password(): void
    {
        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
            'password' => 'oldpass123',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/public/admin-users/change-password', [
                'current_password' => 'oldpass123',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Password changed successfully');

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }
}
