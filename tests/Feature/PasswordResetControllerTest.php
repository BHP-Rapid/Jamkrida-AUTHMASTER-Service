<?php

namespace Tests\Feature;

use App\Models\UrlVerification;
use App\Models\User;
use App\Models\UserMitra;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetControllerTest extends TestCase
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
                $table->boolean('is_delete')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('url_verification')) {
            Schema::create('url_verification', function (Blueprint $table): void {
                $table->id();
                $table->string('user_id');
                $table->string('url_key')->unique();
                $table->dateTime('valid_before');
                $table->timestamps();
            });
        }
    }

    public function test_resend_reset_password_email_creates_url_record_for_admin(): void
    {
        Mail::fake();

        User::factory()->create([
            'user_id' => 'ADM001',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/public/auth/reset-password/resend-email', [
            'user_type' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('url_verification', [
            'user_id' => 'ADM001',
        ]);
    }

    public function test_validate_reset_url_returns_user_context_for_mitra(): void
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

        UrlVerification::query()->create([
            'user_id' => 'MTR001',
            'url_key' => 'valid-key',
            'valid_before' => now()->addHours(24),
        ]);

        $response = $this->getJson('/api/public/auth/reset-password/validate?url_key=valid-key&user_type=mitra');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 'MTR001')
            ->assertJsonPath('data.email', 'mitra@example.com');
    }

    public function test_reset_password_updates_admin_password(): void
    {
        User::factory()->create([
            'user_id' => 'ADM001',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'oldpass123',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/public/auth/reset-password', [
            'user_id' => 'ADM001',
            'user_type' => 'admin',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password berhasil diperbarui, Silahkan login kembali.');

        $user = User::query()->where('user_id', 'ADM001')->firstOrFail();
        $this->assertTrue(Hash::check('newpass123', $user->password));
    }
}
