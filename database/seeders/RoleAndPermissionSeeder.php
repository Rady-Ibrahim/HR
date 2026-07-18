<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => 'مدير النظام',
            'hr_manager' => 'مدير الموارد البشرية',
            'finance_manager' => 'مدير المالية',
            'operations_manager' => 'مدير التشغيل',
            'warehouse_manager' => 'مدير المخزن',
            'delivery_manager' => 'مدير التوصيل',
            'approver_level_1' => 'موافق المستوى الأول',
            'approver_level_2' => 'موافق المستوى الثاني',
            'approver_level_3' => 'موافق المستوى الثالث',
            'manager' => 'مدير',
            'driver' => 'سائق / مندوب',
            'employee' => 'الموظف',
            'report_viewer' => 'عارض التقارير',
        ];

        foreach ($roles as $key => $value) {
            Role::firstOrCreate(
                ['name' => $key],
                ['description' => $value]
            );
        }

        $permissions = [
            // Employee Management
            ['name' => 'view_employees', 'group' => 'employees', 'description' => 'عرض الموظفين'],
            ['name' => 'create_employees', 'group' => 'employees', 'description' => 'إنشاء موظفين'],
            ['name' => 'edit_employees', 'group' => 'employees', 'description' => 'تعديل الموظفين'],
            ['name' => 'delete_employees', 'group' => 'employees', 'description' => 'حذف الموظفين'],

            // Requests Management
            ['name' => 'view_requests', 'group' => 'requests', 'description' => 'عرض الطلبات'],
            ['name' => 'create_requests', 'group' => 'requests', 'description' => 'إنشاء طلبات'],
            ['name' => 'edit_requests', 'group' => 'requests', 'description' => 'تعديل الطلبات'],
            ['name' => 'delete_requests', 'group' => 'requests', 'description' => 'حذف الطلبات'],
            ['name' => 'approve_requests', 'group' => 'requests', 'description' => 'موافقة الطلبات'],

            // Deliveries
            ['name' => 'view_deliveries', 'group' => 'deliveries', 'description' => 'عرض التسليمات'],
            ['name' => 'create_deliveries', 'group' => 'deliveries', 'description' => 'إنشاء تسليمات'],
            ['name' => 'edit_deliveries', 'group' => 'deliveries', 'description' => 'تعديل التسليمات'],
            ['name' => 'approve_routes', 'group' => 'deliveries', 'description' => 'موافقة خطوط السير'],

            // Collections
            ['name' => 'view_collections', 'group' => 'collections', 'description' => 'عرض التحصيلات'],
            ['name' => 'create_collections', 'group' => 'collections', 'description' => 'إنشاء تحصيلات'],
            ['name' => 'approve_collections', 'group' => 'collections', 'description' => 'موافقة التحصيلات'],

            // Attendance
            ['name' => 'view_attendance', 'group' => 'attendance', 'description' => 'عرض الحضور'],
            ['name' => 'manage_attendance', 'group' => 'attendance', 'description' => 'إدارة الحضور'],

            // Salary
            ['name' => 'view_salaries', 'group' => 'salaries', 'description' => 'عرض الرواتب'],
            ['name' => 'create_salaries', 'group' => 'salaries', 'description' => 'إنشاء رواتب'],
            ['name' => 'approve_salaries', 'group' => 'salaries', 'description' => 'موافقة الرواتب'],
            ['name' => 'manage_incentives', 'group' => 'salaries', 'description' => 'إدارة الحوافز'],
            ['name' => 'manage_deductions', 'group' => 'salaries', 'description' => 'إدارة الخصومات'],

            // Reports
            ['name' => 'view_reports', 'group' => 'reports', 'description' => 'عرض التقارير'],
            ['name' => 'export_reports', 'group' => 'reports', 'description' => 'تصدير التقارير'],

            // Dashboard
            ['name' => 'view_dashboard', 'group' => 'dashboard', 'description' => 'عرض لوحة التحكم'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $permissions = Permission::all();
            $superAdminRole->permissions()->sync($permissions);
        }

        $collectionPermissions = Permission::whereIn('name', [
            'view_collections',
            'create_collections',
        ])->pluck('id');

        foreach (['hr_manager', 'driver'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && $collectionPermissions->isNotEmpty()) {
                $role->permissions()->syncWithoutDetaching($collectionPermissions);
            }
        }

        // Direct managers approve collections from mobile; HR can too (dashboard)
        $managerApprovePermissions = Permission::whereIn('name', [
            'view_collections',
            'approve_collections',
        ])->pluck('id');

        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole && $managerApprovePermissions->isNotEmpty()) {
            $managerRole->permissions()->syncWithoutDetaching($managerApprovePermissions);
        }

        $hrRole = Role::where('name', 'hr_manager')->first();
        $approveCollection = Permission::where('name', 'approve_collections')->first();
        if ($hrRole && $approveCollection) {
            $hrRole->permissions()->syncWithoutDetaching([$approveCollection->id]);
        }
    }
}
