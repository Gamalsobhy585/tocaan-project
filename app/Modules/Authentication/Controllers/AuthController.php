<?php

namespace App\Modules\Authentication\Controllers;

use App\Modules\Authentication\Requests\LoginRequest;
use App\Modules\Authentication\Requests\RegisterRequest;
use App\Modules\Authentication\Requests\UpdatePasswordRequest;
use App\Modules\Authentication\Services\Implementations\AuthService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class AuthController extends Controller
{
    use ResponseTrait;
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
             $this->authService->register($request);
            return $this->success(
                __("messages.register.success"),
                201
            );
        }catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $statusCode = $e->getCode() ?: 500;
            
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $errorMessage = $e->validator->errors()->first();
                $statusCode = 422;
            }
            
            return $this->returnError($errorMessage, $statusCode);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $data = $this->authService->login($request);
            return $this->returnData(
                __('messages.login.success'),
                200,
                $data
            );
        } catch (\Exception $e) {
            $this->returnError($e->getMessage(), $e->getCode() ?: 401);
        }
    }
 
    public function logout(Request $request)
    {
        try {
            $result = $this->authService->logout($request);
            if ($result) {
                return $this->success(__('messages.logout.success'), 200);
            }
            $this->returnError(__('messages.logout.failed'), 400);
        } catch (\Exception $e) {
            $this->returnError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function renewPassword(UpdatePasswordRequest $request)
    {
        try {
            $email = $request->user()->email;
            $this->authService->updateoldPassword(
                $email,
                $request->old_password,
                $request->new_password
            );
            return $this->success(__('messages.renew.success'), 200);
        } catch (\Exception $e) {
            $this->returnError($e->getMessage(), $e->getCode() ?: 401);
        }
    }

    public function getUserInfo(Request $request)
    {
        try {
            $user = $this->authService->getUserInfo($request);
            return $this->returnData(
                __('messages.user.success'),
                200,
                $user
            );
        } catch (\Exception $e) {
            $this->returnError($e->getMessage(), $e->getCode() ?: 404);
        }
    }
}
