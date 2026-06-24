<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_number', 'customer_id', 'customer_name', 'company_name', 'warehouse',
        'assigned_employee_id', 'items_count', 'total_quantity', 'total_amount', 'status',
        'created_by_id', 'prepared_by_id', 'reviewed_by_id', 'approved_by_id',
        'prepared_at', 'reviewed_at', 'approved_at', 'estimated_delivery_date',
        'actual_delivery_date', 'notes', 'rejection_reason'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'datetime',
        'prepared_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'approvable_id')->where('approvable_type', self::class);
    }

    public function delivery(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'prepared_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewed_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }
}
