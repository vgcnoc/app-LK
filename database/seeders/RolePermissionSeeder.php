<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'akses_dashboard',
            // Transaksi / Buku Kas
            'akses_transaksi',
            'tambah_transaksi',
            'edit_transaksi',
            'hapus_transaksi',
            // Pelanggan Aktif
            'akses_data_pelanggan',
            'tambah_pelanggan',
            'edit_pelanggan',
            'hapus_pelanggan',
            // Pelanggan Non-Aktif
            'akses_data_nonaktif',
            // Booking Pelanggan
            'akses_booking',
            'tambah_booking',
            'edit_booking',
            'hapus_booking',
            'setujui_booking', // Activate to active customer
            // Billing
            'akses_billing',
            'tambah_billing',
            'edit_billing',
            'hapus_billing',
            // Voucher & Saldo
            'akses_voucher_saldo',
            'tambah_voucher_saldo',
            'edit_voucher_saldo',
            'hapus_voucher_saldo',
            // Multi-Tier Affiliate & Sales
            'akses_affiliate',
            'tambah_affiliate',
            'edit_affiliate',
            'hapus_affiliate',
            'kelola_akun_sales',
            // Laporan
            'akses_laporan',
            'ekspor_laporan',
            // Master Data
            'akses_master_data',
            'tambah_master_data',
            'edit_master_data',
            'hapus_master_data',
            // Pengaturan
            'manajemen_pengguna',
            'manajemen_role',
            'pengaturan_aplikasi', // App logo, etc
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define Spatie Roles
        $roles = [
            'admin',
            'staff',
            'sales',
            'finance',
            'manajer'
        ];

        foreach ($roles as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
        }

        // Give all permissions to admin role
        $adminRole = \Spatie\Permission\Models\Role::findByName('admin');
        $adminRole->syncPermissions($permissions);

        // Assign default permissions to sales
        $salesRole = \Spatie\Permission\Models\Role::findByName('sales');
        $salesRole->syncPermissions([
            'akses_dashboard',
            'akses_booking',
            'tambah_booking',
            'akses_affiliate',
        ]);

        // Assign default permissions to staff
        $staffRole = \Spatie\Permission\Models\Role::findByName('staff');
        $staffRole->syncPermissions([
            'akses_dashboard',
            'akses_transaksi',
            'tambah_transaksi',
            'akses_data_pelanggan',
            'tambah_pelanggan',
            'edit_pelanggan',
            'akses_booking',
            'setujui_booking',
        ]);

        // Assign default permissions to finance
        $financeRole = \Spatie\Permission\Models\Role::findByName('finance');
        $financeRole->syncPermissions([
            'akses_dashboard',
            'akses_transaksi',
            'tambah_transaksi',
            'edit_transaksi',
            'akses_billing',
            'tambah_billing',
            'edit_billing',
            'akses_laporan',
            'ekspor_laporan',
        ]);

        // Assign default permissions to manajer
        $manajerRole = \Spatie\Permission\Models\Role::findByName('manajer');
        $manajerRole->syncPermissions([
            'akses_dashboard',
            'akses_transaksi',
            'akses_data_pelanggan',
            'akses_data_nonaktif',
            'akses_billing',
            'akses_voucher_saldo',
            'akses_affiliate',
            'akses_laporan',
            'ekspor_laporan',
        ]);

        // For existing users with 'admin' role string, assign them the Spatie 'admin' role
        // Also sync to clear out their direct permissions so it respects the Role.
        $adminUsers = User::where('role', 'admin')->get();
        foreach ($adminUsers as $user) {
            $user->syncRoles(['admin']);
            $user->syncPermissions([]); // Clear direct permissions so they inherit from role
        }
        
        // For other users, sync roles based on their string 'role'
        $otherUsers = User::where('role', '!=', 'admin')->get();
        foreach ($otherUsers as $user) {
            if ($user->role && in_array($user->role, $roles)) {
                $user->syncRoles([$user->role]);
            }
            $user->syncPermissions([]); // Clear direct permissions
        }
    }
}
