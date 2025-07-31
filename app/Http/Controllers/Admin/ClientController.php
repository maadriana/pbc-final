<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\Project;
use App\Http\Requests\StoreClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index()
    {
        $query = Client::with(['user', 'creator']);

        // Filter clients based on user role and project assignments
        if (!auth()->user()->isSystemAdmin()) {
            // Get project IDs where user is assigned
            $assignedProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');

            // Filter clients that are assigned to these projects
            $query->whereHas('projects', function($q) use ($assignedProjectIds) {
                $q->whereIn('projects.id', $assignedProjectIds);
            });
        }

        $clients = $query->latest()->paginate(15);

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        // Check permission
        if (!auth()->user()->canManageClients()) {
            abort(403, 'You do not have permission to create clients.');
        }

        return view('admin.clients.create');
    }

    public function store(StoreClientRequest $request)
    {
        // Check permission
        if (!auth()->user()->canManageClients()) {
            abort(403, 'You do not have permission to create clients.');
        }

        DB::transaction(function () use ($request) {
            // Create user account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'client',
            ]);

            // Create client
            Client::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'address' => $request->address,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        // Check if user can access this client
        if (!$this->canAccessClient($client)) {
            abort(403, 'You do not have permission to view this client.');
        }

        $client->load(['user', 'creator', 'projects.assignments.user', 'pbcRequests']);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        // Check if user can access and edit this client
        if (!$this->canAccessClient($client) || !auth()->user()->canManageClients()) {
            abort(403, 'You do not have permission to edit this client.');
        }

        return view('admin.clients.edit', compact('client'));
    }

    public function update(StoreClientRequest $request, Client $client)
    {
        // Check if user can access and edit this client
        if (!$this->canAccessClient($client) || !auth()->user()->canManageClients()) {
            abort(403, 'You do not have permission to update this client.');
        }

        DB::transaction(function () use ($request, $client) {
            // Update user account
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $client->user->update($userData);

            // Update client
            $client->update([
                'company_name' => $request->company_name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        });

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        // Only System Admin can delete clients
        if (!auth()->user()->isSystemAdmin()) {
            abort(403, 'Only System Admin can delete clients.');
        }

        // Check if client has active projects or PBC requests
        if ($client->projects()->count() > 0 || $client->pbcRequests()->count() > 0) {
            return redirect()
                ->route('admin.clients.index')
                ->with('error', 'Cannot delete client with active projects or PBC requests.');
        }

        DB::transaction(function () use ($client) {
            $user = $client->user;
            $client->delete();
            $user->delete();
        });

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    /**
     * Check if current user can access the given client
     */
    private function canAccessClient(Client $client)
    {
        // System Admin can access all clients
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        // Check if user is assigned to any project with this client
        $userProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
        $clientProjectIds = $client->projects()->pluck('projects.id');

        return $userProjectIds->intersect($clientProjectIds)->isNotEmpty();
    }

    /**
     * Get clients accessible to current user for dropdowns
     */
    public function getAccessibleClients()
    {
        $query = Client::with('user');

        if (!auth()->user()->isSystemAdmin()) {
            $assignedProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
            $query->whereHas('projects', function($q) use ($assignedProjectIds) {
                $q->whereIn('projects.id', $assignedProjectIds);
            });
        }

        return $query->orderBy('company_name')->get();
    }
}
