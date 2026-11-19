<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
    }

    private function user(string $role = 'petugas', bool $active = true): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role.($active ? '' : '-off').'@qlinic.test',
            'password' => 'secret123',
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Masuk Panel Qlinic');
    }

    public function test_staff_can_log_in(): void
    {
        $user = $this->user();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect('/admin/antrian');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->user();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = $this->user('petugas', false);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/antrian')->assertRedirect('/admin/login');
        $this->get('/admin/jadwal')->assertRedirect('/admin/login');
    }

    public function test_petugas_cannot_open_schedule_but_admin_can(): void
    {
        $this->actingAs($this->user('petugas'))->get('/admin/jadwal')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/admin/jadwal')->assertOk()->assertSee('Jadwal Praktik');
    }

    public function test_user_without_staff_role_is_logged_out(): void
    {
        $user = $this->user('guest');

        $this->actingAs($user)->get('/admin/antrian')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAs($this->user())->post('/admin/logout')->assertRedirect('/admin/login');

        $this->assertGuest();
    }
}