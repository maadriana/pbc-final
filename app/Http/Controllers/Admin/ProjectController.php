<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Client;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->paginate(15);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'active',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['clients', 'pbcRequests', 'creator']);
        $availableClients = Client::whereDoesntHave('projects', function($query) use ($project) {
            $query->where('project_id', $project->id);
        })->get();

        return view('admin.projects.show', compact('project', 'availableClients'));
    }

    public function edit(Project $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        // Check if project has active PBC requests
        if ($project->pbcRequests()->count() > 0) {
            return redirect()
                ->route('admin.projects.index')
                ->with('error', 'Cannot delete project with active PBC requests.');
        }

        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    public function assignClient(Request $request, Project $project)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id'
        ]);

        // Check if client is already assigned
        if ($project->clients()->where('client_id', $request->client_id)->exists()) {
            return redirect()
                ->route('admin.projects.show', $project)
                ->with('error', 'Client is already assigned to this project.');
        }

        $project->clients()->attach($request->client_id, [
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Client assigned to project successfully.');
    }

    public function removeClient(Project $project, Client $client)
    {
        $project->clients()->detach($client->id);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Client removed from project successfully.');
    }
}
