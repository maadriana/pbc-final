<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['client']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        // Check if user can create users (only system admin)
        if (!auth()->user()->canCreateUsers()) {
            abort(403, 'You do not have permission to create users.');
        }

        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        // Check if user can create users (only system admin)
        if (!auth()->user()->canCreateUsers()) {
            abort(403, 'You do not have permission to create users.');
        }

        // Validation with all valid roles
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => [
                'required',
                Rule::in([
                    User::ROLE_SYSTEM_ADMIN,
                    User::ROLE_ENGAGEMENT_PARTNER,
                    User::ROLE_MANAGER,
                    User::ROLE_ASSOCIATE,
                    User::ROLE_CLIENT
                ])
            ],
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // If creating a client user, also create client record
        if ($request->role === User::ROLE_CLIENT) {
            // Redirect to client creation page with the user_id
            return redirect()
                ->route('admin.clients.create', ['user_id' => $user->id])
                ->with('success', 'Client user created. Please complete the client company information.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['client', 'createdClients', 'createdProjects', 'uploadedDocuments']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => [
                'required',
                Rule::in([
                    User::ROLE_SYSTEM_ADMIN,
                    User::ROLE_ENGAGEMENT_PARTNER,
                    User::ROLE_MANAGER,
                    User::ROLE_ASSOCIATE,
                    User::ROLE_CLIENT
                ])
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Update user data
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Check if user has dependencies
        if ($user->client) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Cannot delete user with associated client record.');
        }

        if ($user->createdClients()->count() > 0) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Cannot delete user who has created client records.');
        }

        if ($user->createdProjects()->count() > 0) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Cannot delete user who has created projects.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
