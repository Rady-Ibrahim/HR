<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_number', 'request_id', 'route_id', 'driver_id', 'vehicle_number',
        'status', 'start_latitude', 'start_longitude', 'end_latitude', 'end_longitude',
        'start_time', 'end_time', 'delivery_notes', 'delivery_photo', 'signature_proof',
        'delivery_items'
    ];

    protected $casts = [
        'delivery_items' => 'json',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(DeliveryCheckpoint::class);
    }

    public function tracking(): HasMany
    {
        return $this->hasMany(VehicleTracking::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }
}
