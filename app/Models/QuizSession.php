<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'name',
        'age',
        'answers',
        'mbti_result',
        'result_details',
        'contact_method',
        'contact_address',
        'contact_sent'
    ];

    protected $casts = [
        'answers' => 'array',
        'result_details' => 'array',
        'contact_sent' => 'boolean'
    ];
}