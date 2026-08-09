<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        // if (! app()->isProduction()) {
             $this->createSampleUsers();
        // }
    }

    private function createPermissions(): void
    {
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Projects
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.manage_budget',
            'projects.manage_members',
            'projects.manage_location',

            // Work Items
            'work_items.view',
            'work_items.create',
            'work_items.update',
            'work_items.assign',

            // Progress
            'progress.view',
            'progress.create',
            'progress.review',

            // Workshops
            'workshops.view',
            'workshops.create',
            'workshops.update',

            // Quality & Punch List
            'quality_notes.view',
            'quality_notes.create',
            'quality_notes.close',
            'punch_list.view',
            'punch_list.create',
            'punch_list.close',

            // Scheduling & Analysis
            'schedule.view',
            'schedule.manage',
            'baseline.manage',
            'dependencies.manage',
            'analysis.view',

            // Materials & Inventory
            'materials.view',
            'purchase_requests.create',
            'purchase_requests.review',
            'purchase_orders.manage',
            'receiving.manage',
            'stock.view',
            'stock.issue',
            'stock.manage_thresholds',

            // Equipment
            'equipment.view',
            'equipment.manage',
            'equipment.reserve',
            'equipment.maintenance',

            // Contracts / Payments / Change Orders
            'contracts.view',
            'contracts.create',
            'contracts.lock',
            'payments.view',
            'payments.create',
            'change_orders.view',
            'change_orders.create',
            'change_orders.review',
            'entitlements.view',

            // Delays / Daily Logs
            'delays.view',
            'delays.create',
            'daily_logs.view',

            // Documents / Comments
            'documents.view',
            'documents.create',
            'documents.download',
            'comments.create',
            'comments.view',

            // Users / Auth
            'users.view',
            'users.create',
            'users.update',
            'users.activate_deactivate',
            'users.reset_password',

            // Audit / Reports
            'audit_logs.view',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $this->command->info('All permissions created.');
    }

    private function createRoles(): void
    {
        // Company Admin
        $companyAdmin = Role::firstOrCreate([
            'name' => 'company_admin',
            'guard_name' => 'api',
        ]);

        $companyAdmin->syncPermissions([
            'dashboard.view',

            'projects.view',
            'projects.create',
            'projects.update',
            'projects.manage_budget',
            'projects.manage_members',
            'projects.manage_location',

            'work_items.view',
            'work_items.create',
            'work_items.update',
            'work_items.assign',

            'progress.view',
            'progress.review',

            'workshops.view',
            'workshops.create',
            'workshops.update',

            'quality_notes.view',
            'quality_notes.create',
            'quality_notes.close',
            'punch_list.view',
            'punch_list.create',
            'punch_list.close',

            'schedule.view',
            'schedule.manage',
            'baseline.manage',
            'dependencies.manage',
            'analysis.view',

            'materials.view',
            'purchase_requests.review',
            'purchase_orders.manage',
            'receiving.manage',
            'stock.view',
            'stock.issue',
            'stock.manage_thresholds',

            'equipment.view',
            'equipment.manage',
            'equipment.reserve',
            'equipment.maintenance',

            'contracts.view',
            'contracts.create',
            'contracts.lock',
            'payments.view',
            'payments.create',
            'change_orders.view',
            'change_orders.create',
            'change_orders.review',
            'entitlements.view',

            'delays.view',
            'delays.create',
            'daily_logs.view',

            'documents.view',
            'documents.create',
            'documents.download',
            'comments.create',
            'comments.view',

            'users.view',
            'users.create',
            'users.update',
            'users.activate_deactivate',
            'users.reset_password',

            'audit_logs.view',
            'reports.view',
        ]);

        // Project Manager
        $projectManager = Role::firstOrCreate([
            'name' => 'project_manager',
            'guard_name' => 'api',
        ]);

        $projectManager->syncPermissions([
            'dashboard.view',

            'projects.view',
            'projects.update',
            'projects.manage_budget',
            'projects.manage_members',
            'projects.manage_location',

            'work_items.view',
            'work_items.create',
            'work_items.update',
            'work_items.assign',

            'progress.view',
            'progress.review',

            'workshops.view',
            'workshops.create',
            'workshops.update',

            'quality_notes.view',
            'quality_notes.create',
            'quality_notes.close',
            'punch_list.view',
            'punch_list.create',
            'punch_list.close',

            'schedule.view',
            'schedule.manage',
            'baseline.manage',
            'dependencies.manage',
            'analysis.view',

            'materials.view',
            'purchase_requests.review',
            'purchase_orders.manage',
            'receiving.manage',
            'stock.view',
            'stock.issue',
            'stock.manage_thresholds',

            'equipment.view',
            'equipment.manage',
            'equipment.reserve',
            'equipment.maintenance',

            'contracts.view',
            'contracts.create',
            'contracts.lock',
            'payments.view',
            'payments.create',
            'change_orders.view',
            'change_orders.create',
            'change_orders.review',
            'entitlements.view',

            'delays.view',
            'delays.create',
            'daily_logs.view',

            'documents.view',
            'documents.create',
            'documents.download',
            'comments.create',
            'comments.view',

            'reports.view',
        ]);

        // Assistant
        $assistant = Role::firstOrCreate([
            'name' => 'assistant',
            'guard_name' => 'api',
        ]);

        $assistant->syncPermissions([
            'dashboard.view',

            'projects.view',

            'work_items.view',

            'progress.view',
            'progress.review',

            'workshops.view',

            'quality_notes.view',
            'quality_notes.create',
            'quality_notes.close',
            'punch_list.view',
            'punch_list.create',
            'punch_list.close',

            'schedule.view',
            'analysis.view',

            'materials.view',
            'purchase_requests.review',
            'purchase_orders.manage',
            'receiving.manage',
            'stock.view',
            'stock.issue',

            'equipment.view',
            'equipment.manage',
            'equipment.reserve',
            'equipment.maintenance',

            'contracts.view',
            'payments.view',
            'change_orders.view',
            'entitlements.view',

            'delays.view',
            'delays.create',
            'daily_logs.view',

            'documents.view',
            'documents.create',
            'documents.download',
            'comments.create',
            'comments.view',

            'reports.view',
        ]);

        // Project Owner
        $projectOwner = Role::firstOrCreate([
            'name' => 'project_owner',
            'guard_name' => 'api',
        ]);

        $projectOwner->syncPermissions([
            'dashboard.view',

            'projects.view',
            'work_items.view',
            'progress.view',

            'quality_notes.view',
            'punch_list.view',

            'schedule.view',
            'analysis.view',

            'materials.view',
            'equipment.view',

            'contracts.view',
            'payments.view',
            'change_orders.view',
            'entitlements.view',

            'delays.view',
            'daily_logs.view',

            'documents.view',
            'documents.download',
            'comments.view',

            'reports.view',
        ]);

        $this->command->info('Roles created and assigned.');
    }

    private function createSampleUsers(): void
    {
        // Company Admin
        $companyAdminUser = User::firstOrCreate(
            ['email' => 'admin@alfanar.com'],
            [
                'name' => 'Company Admin',
                'internal_id' => null,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'fcm_token' => 'asdf1342'
            ]
        );
        $companyAdminUser->assignRole('company_admin');
        $companyAdminUser->save();
        // Project Manager
        $projectManagerUser = User::firstOrCreate(
            ['internal_id' => 'pm.ahmad@alfanar'],
            [
                'name' => 'Ahmad Project Manager',
                'email' => 'ahmad.pm@alfanar.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
        $projectManagerUser->assignRole('project_manager');
        $projectManagerUser->save();
        // Assistant
        $assistantUser = User::firstOrCreate(
            ['internal_id' => 'asst.sara@alfanar'],
            [
                'name' => 'Sara Assistant',
                'email' => 'sara.assistant@alfanar.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'fcm_token' => 'asdf1342'
            ]
        );
        $assistantUser->assignRole('assistant');
        $assistantUser->save();
        // Project Owner
        $projectOwnerUser = User::firstOrCreate(
            ['internal_id' => 'owner.khaled@alfanar'],
            [
                'name' => 'Khaled Project Owner',
                'email' => 'khaled.owner@alfanar.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'fcm_token' => 'asdf1342'
            ]
        );
        $projectOwnerUser->assignRole('project_owner');
        $projectOwnerUser->save();
        $this->command->info('Sample users created.');
        $this->command->info('Credentials:');
        $this->command->info('Company Admin: admin@alfanar.com / password');
        $this->command->info('Project Manager: pm.ahmad@alfanar / password');
        $this->command->info('Assistant: asst.sara@alfanar / password');
        $this->command->info('Project Owner: owner.khaled@alfanar / password');
    }
}
