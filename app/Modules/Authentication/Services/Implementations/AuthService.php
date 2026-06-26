<?php

namespace App\Modules\Authentication\Services\Implementations;

use App\Modules\Authentication\Repositories\Interface\IUser;
use App\Modules\Authentication\Resources\UserResource;
use App\Modules\Authentication\Services\Interface\IAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService implements IAuthService
{
    private IUser $userRepo;

    public function __construct(IUser $user)
    {
        $this->userRepo = $user;
    }

    public function register($request)
    {
        try {
            if ($this->userRepo->getByEmail($request->email)) {
                throw new \Exception(__('messages.register.email_exists'), 422);
            }

            DB::beginTransaction();

            $storedUser = $this->userRepo->store([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();
            return $storedUser;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login($request)
    {
        $credentials = $request->only('email', 'password');

        try {
            // Attempt to create a JWT from credentials directly
            $token = JWTAuth::attempt($credentials);
        } catch (JWTException $e) {
            throw new \Exception(__('messages.login.token_error'), 500);
        }

        if (!$token) {
            throw new \Exception(__('messages.login.invalid_credentials'), 401);
        }

        $user = JWTAuth::user();

        return [
            'token'     => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // seconds
            'user_data' => new UserResource($user),
        ];
    }

    public function logout($request)
    {
        try {
            // Invalidates the token so it can never be used again
            JWTAuth::invalidate(JWTAuth::getToken());
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    public function updateoldPassword($email, $oldPassword, $newPassword)
    {
        $user = $this->userRepo->getByEmail($email);

        if (!$user) {
            throw new \Exception(__('messages.renew.user_not_found'), 404);
        }

        if (!Hash::check($oldPassword, $user->password)) {
            throw new \Exception(__('messages.renew.failed'), 401);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }

    public function getUserInfo($request)
    {
        try {
            $user = $this->userRepo->getUserInfo();

            return Cache::remember('user:info:' . $user->id, now()->addHour(), function () use ($user) {
                return new UserResource($user);
            });
        } catch (\Exception $e) {
            Log::error('Failed to get user info: ' . $e->getMessage());
            throw new \Exception(__('messages.user.not_found'), 404);
        }
    }
}