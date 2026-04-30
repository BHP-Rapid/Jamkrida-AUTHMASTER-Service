<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MappingValueControllerTest extends TestCase
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
        });

        if (! Schema::hasTable('mapping_value')) {
            Schema::create('mapping_value', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sequence')->nullable();
                $table->string('key')->nullable();
                $table->string('value')->nullable();
                $table->string('label')->nullable();
                $table->string('option1')->nullable();
                $table->string('option2')->nullable();
                $table->string('option3')->nullable();
                $table->string('option4')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_mapping_value_index_requires_jwt_token(): void
    {
        $response = $this->getJson('/api/public/master/mapping-values');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized: token missing.');
    }

    public function test_mapping_value_index_returns_all_data(): void
    {
        \App\Models\MappingValue::query()->create([
            'key' => 'currency',
            'value' => 'IDR',
            'label' => 'Rupiah',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->getJson('/api/public/master/mapping-values');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.key', 'currency')
            ->assertJsonPath('data.0.value', 'IDR');
    }

    public function test_mapping_value_get_by_key_returns_matching_records(): void
    {
        \App\Models\MappingValue::query()->create([
            'key' => 'industry_type',
            'value' => 'tech',
            'label' => 'Technology',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->getJson('/api/public/master/mapping-values/key/industry_type');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.label', 'Technology');
    }

    public function test_public_provinces_route_returns_nusa_data(): void
    {
        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->getJson('/api/public/master/provinces?name=Jawa');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_internal_mapping_value_route_requires_internal_service_token(): void
    {
        $response = $this->getJson('/api/int/master/mapping-values');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized internal service request.');
    }

    public function test_internal_mapping_value_route_requires_forwarded_user_token(): void
    {
        \App\Models\MappingValue::query()->create([
            'key' => 'currency',
            'value' => 'USD',
            'label' => 'US Dollar',
        ]);

        $response = $this->withToken('internal-authmaster-token')
            ->getJson('/api/int/master/mapping-values');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized: X-User-Token missing.');
    }

    public function test_internal_mapping_value_route_returns_all_data_with_internal_and_forwarded_user_tokens(): void
    {
        \App\Models\MappingValue::query()->create([
            'key' => 'currency',
            'value' => 'USD',
            'label' => 'US Dollar',
        ]);

        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->getJson('/api/int/master/mapping-values');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.value', 'USD');
    }

    public function test_internal_provinces_route_returns_nusa_data_with_internal_and_forwarded_user_tokens(): void
    {
        $user = User::factory()->create([
            'user_id' => 'ADM001',
            'role' => 'admin',
        ]);

        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken('internal-authmaster-token')
            ->withHeader('X-User-Token', $userToken)
            ->getJson('/api/int/master/provinces?name=Jawa');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data'));
    }
}
