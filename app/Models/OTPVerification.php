<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTPVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verification';

    protected $fillable = ['user_id', 'email', 'otp', 'valid_before'];
}
