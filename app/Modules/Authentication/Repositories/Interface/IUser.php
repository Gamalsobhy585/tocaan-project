<?php

namespace App\Modules\Authentication\Repositories\Interface;

interface IUser
{
    public function getByEmail($email);
    public function store($model);
    public function update($model);
    public function getUserInfo();


}
