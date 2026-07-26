<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

trait RestrictToSubordinates
{
    private function getCurrentEmployee(): ?Employee
    {
        $user = Auth::user();
        if (!$user) return null;

        return $user->employee
            ?? Employee::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
    }

    private function isManager(): bool
    {
        $emp = $this->getCurrentEmployee();
        return $emp && $emp->is_manager;
    }

    private function getMySubordinateIds(): array
    {
        $emp = $this->getCurrentEmployee();
        if (!$emp) return [];
        return $emp->subordinates()->pluck('id')->toArray();
    }

    private function validateSubordinate(int $employeeId): void
    {
        $emp = $this->getCurrentEmployee();
        if (!$emp) {
            abort(401, 'غير مصرح');
        }

        $isSuperAdmin = Auth::user()?->hasRole('super_admin')
            ?? optional(Auth::user()?->roles()->first())?->name === 'super_admin';

        if ($isSuperAdmin) return;

        if (!$emp->is_manager) {
            abort(403, 'غير مصرح لك. أنت لست مديراً.');
        }

        $exists = $emp->subordinates()->where('id', $employeeId)->exists();
        if (!$exists) {
            abort(403, 'هذا الموظف ليس ضمن فريقك');
        }
    }

    private function scopeSubordinates($query, string $employeeColumn = 'employee_id')
    {
        $emp = $this->getCurrentEmployee();
        if (!$emp) return $query;

        $isSuperAdmin = Auth::user()?->hasRole('super_admin')
            ?? optional(Auth::user()?->roles()->first())?->name === 'super_admin';

        if ($isSuperAdmin) return $query;

        if (!$emp->is_manager) {
            $query->where($employeeColumn, $emp->id);
            return $query;
        }

        $subIds = $this->getMySubordinateIds();
        $subIds[] = $emp->id;
        $query->whereIn($employeeColumn, $subIds);

        return $query;
    }
}
