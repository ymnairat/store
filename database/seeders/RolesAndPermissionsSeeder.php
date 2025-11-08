<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Products
            ['name' => 'products.view', 'display_name' => 'عرض المنتجات', 'group' => 'products'],
            ['name' => 'products.create', 'display_name' => 'إضافة منتجات', 'group' => 'products'],
            ['name' => 'products.edit', 'display_name' => 'تعديل منتجات', 'group' => 'products'],
            ['name' => 'products.delete', 'display_name' => 'حذف منتجات', 'group' => 'products'],
            
            // Warehouses
            ['name' => 'warehouses.view', 'display_name' => 'عرض المخازن', 'group' => 'warehouses'],
            ['name' => 'warehouses.create', 'display_name' => 'إضافة مخازن', 'group' => 'warehouses'],
            ['name' => 'warehouses.edit', 'display_name' => 'تعديل مخازن', 'group' => 'warehouses'],
            ['name' => 'warehouses.delete', 'display_name' => 'حذف مخازن', 'group' => 'warehouses'],
            
            // Transactions
            ['name' => 'transactions.view', 'display_name' => 'عرض الحركات', 'group' => 'transactions'],
            ['name' => 'transactions.create', 'display_name' => 'إنشاء حركات', 'group' => 'transactions'],
            
            // Inventory
            ['name' => 'inventory.view', 'display_name' => 'عرض المخزون', 'group' => 'inventory'],
            
            
            // Users Management
            ['name' => 'users.view', 'display_name' => 'عرض المستخدمين', 'group' => 'users'],
            ['name' => 'users.create', 'display_name' => 'إضافة مستخدمين', 'group' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'تعديل مستخدمين', 'group' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'حذف مستخدمين', 'group' => 'users'],
            
            // Roles & Permissions
            ['name' => 'roles.view', 'display_name' => 'عرض الأدوار', 'group' => 'roles'],
            ['name' => 'roles.create', 'display_name' => 'إضافة أدوار', 'group' => 'roles'],
            ['name' => 'roles.edit', 'display_name' => 'تعديل أدوار', 'group' => 'roles'],
            ['name' => 'roles.delete', 'display_name' => 'حذف أدوار', 'group' => 'roles'],
        ];

        foreach ($permissions as $perm) {
            Permission::create($perm);
        }

        // Create Roles
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'مدير عام',
            'description' => 'صلاحيات كاملة على النظام'
        ]);

        $projectManagerRole = Role::create([
            'name' => 'project_manager',
            'display_name' => 'مدير مشروع',
            'description' => 'إدارة المشاريع والمواد المتعلقة بها'
        ]);

        $maintenanceManagerRole = Role::create([
            'name' => 'maintenance_manager',
            'display_name' => 'مدير صيانة',
            'description' => 'إدارة أعمال الصيانة والمواد'
        ]);

        $employeeRole = Role::create([
            'name' => 'employee',
            'display_name' => 'موظف',
            'description' => 'صلاحيات محدودة للعرض والحركات'
        ]);

        // Assign all permissions to admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Assign permissions to project manager
        $projectManagerRole->givePermission('products.view');
        $projectManagerRole->givePermission('products.create');
        $projectManagerRole->givePermission('products.edit');
        $projectManagerRole->givePermission('warehouses.view');
        $projectManagerRole->givePermission('transactions.view');
        $projectManagerRole->givePermission('transactions.create');
        $projectManagerRole->givePermission('inventory.view');

        // Assign permissions to maintenance manager
        $maintenanceManagerRole->givePermission('products.view');
        $maintenanceManagerRole->givePermission('warehouses.view');
        $maintenanceManagerRole->givePermission('transactions.view');
        $maintenanceManagerRole->givePermission('transactions.create');
        $maintenanceManagerRole->givePermission('inventory.view');

        // Assign permissions to employee
        $employeeRole->givePermission('products.view');
        $employeeRole->givePermission('warehouses.view');
        $employeeRole->givePermission('transactions.view');
        $employeeRole->givePermission('inventory.view');
    }
}
