<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
class UserService
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function add($username, $password, $realName, $college, $roleId, $role, $class, $phone)
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
            'class' => $class,
            'role_id' => $roleId,
            'role' => $role,
            'phone' => $phone,
        ]);
    }

    public function authenticate($roleId, $password)
    {
        $user = $this->user->where('role_id', $roleId)->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        throw new \Exception('ID或密码错误');
    }

    protected function userExists($roleId)
    {
        return $this->user->where('role_id', $roleId)->exists();
    }

    public function getUsersPaginated($page, $perPage)
    {
        return $this->user->orderBy('user_id', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    public function delete($roleId)
    {
        $user = $this->user->where('role_id', $roleId)->first();

        if (!$user) {
            throw new \Exception('用户不存在');
        }

        return $user->delete();
    }

    public function getUserById($userId)
    {
        return User::find($userId);
    }
}