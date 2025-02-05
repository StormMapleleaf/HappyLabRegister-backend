<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function add($username, $password, $realName, $college, $roleId, $role, $phone)
    {
        // 检查用户是否存在
        if ($this->userExists($roleId)) {
            throw new \Exception('用户已存在');
        }

        // 验证非空字段
        if (empty($realName) || empty($college) || empty($roleId) || empty($role) || empty($phone)) {
            throw new \InvalidArgumentException('真实姓名，学院，身份，身份id和手机号不能为空');
        }
        
        // 创建用户
        return $this->user->create([
            'username' => $username,
            'password' => bcrypt($password),
            'real_name' => $realName,
            'college' => $college,
            'role_id' => $roleId,
            'role' => $role,
            'phone' => $phone,
        ]);
    }

    protected function userExists($roleId)
    {
        return $this->user->where('role_id', $roleId)->exists();
    }
}