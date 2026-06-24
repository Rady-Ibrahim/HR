<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_code', 'route_name', 'start_point', 'end_point', 'distance_km',
        'estimated_time_minutes', 'waypoints', 'status'
    ];

    protected $casts = [
        'waypoints' => 'json',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
