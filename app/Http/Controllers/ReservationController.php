<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ReservationController extends Controller
{
    protected $reservationService;
    protected $userService;

    public function __construct(ReservationService $reservationService, UserService $userService)
    {
        $this->reservationService = $reservationService;
        $this->userService = $userService;
    }

    // 获取所有预约
    public function getReservations(Request $request)
    {
        $validatedData = $request->validate([
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ]);

        $page = $validatedData['page'];
        $perPage = $validatedData['per_page'];

        $cacheKey = "reservations_page_{$page}_per_page_{$perPage}";

        // 尝试从 Redis 中获取数据
        $reservations = Redis::get($cacheKey);
        if ($reservations) {
            $reservations = json_decode($reservations, true);
        } else {
            // 如果 Redis 中没有数据，则从数据库中获取并存入 Redis
            $reservations = Reservation::paginate($perPage, ['*'], 'page', $page);
            $reservations = $reservations->toArray();

            foreach ($reservations['data'] as &$reservation) {
                $user = $this->userService->getUserById($reservation['user_id']);
                $reservation['real_name'] = $user ? $user->real_name : null;
            }

            Redis::setex($cacheKey, 600, json_encode($reservations));
        }

        return response()->json($reservations, 200);
    }

    // 创建新的预约
    public function createReservation(Request $request)
    {
        $validatedData = $request->validate([
            'real_name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'role_id' => 'required|string|max:255',
        ]);

        // 验证用户信息
        try {
            $user = $this->reservationService->validateUser(
                $validatedData['real_name'],
                $validatedData['role'],
                $validatedData['role_id']
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $user_id = $this->reservationService->getUserIdByRoleId($validatedData['role_id']);
        
        if ($this->reservationService->hasReservationToday($user_id)) {
            return response()->json(['error' => '用户今天已经有预约记录'], 400);
        }

        $checkin_code = $this->reservationService->generateSignInCode();

        $reservationData = [
            'real_name' => $validatedData['real_name'],
            'role' => $validatedData['role'],
            'role_id' => $validatedData['role_id'],
            'user_id' => $user_id,
            'checkin_code' => $checkin_code,
            'reservation_time' => Carbon::now('Asia/Shanghai')->format('Y-m-d H:i'),
            'expiration' => Carbon::now('Asia/Shanghai')->endOfDay()->format('Y-m-d H:i'),
            'status' => '已预约',
        ];

        $reservation = Reservation::create($reservationData);

        $this->clearReservationsCache();

        return response()->json($reservation, 201);
    }

    // 清除 Redis 缓存
    protected function clearReservationsCache()
    {
        $keys = Redis::keys('reservations_page_*');
        foreach ($keys as $key) {
            Redis::del($key);
        }
    }

    //签到
    public function checkIn(Request $request)
    {
        $validatedData = $request->validate([
            'checkin_code' => 'required|string',
        ]);
       
        $reservation = Reservation::where('checkin_code', $validatedData['checkin_code'])->first();

        if (!$reservation) {
            return response()->json(['error' => '无效的签到码'], 400);
        }

        if ($reservation->status == '已签到') {
            return response()->json(['error' => '该预约已签到'], 400);
        }

        $reservation->status = '已签到';
        $reservation->checkin_time = Carbon::now('Asia/Shanghai')->format('Y-m-d H:i');
        $reservation->save();

        $this->clearReservationsCache();

        return response()->json(['message' => '签到成功', 'reservation' => $reservation], 200);
    }

    public function getCheckinCodeByRoleId(Request $request)
    {
        $validatedData = $request->validate([
            'role_id' => 'required|string',
        ]);

        try {
            $user_id = $this->reservationService->getUserIdByRoleId($validatedData['role_id']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $reservation = Reservation::where('user_id', $user_id)
                                ->where('status', '已预约')
                                ->first();

        if (!$reservation) {
            return response()->json(['error' => '未找到对应的预约记录'], 404);
        }

        return response()->json(['checkin_code' => $reservation->checkin_code], 200);
    }
}