<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponentLog extends Model
{
    protected $fillable = [
        'salary_id', 'component_type', 'component_name', 'component_id', 'amount', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function salary(): BelongsTo
    {
        return $this->belongsTo(Salary::class);
    }
}
