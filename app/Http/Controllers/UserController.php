<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['first_name', 'last_name', 'username', 'email', 'bio', 'phone']);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }

    public function settings()
    {
        $user = Auth::user();
        return view('settings.index', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('settings')->with('success', 'Password updated successfully!');
    }

    public function updateNotifications(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sound_notifications' => 'boolean',
        ]);

        $user->update([
            'email_notifications' => $request->has('email_notifications'),
            'push_notifications' => $request->has('push_notifications'),
            'sound_notifications' => $request->has('sound_notifications'),
        ]);

        return redirect()->route('settings')->with('success', 'Notification preferences updated!');
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = \App\Models\User::where('id', '!=', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhere('username', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->select('id', 'first_name', 'last_name', 'username', 'avatar')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    public function updatePresence(Request $request)
    {
        $user = Auth::user();

        $online = $request->boolean('online', true);

        if ($online) {
            $user->markAsOnline();
        } else {
            $user->markAsOffline();
        }

        return response()->json(['status' => 'success']);
    }
}
