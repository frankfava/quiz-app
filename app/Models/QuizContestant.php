<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizContestant extends Model
{
    protected $fillable = [
        'quiz_id',
        'name',
        'joined_at',
        'meta',
    ];

    protected $casts = [
        'name' => 'string',
        'quiz_id' => 'integer',
        'joined_at' => 'datetime',
        'meta' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $contestant) {
            if (empty($contestant->joined_at)) {
                $contestant->joined_at = now();
            }
        });
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
