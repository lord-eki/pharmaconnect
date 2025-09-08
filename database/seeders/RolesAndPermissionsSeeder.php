<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = ['Admin', 'Supplier', 'Insurer', 'Operation', 'Physician'];
        
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        // Create permissions (examples - adjust based on your needs)
        $permissions = [
            'view_users',
            'create_users', 
            'edit_users',
            'delete_users',
            'manage_suppliers',
            'manage_insurance',
            'manage_operations',
            'manage_medical_records',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $adminRole = Role::findByName('Admin');
        $adminRole->givePermissionTo(Permission::all());

        $supplierRole = Role::findByName('Supplier');
        $supplierRole->givePermissionTo(['view_users', 'manage_suppliers']);

    }
}