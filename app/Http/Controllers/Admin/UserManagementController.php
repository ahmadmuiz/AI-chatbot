<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function disable(User $user): RedirectResponse
    {
        // Prevent self-disable
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot disable your own account.']);
        }

        $user->update([
            'is_active'   => false,
            'disabled_at' => now(),
        ]);

        AuditService::log(
            'user.disabled',
            "Admin disabled account for {$user->name} ({$user->email})",
            $user,
            ['admin_id' => auth()->id()],
        );

        return back()->with('success', "Account for {$user->name} has been disabled.");
    }

    public function enable(User $user): RedirectResponse
    {
        $user->update([
            'is_active'   => true,
            'disabled_at' => null,
        ]);

        AuditService::log(
            'user.enabled',
            "Admin re-enabled account for {$user->name} ({$user->email})",
            $user,
            ['admin_id' => auth()->id()],
        );

        return back()->with('success', "Account for {$user->name} has been re-enabled.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        // Generate a secure 12-character temporary password
        $tempPassword = Str::password(12, letters: true, numbers: true, symbols: true, spaces: false);

        $user->update([
            'password'             => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        AuditService::log(
            'user.password_reset',
            "Admin reset password for {$user->name} ({$user->email})",
            $user,
            ['admin_id' => auth()->id()],
        );

        // Flash the temp password once — it will not be stored in plaintext
        return back()->with([
            'success'       => "Password reset for {$user->name}.",
            'temp_password' => $tempPassword,
            'reset_user'    => $user->name,
        ]);
    }
}
