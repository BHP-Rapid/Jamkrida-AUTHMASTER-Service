<?php

namespace Tests\Feature;

use App\Models\MasterMenu;
use App\Models\MasterMenuRoleMapping;
use App\Models\MasterRole;
use App\Models\User;
use App\Models\UserMitra;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InternalUserClientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.internal.token', 'internal-authmaster-token');

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
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->nullable();
            }
        });

        if (! Schema::hasTable('user_mitra')) {
            Schema::create('user_mitra', function (Blueprint $table): void {
                $table->id();
                $table->string('user_id')->unique();
                $table->unsignedBigInteger('mitra_id')->nullable();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('role')->nullable();
                $table->string('status')->nullable();
                $table->string('statusApproval')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('master_role')) {
            Schema::create('master_role', function (Blueprint $table): void {
                $table->id();
                $table->string('role_code')->nullable();
                $table->string('role_name')->nullable();
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('master_menus_v2')) {
            Schema::create('master_menus_v2', function (Blueprint $table): void {
                $table->id();
                $table->string('menu_code')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('title')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('master_menu_role_mapping_v2')) {
            Schema::create('master_menu_role_mapping_v2', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('menu_id');
                $table->boolean('can_view')->default(false);
                $table->boolean('can_create')->default(false);
                $table->boolean('can_edit')->default(false);
                $table->boolean('can_delete')->default(false);
                $table->boolean('can_approve')->default(false);
                $table->timestamps();
            });
        }
    }

    public function test_internal_user_route_requires_valid_bearer_token(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/internal/users/{$user->id}");

        $response
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized internal service request.',
            ]);
    }

    public function test_internal_user_route_returns_user_when_token_is_valid(): void
    {
        $user = User::factory()->create([
            'name' => 'Internal User',
            'email' => 'internal@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->withToken('internal-authmaster-token')
            ->getJson("/api/internal/users/{$user->id}");

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User berhasil diambil.',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Internal User',
                    'email' => 'internal@example.com',
                ],
            ]);
    }

    public function test_internal_user_route_returns_not_found_when_user_missing(): void
    {
        $response = $this->withToken('internal-authmaster-token')
            ->getJson('/api/internal/users/999999');

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ]);
    }

    public function test_internal_context_route_returns_admin_user_context(): void
    {
        $role = MasterRole::query()->create([
            'id' => 1,
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'internal',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'name' => 'Admin Context',
            'email' => 'context-admin@example.com',
            'role' => 'admin',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->getJson('/api/internal/users/ADM001/context');

        $response
            ->assertOk()
            ->assertJsonPath('data.user_id', 'ADM001')
            ->assertJsonPath('data.auth_type', 'admin')
            ->assertJsonPath('data.role_id', 1)
            ->assertJsonPath('data.role_code', 'admin')
            ->assertJsonPath('data.user.user_id', 'ADM001')
            ->assertJsonPath('data.user.name', 'Admin Context')
            ->assertJsonPath('data.user.email', 'context-admin@example.com');
    }

    public function test_internal_permission_check_route_returns_allowed_status(): void
    {
        $role = MasterRole::query()->create([
            'id' => 1,
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'internal',
        ]);

        $menu = new MasterMenu([
            'menu_code' => 'USR_LIST',
            'title' => 'User List',
        ]);
        $menu->id = 19;
        $menu->save();

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
            'role_id' => 1,
        ]);

        $userToken = JWTAuth::fromUser($user);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => 19,
            'can_view' => true,
        ]);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->postJson('/api/internal/permissions/check', [
                'user_id' => 'ADM001',
                'menu_code' => 'USR_LIST',
                'action' => 'view',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.allowed', true)
            ->assertJsonPath('data.user.user_id', 'ADM001')
            ->assertJsonPath('data.user.role_code', 'admin');
    }

    public function test_internal_permission_check_allows_any_requested_action(): void
    {
        $role = MasterRole::query()->create([
            'id' => 1,
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'internal',
        ]);

        $menu = MasterMenu::query()->create([
            'menu_code' => 'PENJAMINAN',
            'title' => 'Penjaminan',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $userToken = JWTAuth::fromUser($user);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => $menu->id,
            'can_create' => true,
            'can_edit' => false,
        ]);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->postJson('/api/internal/permissions/check', [
                'user_id' => 'ADM001',
                'menu_code' => 'PENJAMINAN',
                'action' => 'edit,create',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.allowed', true)
            ->assertJsonPath('data.action', 'edit,create')
            ->assertJsonPath('data.actions', ['edit', 'create']);
    }

    public function test_internal_role_check_route_returns_allowed_status_for_mitra(): void
    {
        MasterRole::query()->create([
            'id' => 5,
            'role_code' => 'mitra',
            'role_name' => 'Mitra',
            'type' => 'external',
        ]);

        $user = UserMitra::query()->create([
            'user_id' => 'MTR001',
            'mitra_id' => 2001,
            'name' => 'Mitra Context',
            'email' => 'mitra-context@example.com',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'approved',
        ]);

        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->postJson('/api/internal/roles/check', [
                'user_id' => 'MTR001',
                'roles' => ['mitra', '5'],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.allowed', true)
            ->assertJsonPath('data.user.auth_type', 'mitra')
            ->assertJsonPath('data.user.user_id', 'MTR001');
    }

    public function test_internal_context_route_requires_forwarded_user_token(): void
    {
        $response = $this->withToken('internal-authmaster-token')
            ->getJson('/api/internal/users/ADM001/context');

        $response
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized: X-User-Token missing.',
            ]);
    }

    public function test_internal_permission_check_blocks_mismatched_forwarded_user_token(): void
    {
        $role = MasterRole::query()->create([
            'id' => 1,
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'internal',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $otherUser = User::factory()->create([
            'user_id' => 'ADM999',
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->postJson('/api/internal/permissions/check', [
                'user_id' => $otherUser->user_id,
                'menu_code' => 'USR_LIST',
                'action' => 'view',
            ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden: requested user does not match forwarded token.',
            ]);
    }
}
