<?php

namespace App\Services\Auth;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\EquipmentBooking;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function companySignIn($request): array
    {
        $user = User::query()->where('email', $request['email'])->first();

        if (is_null($user)) {
            return [
                'user' => [],
                'message' => 'Company account not found.',
                'status' => 404
            ];
        }

        if (!Hash::check($request->password, $user->password)) {
            return [
                'user' => [],
                'message' => 'Invalid credentials.',
                'status' => 401
            ];
        }

        if ($user->status !== 'active') {
            throw new Exception('This account is inactive.', 403);
        }

        if (!$user->hasRole('company_admin')) {
            throw new Exception('This account is not allowed to use company login.', 403);
        }

        $user = $this->appendRoleAndPermissions($user);
        $user['token'] = $user->createToken('Auth token')->plainTextToken;

        return [
            'user' => $user,
            'message' => 'Company login successful.',
            'status' => 200
        ];
    }

    public function internalSignIn($request): array
    {
        $user = User::query()->where('internal_id', $request['internal_id'])->first();

        if (is_null($user)) {
            return [
                'user' => [],
                'message' => 'Internal account not found.',
                'status' => 401
            ];
        }

        if (!Hash::check($request->password, $user->password)) {
            return [
                'user' => [],
                'message' => 'Invalid credentials.',
                'status' => 401
            ];
        }

        if ($user->status !== 'active') {
            throw new Exception('This account is inactive.', 403);
        }

        if ($user->hasRole('company_admin')) {
            throw new Exception('Company admin must use company login.', 403);
        }

        $user = $this->appendRoleAndPermissions($user);
        $user['token'] = $user->createToken('Auth token')->plainTextToken;

        return [
            'user' => $user,
            'message' => 'Internal login successful.',
            'status' => 200
        ];
    }
    public function signOut(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (is_null($user)) {
            return [
                'message' => 'Invalid token.',
                'status' => 401,
                'user' => null,
            ];
        }

        $loggedOutUser = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'internal_id' => $user->internal_id,
            'role' => $user->getRoleNames()->values()->toArray(),
        ];

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return [
            'message' => 'Sign out successful.',
            'status' => 200,
            'user' => $loggedOutUser,
        ];
    }

    public function appendRoleAndPermissions($user)
    {
        $user->load('roles', 'permissions');

        $role = null;

        if ($user->roles->isNotEmpty()) {
            $role = $user->roles->first()->name;
        }

        $permissions = [];

        foreach ($user->permissions as $permission) {
            $permissions[] = $permission->name;
        }

        unset($user['roles']);
        unset($user['permissions']);

        $user['role'] = $role;
        $user['permissions'] = $permissions;

        return $user;
    }

    public function createInternalUser($request): array
    {
        $request->validated();

        $roleName = $request->role;

        $internalId = $this->generateInternalId($request->name, $roleName);

        $user = User::query()->create([
            'name' => $request->name,
            'email' => null,
            'internal_id' => $internalId,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
        ]);

        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'api')
            ->first();

        if (! $role) {
            throw new \Exception('Role not found.', 404);
        }

        $user->assignRole($role);

        $user = $user->fresh();
        $user = $this->appendRoleAndPermissions($user);
        $user->makeHidden(['email', 'email_verified_at']);

        return [
            'message' => 'Internal user created successfully.',
            'user' => $user,
            'status' => 201,
        ];
    }
    private function generateInternalId(string $name, string $roleName): string
    {
        $prefix = match ($roleName) {
            'project_manager' => 'pm',
            'assistant' => 'asst',
            'project_owner' => 'owner',
            default => 'user',
        };

        $baseName = Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', '')
            ->trim()
            ->replace(' ', '.')
            ->toString();

        if ($baseName === '') {
            $baseName = 'user';
        }

        $baseInternalId = $prefix . '.' . $baseName . '@mutqin';
        $internalId = $baseInternalId;
        $counter = 2;

        while (User::query()->where('internal_id', $internalId)->exists()) {
            $internalId = $prefix . '.' . $baseName . $counter . '@alfanar';
            $counter++;
        }

        return $internalId;
    }



    public function toggleUserStatus($userId): array
    {
        $user = User::query()->find($userId);

        if (! $user) {
            throw new \Exception('User not found.', 404);
        }

        if ($user->hasRole('company_admin')) {
            throw new \Exception('You cannot change the status of company admin.', 403);
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $message = $user->status === 'active'
            ? 'User activated successfully.'
            : 'User deactivated successfully.';

        $user = $user->fresh();
        $user = $this->appendRoleAndPermissions($user);

        return [
            'message' => $message,
            'user' => $user,
            'status' => 200,
        ];
    }



    public function deleteInternalUser($userId): array
    {
        $user = User::query()->find($userId);

        if (! $user) {
            throw new \Exception('User not found.', 404);
        }

        if ($user->hasRole('company_admin')) {
            throw new \Exception('You cannot delete company admin.', 403);
        }

        $hasProjects = Project::query()
            ->where('project_manager_id', $user->id)
            ->orWhere('assistant_engineer_id', $user->id)
            ->orWhere('owner_id', $user->id)
            ->exists();

        if ($hasProjects) {
            throw new \Exception(
                'Cannot delete this user because they are assigned to one or more projects.',
                409
            );
        }

        $userName = $user->name;
        $userInternalId = $user->internal_id;
        $userEmail = $user->email;

        $user->delete();

        return [
            'message' => 'User deleted successfully.',
            'user' => [
                'name' => $userName,
                'internal_id' => $userInternalId,
                'email' => $userEmail,
            ],
            'status' => 200,
        ];
    }
    public function getUsersByRole($request): array
    {
        $request->validated();

        if ($request->role === 'all') {
            $users = User::query()
                ->with('roles')
                ->get();

            $users->each(function ($user) {
                $this->appendRoleAndPermissions($user);
                $user->makeHidden(['email', 'email_verified_at', 'password', 'remember_token']);
            });

            return [
                'message' => 'Users fetched successfully.',
                'users' => $users,
                'status' => 200,
            ];
        }

        $users = User::role($request->role, 'api')
            ->with('roles')
            ->get();

        $users->each(function ($user) {
            $this->appendRoleAndPermissions($user);
            $user->makeHidden(['email', 'email_verified_at', 'password', 'remember_token']);
        });

        return [
            'message' => 'Users fetched successfully.',
            'users' => $users,
            'status' => 200,
        ];
    }
    public function resetUserPassword($request, $userId): array
    {
        $request->validated();

        $admin = Auth::user();

        if (! Hash::check($request->admin_password, $admin->password)) {
            throw new \Exception('Admin password is incorrect.', 401);
        }

        $user = User::query()->find($userId);

        if (! $user) {
            throw new \Exception('User not found.', 404);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return [
            'message' => 'User password updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'internal_id' => $user->internal_id,
            ],
            'status' => 200,
        ];
    }
    public function search($request): array
    {
        $request->validated();

        $users = User::query()
            ->where('name', 'like', '%' . $request->keyword . '%')
            ->orWhere('internal_id', 'like', '%' . $request->keyword . '%')
            ->get()
            ->map(function ($user) {

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'internal_id' => $user->internal_id,
                    'status' => $user->status,
                ];
            });

        return [
            'message' => 'Users search completed successfully.',
            'users' => $users,
            'status' => 200,
        ];
    }
    public function statistics(
        $request,
        $userId
    ): array {
        $user = User::query()
            ->with('roles')
            ->find($userId);

        if (! $user) {

            throw new \Exception(
                'User not found.',
                404
            );
        }

        $projects = Project::query()

            ->where('project_manager_id', $user->id)

            ->orWhere('assistant_engineer_id', $user->id)

            ->orWhere('owner_id', $user->id)

            ->get();

        $activities = ActivityLog::query()

            ->where('user_id', $user->id)

            ->latest()

            ->take(10)

            ->get();

        if ($request->type === 'projects') {

            return [

                'message' =>
                'Projects fetched successfully.',

                'data' => [

                    'projects' => $projects
                        ->map(function ($project) {

                            return [

                                'id' => $project->id,

                                'name' => $project->name,

                                'status' => $project->status,
                            ];
                        }),
                ],

                'status' => 200,
            ];
        }

        if ($request->type === 'activities') {

            return [

                'message' =>
                'Activities fetched successfully.',

                'data' => [

                    'activities' => $activities
                        ->map(function ($activity) {

                            return [

                                'action' =>
                                $activity->action,

                                'method' =>
                                $activity->method,

                                'endpoint' =>
                                $activity->endpoint,

                                'description' =>
                                $activity->description,

                                'created_at' =>
                                $activity->created_at,
                            ];
                        }),
                ],

                'status' => 200,
            ];
        }

        if ($request->type === 'endpoints') {

            $endpoints = ActivityLog::query()

                ->where('user_id', $user->id)

                ->selectRaw(
                    'endpoint, COUNT(*) as total'
                )

                ->groupBy('endpoint')

                ->orderByDesc('total')

                ->get();

            return [

                'message' =>
                'Endpoints fetched successfully.',

                'data' => [

                    'endpoints' => $endpoints,
                ],

                'status' => 200,
            ];
        }

        if ($request->type === 'comments') {

            $comments = Comment::query()

                ->where('user_id', $user->id)

                ->latest()

                ->get();

            return [

                'message' =>
                'Comments fetched successfully.',

                'data' => [

                    'comments' => $comments,
                ],

                'status' => 200,
            ];
        }

        if ($request->type === 'bookings') {

            $bookings = EquipmentBooking::query()

                ->with([
                    'equipment',
                    'workItem',
                ])

                ->where('booked_by', $user->id)

                ->latest()

                ->get();

            return [

                'message' =>
                'Bookings fetched successfully.',

                'data' => [

                    'bookings' => $bookings,
                ],

                'status' => 200,
            ];
        }

        $commentsCount = Comment::query()

            ->where('user_id', $user->id)

            ->count();

        $bookingsCount = EquipmentBooking::query()

            ->where('booked_by', $user->id)

            ->count();

        $apiCallsCount = ActivityLog::query()

            ->where('user_id', $user->id)

            ->count();

        return [

            'message' =>
            'User statistics fetched successfully.',

            'data' => [

                'user' => [

                    'id' => $user->id,

                    'name' => $user->name,

                    'internal_id' => $user->internal_id,

                    'status' => $user->status,

                    'roles' => $user->roles
                        ->pluck('name'),
                ],

                'statistics' => [

                    'projects_count' =>
                    $projects->count(),

                    'comments_count' =>
                    $commentsCount,

                    'equipment_bookings_count' =>
                    $bookingsCount,

                    'api_calls_count' =>
                    $apiCallsCount,
                ],
            ],

            'status' => 200,
        ];
    }


    public function account(): array
    {
        $owner = auth()->user();

        return [

            'message' => 'Account fetched successfully.',

            'data' => [

                'name' => $owner->name,

                'email' => $owner->email,

                'projects_count' => $owner->ownedProjects()->count(),

                'active_projects_count' => $owner
                    ->ownedProjects()
                    ->where('status', 'ongoing')
                    ->count(),

                'avatar' => $owner->avatar ?? null,
            ],

            'status' => 200,
        ];
    }


    public function profile(): array
    {
        $user = auth()->user();

        return [

            'message' => 'Account fetched successfully.',

            'data' => [

                'name' => $user->name,

                'email' => $user->email,

                'role' => 'مساعد تنفيذي',

                'assigned_projects_count' =>
                    $user->assignedProjects()->count(),

                'account_status' =>
                    $user->status === 'active'
                        ? 'verified'
                        : 'inactive',

                'avatar' => null,

            ],

            'status' => 200,
        ];
    }
    public function getUserNotifications(
        User $user
    ) {
        return Notification::query()

            ->where('user_id', $user->id)

            ->with([
                'project',
                'workItem'
            ])

            ->latest()

            ->paginate(20);
    }
}
