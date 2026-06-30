<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{

    use HasFactory;

    protected $fillable = [
    'reference_id',
    'user_id',
    'course_id',
    'payment_method',
    'price_paid',
    'status',
    'snap_token'
];

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function user() {
        return $this->belongsTo(User::class)
;    }
}
