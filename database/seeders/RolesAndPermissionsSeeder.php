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
        $defaultPassword = Hash::make('password');
        // Company Admin
        $companyAdminUser = User::firstOrCreate(
            ['email' => 'admin@mutqin.com'],
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
            ['internal_id' => 'pm.ahmad@mutqin'],
            [
                'name' => 'Ahmad Project Manager',
                'email' => 'ahmad.pm@mutqin.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
        $projectManagerUser->assignRole('project_manager');
        $projectManagerUser->save();
        // Additional 5 Project Managers
        $additionalPMs = [
            ['name' => 'Omar Al-Ali', 'internal_id' => 'pm.omar@mutqin', 'email' => 'omar.pm@mutqin.com'],
            ['name' => 'Tarek Mansour', 'internal_id' => 'pm.tarek@mutqin', 'email' => 'tarek.pm@mutqin.com'],
            ['name' => 'Youssef Nader', 'internal_id' => 'pm.youssef@mutqin', 'email' => 'youssef.pm@mutqin.com'],
            ['name' => 'Rami Al-Khatib', 'internal_id' => 'pm.rami@mutqin', 'email' => 'rami.pm@mutqin.com'],
            ['name' => 'Zaid Hamdan', 'internal_id' => 'pm.zaid@mutqin', 'email' => 'zaid.pm@mutqin.com'],
        ];

        foreach ($additionalPMs as $pm) {
            $user = User::firstOrCreate(
                ['internal_id' => $pm['internal_id']],
                [
                    'name' => $pm['name'],
                    'email' => $pm['email'],
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]
            );
            $user->assignRole('project_manager');
            $user->save();
        }

        // Assistant (Original)
        $assistantUser = User::firstOrCreate(
            ['internal_id' => 'asst.sara@mutqin'],
            [
                'name' => 'Sara Assistant',
                'email' => 'sara.assistant@mutqin.com',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'status' => 'active',
                'fcm_token' => 'asdf1342'
            ]
        );
        $assistantUser->assignRole('assistant');
        $assistantUser->save();

        // Additional 5 Assistants
        $additionalAssistants = [
            ['name' => 'Nour Al-Huda', 'internal_id' => 'asst.nour@mutqin', 'email' => 'nour.assistant@mutqin.com'],
            ['name' => 'Maya Hasan', 'internal_id' => 'asst.maya@mutqin', 'email' => 'maya.assistant@mutqin.com'],
            ['name' => 'Hiba Al-Masri', 'internal_id' => 'asst.hiba@mutqin', 'email' => 'hiba.assistant@mutqin.com'],
            ['name' => 'Fadi Othman', 'internal_id' => 'asst.fadi@mutqin', 'email' => 'fadi.assistant@mutqin.com'],
            ['name' => 'Laila Salem', 'internal_id' => 'asst.laila@mutqin', 'email' => 'laila.assistant@mutqin.com'],
        ];

        foreach ($additionalAssistants as $asst) {
            $user = User::firstOrCreate(
                ['internal_id' => $asst['internal_id']],
                [
                    'name' => $asst['name'],
                    'email' => $asst['email'],
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'fcm_token' => 'asdf1342',
                ]
            );
            $user->assignRole('assistant');
            $user->save();
        }

        // Project Owner (Original)
        $projectOwnerUser = User::firstOrCreate(
            ['internal_id' => 'owner.khaled@mutqin'],
            [
                'name' => 'Khaled Project Owner',
                'email' => 'khaled.owner@mutqin.com',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'status' => 'active',
                'fcm_token' => 'asdf1342'
            ]
        );
        $projectOwnerUser->assignRole('project_owner');
        $projectOwnerUser->save();

        // Additional 5 Project Owners
        $additionalOwners = [
            ['name' => 'Bilal Al-Sayed', 'internal_id' => 'owner.bilal@mutqin', 'email' => 'bilal.owner@mutqin.com'],
            ['name' => 'Samer Al-Ahmad', 'internal_id' => 'owner.samer@mutqin', 'email' => 'samer.owner@mutqin.com'],
            ['name' => 'Hasan Kassam', 'internal_id' => 'owner.hasan@mutqin', 'email' => 'hasan.owner@mutqin.com'],
            ['name' => 'Majd Al-Rifai', 'internal_id' => 'owner.majd@mutqin', 'email' => 'majd.owner@mutqin.com'],
            ['name' => 'Wael Al-Bitar', 'internal_id' => 'owner.wael@mutqin', 'email' => 'wael.owner@mutqin.com'],
        ];

        foreach ($additionalOwners as $owner) {
            $user = User::firstOrCreate(
                ['internal_id' => $owner['internal_id']],
                [
                    'name' => $owner['name'],
                    'email' => $owner['email'],
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'fcm_token' => 'asdf1342',
                ]
            );
            $user->assignRole('project_owner');
            $user->save();
        }
        $this->command->info('Sample users created.');
        $this->command->info('Credentials:');
        $this->command->info('Company Admin: admin@mutqin.com / password');
        $this->command->info('Project Manager: pm.ahmad@mutqin / password');
        $this->command->info('Assistant: asst.sara@mutqin / password');
        $this->command->info('Project Owner: owner.khaled@mutqin / password');
    }
}
