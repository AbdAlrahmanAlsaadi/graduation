<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
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

    // public function signOut(): array
    // {
    //     $user = Auth::user();
    //
    //     if (is_null($user)) {
    //         return [
    //             'message' => 'Invalid token.',
    //             'status' => 401
    //         ];
    //     }
    //
    //     $token = $user->currentAccessToken();
    //
    //     if ($token) {
    //         $token->delete();
    //     }
    //
    //     return [
    //         'message' => 'Sign out successful.',
    //         'status' => 200
    //     ];
    // }

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
}
