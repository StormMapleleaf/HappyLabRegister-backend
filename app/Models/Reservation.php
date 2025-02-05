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
        'date',
        'checkin_code',
        'checkin_time',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'checkin_time' => 'datetime',
        'date' => 'date',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}