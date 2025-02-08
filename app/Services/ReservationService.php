<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationService
{
    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    // 获取所有预约
    public function getAllReservations()
    {
        return $this->reservation->all();
    }

    // 根据 role_id 获取 user_id
    public function getUserIdByRoleId($roleId)
    {
        $user = User::where('role_id', $roleId)->first();

        if ($user) {
            return $user->user_id;
        }

        throw new \Exception('用户不存在');
    }

    // 检查用户当天是否有预约记录
    public function hasReservationToday($userId)
    {
        $today = Carbon::today();

        return $this->reservation->where('user_id', $userId)
            ->whereDate('reservation_time', $today)
            ->where('status', '已预约')
            ->exists();
    }

    // 验证用户信息
    public function validateUser($realName, $role, $roleId)
    {
        $user = User::where('real_name', $realName)
            ->where('role', $role)
            ->where('role_id', $roleId)
            ->first();
        
        if ($user) {
            return $user;
        }

        throw new \Exception('用户信息不匹配');
    }

    // 生成随机签到码
    public function generateSignInCode()
    {
        $datePart = Carbon::now('Asia/Shanghai')->format('md'); // 获取当前月和日，格式为MMDD
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomLetters = '';

        for ($i = 0; $i < 4; $i++) {
            $randomLetters .= $letters[rand(0, 25)];
        }

        return $datePart . $randomLetters;
    }
}