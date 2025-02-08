<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservation';

    protected $primaryKey = 'reservation_id';

    protected $fillable = [
        'user_id',
        'reservation_time',
        'checkin_code',
        'checkin_time',
        'expiration',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'checkin_time' => 'datetime',
        'reservation_time' => 'datetime',
        'expiration' => 'datetime',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}