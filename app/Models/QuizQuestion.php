<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class QuizQuestion extends Pivot
{
    public $table = 'quiz_questions';

    public $hidden = ['created_at', 'updated_at'];

    /* ======= Relationships ======= */

    public function quix()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
