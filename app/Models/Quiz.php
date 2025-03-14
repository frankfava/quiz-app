<?php

namespace App\Models;

use App\Enums\QuizStatus;
use App\Enums\QuizType;
use App\Relationships\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory,
        HasTenant;

    protected $fillable = [
        'label',
        'tenant_id',
        'status',
        'type',
        'meta',
    ];

    protected $casts = [
        'label' => 'string',
        'tenant_id' => 'integer',
        'status' => QuizStatus::class,
        'type' => QuizType::class,
        'created_by_id' => 'integer',
        'meta' => 'json',
    ];

    public static function booted()
    {
        static::creating(function (self $quiz) {
            if (empty($quiz->created_by_id)) {
                $quiz->created_by_id = auth()->check() ? auth()->id() : null;
            }
            if (empty($quiz->type)) {
                $quiz->type = QuizType::FOLLOW_ALONG->value;
            }
        });
    }

    /** Scope to get quizzes by status */
    public function scopeByStatus($query, QuizStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /** Scope to get quizzes by Owner */
    public function scopeByOwner($query, null|int|User $user = null, bool $include = true)
    {
        $user ??= (auth()->check() ? auth()->user() : null);
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('created_by_id', ($include ? '=' : '!='), $userId);
    }

    /** Scope quizzes that are Open to Join */
    public function scopeOpenToJoin($query)
    {
        return $query
            ->ByStatus(QuizStatus::PENDING);
    }

    /** User that created this quiz */
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
            ->using(QuizQuestion::class)
            ->withPivot('order')
            ->orderBy('quiz_questions.order');
    }

    public function contestants()
    {
        return $this->hasMany(QuizContestant::class);
    }

    public function submissions()
    {
        return $this->hasMany(QuizSubmission::class);
    }
}
