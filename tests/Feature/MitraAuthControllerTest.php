<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMitra;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MitraAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('user_mitra')) {
            Schema::create('user_mitra', function (Blueprint $table): void {
                $table->id();
                $table->string('user_id')->unique();
                $table->unsignedBigInteger('mitra_id')->nullable();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->dateTime('last_login')->nullable();
                $table->string('phone')->nullable();
                $table->string('role')->nullable();
                $table->string('status')->nullable();
                $table->string('statusApproval')->nullable();
                $table->integer('login_attempts')->nullable();
                $table->dateTime('suspend_until')->nullable();
                $table->boolean('is_delete')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_mitra')) {
            Schema::create('tenant_mitra', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('mitra_id')->unique();
                $table->string('tenant_id')->nullable();
                $table->boolean('is_conventional')->default(false);
                $table->boolean('is_syariah')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('setting_product_dtl')) {
            Schema::create('setting_product_dtl', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('hdr_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('lampiran')->nullable();
                $table->string('reason_claim')->nullable();
                $table->string('key')->unique();
                $table->string('value')->nullable();
                $table->boolean('is_mandatory')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('otp_verification')) {
            Schema::create('otp_verification', function (Blueprint $table): void {
                $table->id();
                $table->string('user_id');
                $table->string('email');
                $table->string('otp');
                $table->dateTime('valid_before');
                $table->timestamps();
            });
        }
    }

    public function test_login_mitra_sends_otp_and_returns_tipe_metod_mitra(): void
    {
        Mail::fake();

        \App\Models\SettingProductDetail::query()->create(['key' => 'OTP_DURATION_GENERAL_SETTINGS', 'value' => '300']);
        \App\Models\SettingProductDetail::query()->create(['key' => 'RESENT_OTP_INTERVAL_GENERAL_SETTINGS', 'value' => '60']);
        \App\Models\SettingProductDetail::query()->create(['key' => 'MAX_FAILED_LOGIN_ATTEMP_GENERAL_SETTINGS', 'value' => '3']);
        \App\Models\SettingProductDetail::query()->create(['key' => 'FAILED_LOGIN_SUSPENDED_GENERAL_SETTINGS', 'value' => '60']);

        \Illuminate\Support\Facades\DB::table('tenant_mitra')->insert([
            'mitra_id' => 2001,
            'tenant_id' => 'TNT001',
            'is_conventional' => true,
            'is_syariah' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\UserMitra::query()->create([
            'user_id' => 'MTR001',
            'mitra_id' => 2001,
            'name' => 'Mitra User',
            'email' => 'mitra@example.com',
            'password' => 'secret123',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'approved',
            'is_delete' => false,
        ]);

        $response = $this->postJson('/api/public/auth/mitra/login', [
            'user_id' => 'MTR001',
            'password' => 'secret123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OTP telah dikirim ke email Anda.',
                'data' => [
                    'user_id' => 'MTR001',
                    'email' => 'mitra@example.com',
                    'role' => 'mitra',
                    'resentOTP' => 60,
                    'tipe_metod_mitra' => 'conventional',
                ],
            ]);
    }

    public function test_verify_mitra_otp_returns_jwt_token(): void
    {
        \Illuminate\Support\Facades\DB::table('tenant_mitra')->insert([
            'mitra_id' => 2001,
            'tenant_id' => 'TNT001',
            'is_conventional' => true,
            'is_syariah' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserMitra::query()->create([
            'user_id' => 'MTR001',
            'mitra_id' => 2001,
            'name' => 'Mitra User',
            'email' => 'mitra@example.com',
            'password' => 'secret123',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'approved',
            'is_delete' => false,
        ]);

        \App\Models\OTPVerification::query()->create([
            'user_id' => 'MTR001',
            'email' => 'mitra@example.com',
            'otp' => '12345',
            'valid_before' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/public/auth/mitra/verify-otp', [
            'user_id' => 'MTR001',
            'otp' => '12345',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.user_id', 'MTR001')
            ->assertJsonPath('data.user.tenant_id', 'TNT001')
            ->assertJsonPath('data.user.email', 'mitra@example.com');

        $this->assertIsString($response->json('data.token'));
        $this->assertIsString($response->json('data.access_token'));
        $this->assertIsString($response->json('data.refresh_token'));

        $tokenUser = JWTAuth::setToken($response->json('data.access_token'))
            ->getPayload()
            ->get('user');

        $this->assertSame('MTR001', $tokenUser['user_id']);
        $this->assertSame(2001, $tokenUser['mitra_id']);
        $this->assertSame('Mitra User', $tokenUser['name']);
        $this->assertSame('mitra@example.com', $tokenUser['email']);
    }

    public function test_refresh_mitra_token_returns_new_token_payload(): void
    {
        $user = UserMitra::query()->create([
            'user_id' => 'MTR001',
            'mitra_id' => 2001,
            'name' => 'Mitra User',
            'email' => 'mitra@example.com',
            'password' => 'secret123',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'approved',
            'is_delete' => false,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/public/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Refresh token berhasil.')
            ->assertJsonPath('data.user.user_id', 'MTR001')
            ->assertJsonPath('data.user.email', 'mitra@example.com')
            ->assertJsonPath('data.user.mitra_id', 2001);

        $this->assertIsString($response->json('data.token'));
        $this->assertIsString($response->json('data.refresh_token'));
    }

    public function test_refresh_mitra_token_accepts_refresh_token_payload(): void
    {
        UserMitra::query()->create([
            'user_id' => 'MTR001',
            'mitra_id' => 2001,
            'name' => 'Mitra User',
            'email' => 'mitra@example.com',
            'password' => 'secret123',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'approved',
            'is_delete' => false,
        ]);

        \App\Models\OTPVerification::query()->create([
            'user_id' => 'MTR001',
            'email' => 'mitra@example.com',
            'otp' => '12345',
            'valid_before' => now()->addMinutes(5),
        ]);

        $verifyResponse = $this->postJson('/api/public/auth/mitra/verify-otp', [
            'user_id' => 'MTR001',
            'otp' => '12345',
        ]);

        $refreshToken = $verifyResponse->json('data.refresh_token');

        $response = $this->postJson('/api/public/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Refresh token berhasil.')
            ->assertJsonPath('data.user.user_id', 'MTR001')
            ->assertJsonPath('data.user.email', 'mitra@example.com')
            ->assertJsonPath('data.user.mitra_id', 2001);

        $this->assertIsString($response->json('data.access_token'));
        $this->assertIsString($response->json('data.refresh_token'));
    }

    public function test_ensure_mitra_middleware_allows_mitra_token_and_blocks_admin_token(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable();
            }
        });

        Route::middleware(['jwt.auth', 'ensure.mitra'])->get('/api/testing/mitra-only', function () {
            return response()->json(['success' => true]);
        });

        $mitra = UserMitra::query()->create([
            'user_id' => 'MTR001',
            'mitra_id' => 2001,
            'name' => 'Mitra User',
            'email' => 'mitra@example.com',
            'password' => 'secret123',
            'role' => 'mitra',
            'status' => 'active',
            'statusApproval' => 'approved',
            'is_delete' => false,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $mitraToken = JWTAuth::fromUser($mitra);
        $adminToken = JWTAuth::fromUser($admin);

        $this->withToken($mitraToken)
            ->getJson('/api/testing/mitra-only')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withToken($adminToken)
            ->getJson('/api/testing/mitra-only')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden: mitra token required.',
            ]);
    }
}
