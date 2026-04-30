<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeUserStatusRequest;
use App\Http\Requests\CompanyLoginRequest;
use App\Http\Requests\CreateInternalUserRequest;
use App\Http\Requests\InternalLoginRequest;
use App\Http\Responses\Response;
use App\Services\Authentication\AuthService;
use App\Services\AuthService as ServicesAuthService;
use Throwable;

class AuthController extends Controller
{
    private ServicesAuthService $authService;

    public function __construct(ServicesAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function companySignIn(CompanyLoginRequest $request)
    {
        try {
            $data = $this->authService->companySignIn($request);

            return Response::success(
                $data['message'],
                $data['user'],
                $data['status']
            );
        } catch (Throwable $throwable) {
            return Response::error(
                $throwable->getMessage(),
                $throwable->getCode() ?: 500
            );
        }
    }

    public function internalSignIn(InternalLoginRequest $request)
    {
        try {
            $data = $this->authService->internalSignIn($request);

            return Response::success(
                $data['message'],
                $data['user'],
                $data['status']
            );
        } catch (Throwable $throwable) {
            return Response::error(
                $throwable->getMessage(),
                $throwable->getCode() ?: 500
            );
        }
    }

    public function signOut()
    {
        try {
            $data = $this->authService->signOut();
            return Response::success($data['message'], $data['user'], $data['status']);
        } catch (Throwable $throwable) {
            return Response::error($throwable->getMessage(), $throwable->getCode() ?: 500);
        }
    }


    public function createInternalUser(CreateInternalUserRequest $request)
    {
        try {
            $data = $this->authService->createInternalUser($request);
            return Response::success($data['message'], $data['user'], $data['status']);
        } catch (Throwable $throwable) {
            return Response::error($throwable->getMessage(), $throwable->getCode() ?: 500);
        }
    }

    public function toggleUserStatus($userId)
    {
        try {
            $data = $this->authService->toggleUserStatus($userId);
            return Response::success($data['message'], $data['user'], $data['status']);
        } catch (\Throwable $throwable) {
            return Response::error($throwable->getMessage(), $throwable->getCode() ?: 500);
        }
    }

    public function deleteInternalUser($userId)
    {
        try {
            $data = $this->authService->deleteInternalUser($userId);
            return Response::success($data['message'], $data['user'], $data['status']);
        } catch (\Throwable $throwable) {
            return Response::error($throwable->getMessage(), $throwable->getCode() ?: 500);
        }
    }
}
