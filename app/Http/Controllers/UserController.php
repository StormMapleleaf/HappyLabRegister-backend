<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    private function clearUsersCache()
    {
        // 清除所有用户分页缓存
        $keys = Cache::get('users_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
            Redis::del($key); // 清除 Redis 缓存
        }
        Cache::forget('users_cache_keys');
    }

    // 添加用户
    public function addUser(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:5',
            'real_name' => 'required|string|max:255',
            'college' => 'required|string|max:255',
            'role_id' => 'required|string',
            'role' => 'required|string|max:255',
            'class' => 'nullable|string|max:255',
            'phone' => 'required|string|max:15',
        ]);
        
        if (empty($validatedData['password'])) {
            $validatedData['password'] = (string) $validatedData['role_id'];
        }

        try {
            $user = $this->userService->add(
                $validatedData['username'] ?? null,
                $validatedData['password'],
                $validatedData['real_name'],
                $validatedData['college'],
                $validatedData['role_id'],
                $validatedData['role'],
                $validatedData['class']??null,
                $validatedData['phone']
            );

            $this->clearUsersCache(); // 清除缓存

            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    //登录
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'role_id' => 'required|string',
            'password' => 'required|string|min:5',
        ]);

        try {
            $user = $this->userService->authenticate(
                $validatedData['role_id'],
                $validatedData['password']
            );

            if ($user && $user->role === '管理员') {
                return response()->json($user, 200);
            } else {
                return response()->json(['error' => '无权限登录'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getUsers(Request $request)
    {
        $validatedData = $request->validate([
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ]);
    
        $page = $validatedData['page'];
        $perPage = $validatedData['per_page'];
    
        $cacheKey = "users_page_{$page}_per_page_{$perPage}";
    
        // 尝试从 Redis 中获取数据
        $users = Redis::get($cacheKey);
        // if ($users) {
        //     $users = json_decode($users, true);
        // } else {
        //     // 如果 Redis 中没有数据，则从数据库中获取并存入 Redis
        //     $users = $this->userService->getUsersPaginated($page, $perPage);
        //     Redis::setex($cacheKey, 600, json_encode($users));
        // }

        if ($users) {
            $users = json_decode($users, true);
        } else {
            try {
                // 如果 Redis 中没有数据，则从数据库中获取
                $users = $this->userService->getUsersPaginated($page, $perPage);
                
                // 将数据存入 Redis
                // Redis::setex($cacheKey, 600, json_encode($users));
            } catch (\Exception $e) {
                // 捕获数据库读取失败的异常
                return response()->json(['error' => '无法从数据库获取用户数据: ' . $e->getMessage()], 500);
            }
        }
    
        // 记录缓存键
        $keys = Cache::get('users_cache_keys', []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put('users_cache_keys', $keys, 600);
        }
    
        return response()->json($users, 200);
    }

    public function viewCache(Request $request)
    {
        $cacheKey = $request->input('key');
        $cacheContent = Cache::get($cacheKey);

        return response()->json(['cache_key' => $cacheKey, 'cache_content' => $cacheContent], 200);
    }

    public function deleteUser(Request $request)
    {
        $validatedData = $request->validate([
            'role_id' => 'required|string',
        ]);

        try {
            $this->userService->delete($validatedData['role_id']);

            $this->clearUsersCache(); // 清除缓存

            return response()->json(['message' => '用户已删除'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}