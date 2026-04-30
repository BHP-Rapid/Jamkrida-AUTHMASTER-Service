<?php

namespace Tests\Feature;

use App\Models\MasterMenu;
use App\Models\MasterMenuRoleMapping;
use App\Models\MasterRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
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
                $table->text('available_actions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Route::middleware('jwt.auth')->get('/api/testing/jwt', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['jwt.auth', 'check.role:admin,1'])->get('/api/testing/role', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['jwt.auth', 'check.permission:19,view'])->get('/api/testing/permission', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['jwt.auth', 'check.permission:USR_LIST,view'])->get('/api/testing/permission-by-code', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['jwt.auth', 'check.permission:PENJAMINAN,edit,create'])->put('/api/testing/permission-any', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware([
            'jwt.auth',
            'check.role:mitra,head_admin_mitra',
            'check.permission:mitra=mitra.claim:view,create|head_admin_mitra=head_admin_mitra.claim:view,create',
        ])->get('/api/testing/permission-by-role', function () {
            return response()->json(['success' => true]);
        });
    }

    public function test_jwt_auth_middleware_rejects_missing_token(): void
    {
        $response = $this->getJson('/api/testing/jwt');

        $response
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized: token missing.',
            ]);
    }

    public function test_check_role_allows_matching_role(): void
    {
        $role = MasterRole::query()->create([
            'id' => 1,
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'type' => 'internal',
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/api/testing/role');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_check_permission_allows_mapped_permission(): void
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
            'is_active' => true,
        ]);
        $menu->id = 19;
        $menu->save();

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => 19,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
            'can_approve' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/api/testing/permission');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_check_permission_allows_menu_code_identifier(): void
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
            'is_active' => true,
        ]);
        $menu->id = 19;
        $menu->save();

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => 19,
            'can_view' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/api/testing/permission-by-code');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_check_permission_blocks_missing_permission(): void
    {
        MasterRole::query()->create([
            'id' => 2,
            'role_code' => 'reviewer',
            'role_name' => 'Reviewer',
            'type' => 'internal',
        ]);

        $user = User::factory()->create([
            'role' => 'reviewer',
            'role_id' => 2,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/api/testing/permission');

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden: insufficient permission.',
            ]);
    }

    public function test_check_permission_allows_any_configured_action(): void
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
            'is_active' => true,
        ]);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $role->id,
            'menu_id' => $menu->id,
            'can_create' => true,
            'can_edit' => false,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->putJson('/api/testing/permission-any');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_check_permission_allows_role_specific_permission_expression(): void
    {
        MasterRole::query()->create([
            'id' => 1,
            'role_code' => 'mitra',
            'role_name' => 'Mitra',
            'type' => 'external',
        ]);

        $headAdminRole = MasterRole::query()->create([
            'id' => 2,
            'role_code' => 'head_admin_mitra',
            'role_name' => 'Head Admin Mitra',
            'type' => 'external',
        ]);

        MasterMenu::query()->create([
            'menu_code' => 'mitra.claim',
            'title' => 'Mitra Claim',
            'is_active' => true,
        ]);

        $headAdminMenu = MasterMenu::query()->create([
            'menu_code' => 'head_admin_mitra.claim',
            'title' => 'Head Admin Mitra Claim',
            'is_active' => true,
        ]);

        MasterMenuRoleMapping::query()->create([
            'role_id' => $headAdminRole->id,
            'menu_id' => $headAdminMenu->id,
            'can_view' => false,
            'can_create' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'head_admin_mitra',
            'role_id' => $headAdminRole->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/api/testing/permission-by-role');

        $response->assertOk()->assertJson(['success' => true]);
    }
}
