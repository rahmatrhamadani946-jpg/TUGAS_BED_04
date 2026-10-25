<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $user = new User([
            'name' => ucfirst($role),
            'email' => "{$role}@test.local",
            'password' => bcrypt('password123'),
        ]);
        $user->role = $role;
        $user->save();

        return $user;
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/user/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_register_user_baru_berole_user(): void
    {
        $this->post('/register', [
            'name' => 'Mahasiswa',
            'email' => 'mhs@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertSame('user', User::where('email', 'mhs@test.local')->first()->role);
    }

    public function test_register_tidak_bisa_menyisipkan_role_admin(): void
    {
        $this->post('/register', [
            'name' => 'Nakal',
            'email' => 'nakal@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertSame('user', User::where('email', 'nakal@test.local')->first()->role);
    }

    public function test_login_dan_logout(): void
    {
        $user = $this->makeUser('user');

        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_user_biasa_bisa_akses_dashboard_user(): void
    {
        $this->actingAs($this->makeUser('user'))
            ->get('/user/dashboard')
            ->assertOk();
    }

    public function test_user_biasa_ditolak_403_di_dashboard_admin(): void
    {
        $this->actingAs($this->makeUser('user'))
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_bisa_akses_dashboard_admin(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Statistik Pengguna');
    }

    public function test_admin_bisa_mengubah_role_dan_menghapus_user(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('user');

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $target), ['role' => 'admin']);
        $this->assertSame('admin', $target->fresh()->role);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));
        $this->assertModelMissing($target);
    }

    public function test_admin_tidak_bisa_mengubah_atau_menghapus_diri_sendiri(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->patch(route('admin.users.role', $admin), ['role' => 'user']);
        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));
        $this->assertModelExists($admin);
    }

    public function test_user_biasa_tidak_bisa_memanggil_endpoint_manajemen_user(): void
    {
        $user = $this->makeUser('user');
        $target = $this->makeUser('admin');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->assertModelExists($target);
    }
}
