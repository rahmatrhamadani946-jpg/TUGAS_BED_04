<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Nilai plus: admin melihat daftar user, mengubah role, dan menghapus user.
 */
class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id')->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        // Admin tidak boleh menurunkan role dirinya sendiri (mencegah terkunci).
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $user->update($data);

        return back()->with('status', "Role {$user->name} diubah menjadi {$data['role']}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return back()->with('status', 'User berhasil dihapus.');
    }
}
