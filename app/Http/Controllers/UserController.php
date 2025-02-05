<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;

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
                $validatedData['phone']
            );

            // $this->clearUsersCache();

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
    
        // 尝试从缓存中获取数据
        $users = Cache::remember($cacheKey, 600, function () use ($page, $perPage) {
            return $this->userService->getUsersPaginated($page, $perPage);
        });
    
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

            // $this->clearUsersCache();

            return response()->json(['message' => '用户已删除'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}