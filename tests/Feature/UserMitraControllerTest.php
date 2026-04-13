<?php

namespace Tests\Feature;

use App\Models\UserMitra;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserMitraControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('tenant_mitra')) {
            Schema::create('tenant_mitra', function (Blueprint $table): void {
                $table->id();
                $table->string('mitra_id')->unique();
                $table->string('tenant_id')->nullable();
                $table->string('institution_id')->nullable();
                $table->string('parent_id')->nullable();
                $table->string('name')->nullable();
                $table->string('name_mitra')->nullable();
                $table->boolean('is_syariah')->default(false);
                $table->boolean('is_conventional')->default(true);
                $table->string('logo')->nullable();
                $table->string('primary_color')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->string('alias')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_mitra')) {
            Schema::create('user_mitra', function (Blueprint $table): void {
                $table->id();
                $table->string('user_id')->unique();
                $table->string('mitra_id');
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->timestamp('last_login')->nullable();
                $table->string('phone')->nullable();
                $table->string('role')->nullable();
                $table->string('status')->nullable();
                $table->string('statusApproval')->nullable();
                $table->timestamp('suspend_until')->nullable();
                $table->integer('login_attempts')->default(0);
                $table->timestamp('deleted_at')->nullable();
                $table->string('created_by')->nullable();
                $table->string('deleted_by')->nullable();
                $table->boolean('is_delete')->default(false);
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function test_store_register_creates_user_mitra(): void
    {
        Schema::table('user_mitra', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_mitra', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });

        \DB::table('tenant_mitra')->insert([
            'mitra_id' => 'MTR001',
            'name' => 'Mitra Satu',
            'name_mitra' => 'Mitra Satu',
            'alias' => 'MTR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/public/mitra-users/register', [
            'mitra_id' => 'MTR001',
            'role' => 'mitra',
            'email' => 'mitra@example.com',
            'name' => 'User Mitra',
            'phone' => '08123456789',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mitra_id', 'MTR001')
            ->assertJsonPath('data.role', 'mitra');
    }

    public function test_get_users_by_role_returns_paginated_user_mitra_data(): void
    {
        \DB::table('tenant_mitra')->insert([
            'mitra_id' => 'MTR001',
            'name' => 'Mitra Satu',
            'name_mitra' => 'Mitra Satu',
            'alias' => 'MTR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserMitra::query()->create([
            'user_id' => 'MTR2026001',
            'mitra_id' => 'MTR001',
            'name' => 'User Mitra',
            'email' => 'mitra@example.com',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'submitted',
            'is_delete' => false,
        ]);

        $actor = UserMitra::query()->create([
            'user_id' => 'MTR2026002',
            'mitra_id' => 'MTR001',
            'name' => 'Actor Mitra',
            'email' => 'actor@example.com',
            'password' => 'oldpass123',
            'role' => 'admin',
            'status' => 'active',
            'statusApproval' => 'submitted',
            'is_delete' => false,
        ]);

        $token = JWTAuth::fromUser($actor);

        $response = $this->withToken($token)
            ->getJson('/api/public/mitra-users/list');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.user_id', 'MTR2026001');
    }

    public function test_change_password_updates_current_user_mitra_password(): void
    {
        $user = UserMitra::query()->create([
            'user_id' => 'MTR2026001',
            'mitra_id' => 'MTR001',
            'name' => 'User Mitra',
            'email' => 'mitra@example.com',
            'password' => Hash::make('oldpass123'),
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'submitted',
            'is_delete' => false,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/public/mitra-users/change-password', [
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
