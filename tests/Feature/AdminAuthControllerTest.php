<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthControllerTest extends TestCase
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
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable();
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->nullable();
            }
            if (! Schema::hasColumn('users', 'status_approval')) {
                $table->string('status_approval')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_delete')) {
                $table->boolean('is_delete')->default(false);
            }
            if (! Schema::hasColumn('users', 'login_attempts')) {
                $table->integer('login_attempts')->nullable();
            }
            if (! Schema::hasColumn('users', 'suspend_until')) {
                $table->dateTime('suspend_until')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_login')) {
                $table->dateTime('last_login')->nullable();
            }
        });

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

    public function test_login_admin_sends_otp_for_approved_active_user(): void
    {
        Mail::fake();

        \App\Models\SettingProductDetail::query()->create(['key' => 'OTP_DURATION_GENERAL_SETTINGS', 'value' => '300']);
        \App\Models\SettingProductDetail::query()->create(['key' => 'RESENT_OTP_INTERVAL_GENERAL_SETTINGS', 'value' => '60']);
        \App\Models\SettingProductDetail::query()->create(['key' => 'MAX_FAILED_LOGIN_ATTEMP_GENERAL_SETTINGS', 'value' => '3']);
        \App\Models\SettingProductDetail::query()->create(['key' => 'FAILED_LOGIN_SUSPENDED_GENERAL_SETTINGS', 'value' => '60']);

        User::factory()->create([
            'user_id' => 1001,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
            'status_approval' => 'approved',
            'is_delete' => false,
        ]);

        $response = $this->postJson('/api/public/auth/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OTP telah dikirim ke email Anda.',
                'data' => [
                    'user_id' => 1001,
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                    'resentOTP' => 60,
                ],
            ]);
    }

    public function test_verify_admin_otp_returns_jwt_token(): void
    {
        $user = User::factory()->create([
            'user_id' => 1001,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
            'status_approval' => 'approved',
        ]);

        \App\Models\OTPVerification::query()->create([
            'user_id' => 1001,
            'email' => 'admin@example.com',
            'otp' => '12345',
            'valid_before' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/public/auth/admin/verify-otp', [
            'user_id' => 1001,
            'otp' => '12345',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.user_id', 1001)
            ->assertJsonPath('data.user.email', 'admin@example.com');

        $this->assertIsString($response->json('data.token'));
        $this->assertIsString($response->json('data.access_token'));
        $this->assertIsString($response->json('data.refresh_token'));

        $tokenUser = JWTAuth::setToken($response->json('data.access_token'))
            ->getPayload()
            ->get('user');

        $this->assertSame(1001, $tokenUser['user_id']);
        $this->assertSame('Admin User', $tokenUser['name']);
        $this->assertSame('admin@example.com', $tokenUser['email']);
    }

    public function test_refresh_admin_token_returns_new_token_payload(): void
    {
        $user = User::factory()->create([
            'user_id' => 1001,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
            'status_approval' => 'approved',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->postJson('/api/public/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Refresh token berhasil.')
            ->assertJsonPath('data.user.user_id', 1001)
            ->assertJsonPath('data.user.email', 'admin@example.com');

        $this->assertIsString($response->json('data.token'));
        $this->assertIsString($response->json('data.refresh_token'));

        $tokenPayload = JWTAuth::setToken($response->json('data.access_token'))->getPayload();
        $tokenUser = $tokenPayload->get('user');

        $this->assertSame('admin', $tokenPayload->get('auth_type'));
        $this->assertSame(1001, $tokenUser['user_id']);
        $this->assertSame('Admin User', $tokenUser['name']);
    }

    public function test_refresh_admin_token_accepts_refresh_token_payload(): void
    {
        $user = User::factory()->create([
            'user_id' => 1001,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
            'status_approval' => 'approved',
        ]);

        \App\Models\OTPVerification::query()->create([
            'user_id' => 1001,
            'email' => 'admin@example.com',
            'otp' => '12345',
            'valid_before' => now()->addMinutes(5),
        ]);

        $verifyResponse = $this->postJson('/api/public/auth/admin/verify-otp', [
            'user_id' => 1001,
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
            ->assertJsonPath('data.user.user_id', 1001)
            ->assertJsonPath('data.user.email', 'admin@example.com');

        $this->assertIsString($response->json('data.access_token'));
        $this->assertIsString($response->json('data.refresh_token'));
    }

    public function test_refresh_admin_token_returns_unauthorized_for_invalid_refresh_token(): void
    {
        $response = $this->postJson('/api/public/auth/refresh', [
            'refresh_token' => 'invalid-refresh-token',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized: refresh token invalid or expired.',
            ]);
    }

    public function test_refresh_admin_token_cannot_reuse_old_refresh_token_after_rotation(): void
    {
        User::factory()->create([
            'user_id' => 1001,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
            'status_approval' => 'approved',
        ]);

        \App\Models\OTPVerification::query()->create([
            'user_id' => 1001,
            'email' => 'admin@example.com',
            'otp' => '12345',
            'valid_before' => now()->addMinutes(5),
        ]);

        $verifyResponse = $this->postJson('/api/public/auth/admin/verify-otp', [
            'user_id' => 1001,
            'otp' => '12345',
        ]);

        $oldRefreshToken = $verifyResponse->json('data.refresh_token');

        $firstRefreshResponse = $this->postJson('/api/public/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);

        $firstRefreshResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $secondRefreshResponse = $this->postJson('/api/public/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);

        $secondRefreshResponse
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized: refresh token invalid or expired.',
            ]);
    }
}
