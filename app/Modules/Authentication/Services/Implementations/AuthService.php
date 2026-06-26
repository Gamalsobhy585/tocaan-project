<?php

namespace App\Modules\Authentication\Services\Implementations;

use App\Modules\Authentication\Repositories\Interface\IUser;
use App\Modules\Authentication\Resources\UserResource;
use App\Modules\Authentication\Services\Interface\IAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;



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
          
            $user = [
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
            ];
            $storedUser = $this->userRepo->store($user);
           
            DB::commit();
            return $storedUser;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login($request)
    {
        $user = $this->userRepo->getByEmail($request->email);
        if (!$user) {
            throw new \Exception(__('messages.login.invalid_credentials'), 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw new \Exception(__('messages.login.invalid_credentials'), 401);
        }

        $token = $user->createToken('default_token');
        $user->token = $token->plainTextToken;

        return [
            'token' => $user->token,
            'user_data' => new UserResource($user),
        ];
    }

    public function logout($request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            return true;
        }
        return false;
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
            
            $cacheKey = 'user:info:' . $user->id;
            
            return Cache::remember($cacheKey, now()->addHours(1), function() use ($user) {
                return new UserResource($user);
            });
            
        } catch (\Exception $e) {
            Log::error('Failed to get user info: ' . $e->getMessage());
            throw new \Exception(__('messages.user.not_found'), 404);
        }
    }

}



