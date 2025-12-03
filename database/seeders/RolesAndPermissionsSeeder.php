<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions List
        $permissions = [
            // User Management (Admin)
            'users.view', 'users.create', 'users.update', 'users.delete',
            
            // Customer Management
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            
            // Catalogue (Products, Categories, Brands)
            'catalogue.view', 'catalogue.create', 'catalogue.update', 'catalogue.delete',
            'inventory.manage', // Stock adjustments
            
            // Orders
            'orders.view', 'orders.update', 'orders.cancel', 'orders.delete',
            
            // Marketing
            'promotions.view', 'promotions.create', 'promotions.update', 'promotions.delete',
            'reviews.manage', // Approve/Reject reviews
            
            // Settings & System
            'settings.view', 'settings.update',
            'authenticity.manage', // Generate QR codes
            'reports.view', // Dashboard stats
        ];

        // 2. Create Permissions (for 'web' guard mainly)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 3. Create Roles & Assign Permissions

        // --- SUPER ADMIN ---
        // Has ALL permissions via Gate::before rule (usually) or assign all
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        // We don't need to assign permissions if we use Gate::before, but let's assign all for clarity
        $superAdminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        // --- ADMIN ---
        // Can do almost everything except maybe critical system settings or deleting other admins
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'users.view', 'users.create', 'users.update',
            'customers.view', 'customers.create', 'customers.update',
            'catalogue.view', 'catalogue.create', 'catalogue.update', 'catalogue.delete',
            'inventory.manage',
            'orders.view', 'orders.update', 'orders.cancel',
            'promotions.view', 'promotions.create', 'promotions.update',
            'reviews.manage',
            'authenticity.manage',
            'reports.view',
        ]);

        // --- MANAGER ---
        // Focused on Products and Orders
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->syncPermissions([
            'catalogue.view', 'catalogue.create', 'catalogue.update',
            'inventory.manage',
            'orders.view', 'orders.update',
            'reviews.manage',
            'promotions.view',
            'reports.view',
        ]);

        // --- CUSTOMER ---
        // Role for the 'customer' guard
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'customer']);
        // Customers usually don't need specific permissions stored in DB if logic is simple,
        // but we can add them if needed (e.g. 'write_reviews', 'place_orders')
        // For now, the role existence is enough for the seeder.
    }
}
