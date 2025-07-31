<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectAssignmentService
{
    public function assignUserToProject(Project $project, User $user, string $role)
    {
        // Validate role
        if (!in_array($role, array_keys(ProjectAssignment::getProjectRoles()))) {
            throw new \InvalidArgumentException("Invalid project role: {$role}");
        }

        // Validate user can be assigned to projects (MTC staff only)
        if ($user->isClient()) {
            throw new \InvalidArgumentException("Clients cannot be assigned to project teams");
        }

        // Check if role is already filled
        $existingAssignment = ProjectAssignment::where('project_id', $project->id)
                                              ->where('role', $role)
                                              ->first();

        if ($existingAssignment && $existingAssignment->user_id !== $user->id) {
            throw new \InvalidArgumentException("Role {$role} is already assigned to another user");
        }

        return ProjectAssignment::updateOrCreate(
            [
                'project_id' => $project->id,
                'role' => $role
            ],
            [
                'user_id' => $user->id
            ]
        );
    }

    public function removeUserFromProject(Project $project, User $user, string $role = null)
    {
        $query = ProjectAssignment::where('project_id', $project->id)
                                 ->where('user_id', $user->id);

        if ($role) {
            $query->where('role', $role);
        }

        return $query->delete();
    }

    public function updateProjectTeam(Project $project, array $assignments)
    {
        DB::transaction(function () use ($project, $assignments) {
            // Remove all existing assignments
            ProjectAssignment::where('project_id', $project->id)->delete();

            // Add new assignments
            foreach ($assignments as $role => $userId) {
                if ($userId) {
                    $user = User::findOrFail($userId);
                    $this->assignUserToProject($project, $user, $role);
                }
            }
        });
    }

    public function getAvailableStaff()
    {
        return User::where('role', '!=', 'client')
                   ->orderBy('name')
                   ->get()
                   ->groupBy('role');
    }

    public function getProjectsByUser(User $user)
    {
        if ($user->isSystemAdmin()) {
            return Project::with(['client', 'assignments.user'])->get();
        }

        if ($user->isClient()) {
            return Project::where('client_id', $user->client->id ?? 0)
                          ->with(['client', 'assignments.user'])
                          ->get();
        }

        return $user->assignedProjects()
                    ->with(['client', 'assignments.user'])
                    ->get();
    }
}
