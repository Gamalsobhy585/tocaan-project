<?php

namespace App\Modules\Authentication\Services\Interface;

interface IAuthService
{
    public function register($request);
    public function login($request);
    public function logout($request);
    public function updateoldPassword($email, $oldPassword, $newPassword);
    public function getUserInfo($request);


}
