<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_code', 'route_name', 'driver_id', 'sales_rep_id', 'vehicle_number',
        'start_point', 'end_point', 'distance_km',
        'estimated_time_minutes', 'waypoints', 'status'
    ];

    protected $casts = [
        'waypoints' => 'json',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('stop_order');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }
}
