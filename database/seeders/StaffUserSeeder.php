<?php

namespace Database\Seeders;

use App\Models\StaffUser;
use App\Models\AdminCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    /**
     * Seed staff user accounts (no applicant data).
     */
    public function run(): void
    {
        // Default Admin Account in admin_credential
        if (DB::table('admin_credential')->where('admin_username', 'ADMIN')->doesntExist()) {
            DB::table('admin_credential')->insert([
                'admin_id' => 1,
                'admin_username' => 'ADMIN',
                'admin_password' => Hash::make('admin123'),
                'two_factor_enabled' => 0,
            ]);
        }

        // Staff Accounts in user_table
        $staffAccounts = [
            [
                'user_id' => 1,
                'username' => 'RECRUITER',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Recruiter Staff',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
            [
                'user_id' => 2,
                'username' => 'REGISTRAR',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Registrar Staff',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
            [
                'user_id' => 6,
                'username' => 'DOCTOR',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Chief Doctor',
                'active_stat' => 'active',
                'rank' => 'COL',
                'afpos' => 'MC',
                'status' => 'GSC',
                'position' => 'CHIEF DOCTOR',
                'e_signature_path' => 'signatures/NC6wkrc9PrJbVYTWXAbLCokqiSypf6G3e3cK8Wov.png',
            ],
            [
                'user_id' => 32,
                'username' => 'DOCTOR_ECG_XRAY',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'jose p rizal',
                'active_stat' => 'active',
                'rank' => 'LTC',
                'afpos' => 'MC',
                'status' => 'GSC',
                'position' => 'MEMBER',
                'e_signature_path' => 'signatures/rXeiCPM717aNX4V6oA2Ab2XDqQOG26sOw18KhABl.png',
            ],
            [
                'user_id' => 35,
                'username' => 'DOCTOR_NP',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'baldonado m baltog',
                'active_stat' => 'active',
                'rank' => 'COL',
                'afpos' => 'MC',
                'status' => 'GSC',
                'position' => 'CHAIRMAN',
                'e_signature_path' => 'signatures/hXTSn1DmtZVgehyUZS5v8Z84itbxuUs3qJWNRHlN.png',
            ],
            [
                'user_id' => 36,
                'username' => 'DOCTOR_OPTHA_EENT',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'anastacio j balbitero',
                'active_stat' => 'active',
                'rank' => 'MAJ',
                'afpos' => 'MC',
                'status' => 'GSC',
                'position' => 'MEMBER',
                'e_signature_path' => 'signatures/1c2vlZM2okhBZkzqAZEs8BldtERz4hJD0H9Ug9kY.png',
            ],
            [
                'user_id' => 39,
                'username' => 'DOCTOR_DENTAL',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'lauro p lagim',
                'active_stat' => 'active',
                'rank' => 'MAJ',
                'afpos' => 'MC',
                'status' => 'GSC',
                'position' => 'MEMBER',
                'e_signature_path' => 'signatures/kYdoNzUNGsWdbNq2Ge9oySodE2SYJz60T90XA52j.png',
            ],
            [
                'user_id' => 40,
                'username' => 'DENTAL',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'laag',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
            [
                'user_id' => 41,
                'username' => 'ECG',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Pvt Jovy Japril',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
            [
                'user_id' => 42,
                'username' => 'NP',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Lourd',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
            [
                'user_id' => 44,
                'username' => 'DOCTOR_LAB_DRUGTEST',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'bogart d macapagal',
                'active_stat' => 'active',
                'rank' => 'LTC',
                'afpos' => 'MC',
                'status' => 'GSC',
                'position' => 'MEMBER',
                'e_signature_path' => 'signatures/QR9aZqrjEsGDkHPPJKeSVefkgGIaaJZ30kVQAZa7.png',
            ],
            [
                'user_id' => 45,
                'username' => 'EXECUTIVE',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Executive Officer',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
            [
                'user_id' => 53,
                'username' => 'DOCTOR_GPE',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Pedro D Penduko',
                'active_stat' => 'active',
                'rank' => 'CPT',
                'afpos' => 'MC',
                'status' => null,
                'position' => 'MEMBER',
                'e_signature_path' => 'signatures/0Ghvs6AFCg7GvOztdxXpj65bFILZh4nYSYpMFwNE.png',
            ],
            [
                'user_id' => 54,
                'username' => 'GPE',
                'password' => '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e',
                'user_username' => 'Pvt Jovy Japril',
                'active_stat' => 'active',
                'rank' => null,
                'afpos' => null,
                'status' => null,
                'position' => null,
                'e_signature_path' => null,
            ],
        ];

        foreach ($staffAccounts as $account) {
            DB::table('user_table')->updateOrInsert(
                ['username' => $account['username']],
                $account
            );
        }
    }
}
