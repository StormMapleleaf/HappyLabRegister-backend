<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
}