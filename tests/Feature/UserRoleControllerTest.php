<?php

namespace Tests\Feature;

use App\Models\MasterMenu;
use App\Models\MasterMenuRoleMapping;
use App\Models\MasterRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserRoleControllerTest extends TestCase
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
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable();
            }
        });

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
                $table->string('trans_key')->nullable();
                $table->string('path')->nullable();
                $table->string('icon')->nullable();
                $table->string('nav_type')->nullable();
                $table->string('web_type')->nullable();
                $table->integer('order_index')->nullable();
                $table->json('available_actions')->nullable();
                $table->boolean('is_active')->default(true);
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

    public function test_role_me_returns_current_user_menu_tree(): void
    {
        $role = MasterRole::query()->create([
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'admin',
        ]);

        $parent = MasterMenu::query()->create([
            'menu_code' => 'DASHBOARD',
            'title' => 'Dashboard',
            'path' => '/dashboard',
            'web_type' => 'admin',
            'order_index' => 1,
            'available_actions' => ['view'],
            'is_active' => true,
        ]);

        $child = MasterMenu::query()->create([
            'menu_code' => 'USER_LIST',
            'parent_id' => $parent->id,
            'title' => 'User List',
            'path' => '/users',
            'web_type' => 'admin',
            'order_index' => 2,
            'available_actions' => ['view', 'create'],
            'is_active' => true,
        ]);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => $parent->id,
            'can_view' => true,
        ]);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => $child->id,
            'can_view' => true,
            'can_create' => true,
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->getJson('/api/public/roles/me');

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Administrator')
            ->assertJsonPath('data.children.0.name', 'Dashboard')
            ->assertJsonPath('data.children.0.children.0.name', 'User List')
            ->assertJsonPath('data.children.0.children.0.action.0', 'view');
    }

    public function test_get_all_roles_returns_paginated_roles(): void
    {
        MasterRole::query()->create([
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'admin',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->getJson('/api/public/roles');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.role_code', 'admin');
    }

    public function test_update_role_updates_boolean_permissions(): void
    {
        $role = MasterRole::query()->create([
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'admin',
        ]);

        $menu = MasterMenu::query()->create([
            'menu_code' => 'USER_LIST',
            'title' => 'User List',
            'path' => '/users',
            'web_type' => 'admin',
            'order_index' => 1,
            'available_actions' => ['view', 'create', 'edit'],
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->putJson('/api/public/roles/access', [
                'role_id' => $role->id,
                'payload' => [
                    [
                        'menu_id' => $menu->id,
                        'action' => ['view', 'edit'],
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Role updated successfully.');

        $this->assertDatabaseHas('master_menu_role_mapping_v2', [
            'role_id' => $role->id,
            'menu_id' => $menu->id,
            'can_view' => true,
            'can_create' => false,
            'can_edit' => true,
        ]);
    }
}
