<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /* ─── Relations ─── */

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }

    /* ─── Scopes ─── */

    /**
     * Conversation between two employees (either direction).
     */
    public function scopeConversation($query, int $empA, int $empB)
    {
        return $query->where(function ($q) use ($empA, $empB) {
            $q->where('sender_id', $empA)->where('receiver_id', $empB);
        })->orWhere(function ($q) use ($empA, $empB) {
            $q->where('sender_id', $empB)->where('receiver_id', $empA);
        });
    }
}
