<?php

namespace App\Libraries;

/**
 * Enterprise RBAC Service for DDA Online
 * Manages 5 roles across 17 features:
 * - superadmin
 * - admin
 * - lo
 * - walidata_vertikal
 * - walidata_opd
 */
class RbacService
{
    /**
     * Resolves the current user's effective role.
     */
    public static function resolveRole($session = null): string
    {
        $session = $session ?? \Config\Services::session();

        $cachedRole = $session->get('admin_resolved_role');
        if (!empty($cachedRole)) {
            return $cachedRole;
        }

        $level = strtolower(trim((string)$session->get('admin_level')));
        $unitkerja = (string)$session->get('admin_unitkerja');

        if ($level === 'super admin' || $level === 'superadmin') {
            $role = 'superadmin';
        } elseif ($level === 'admin') {
            $role = 'admin';
        } elseif ($level === 'lo' || $level === 'spv') {
            $role = 'lo';
        } elseif ($level === 'walidata' || $level === 'kominfo') {
            $jenisInstansi = self::getJenisInstansi($unitkerja, $session);
            $role = ($jenisInstansi === 'opd') ? 'walidata_opd' : 'walidata_vertikal';
        } else {
            // Default fallback
            $role = 'walidata_vertikal';
        }

        $session->set('admin_resolved_role', $role);
        return $role;
    }

    /**
     * Get jenis_instansi for a unitkerja ('opd' or 'vertikal')
     */
    public static function getJenisInstansi(string $id_unitkerja, $session = null): string
    {
        $session = $session ?? \Config\Services::session();
        $cachedJenis = $session->get('admin_jenis_instansi');
        if (!empty($cachedJenis)) {
            return $cachedJenis;
        }

        if (empty($id_unitkerja)) {
            return 'vertikal';
        }

        try {
            $db = \Config\Database::connect();
            $row = $db->table('m_unitkerja')
                ->select('jenis_instansi')
                ->where('id_unitkerja', $id_unitkerja)
                ->get()
                ->getRow();

            $jenis = ($row && !empty($row->jenis_instansi)) ? strtolower(trim($row->jenis_instansi)) : 'vertikal';
        } catch (\Throwable $e) {
            $jenis = 'vertikal';
        }

        $session->set('admin_jenis_instansi', $jenis);
        return $jenis;
    }

    /**
     * Check if the current user has permission for a specific feature.
     *
     * @param string $permission
     * @param object|null $session
     * @return bool
     */
    public static function can(string $permission, $session = null): bool
    {
        $role = self::resolveRole($session);

        $matrix = [
            // 1. Dashboard
            'dashboard' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => true,
                'walidata_opd'      => true,
            ],
            // 2. Input Data DDA (Upload Excel / Input / Save)
            'input_dda_input' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => true,
                'walidata_opd'      => false,
            ],
            // 3. Verifikasi Data DDA (ACC)
            'input_dda_verif' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 4. Publish Data DDA
            'input_dda_publish' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 5. Lihat / Eksplorasi Data DDA
            'view_dda' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => true,
                'walidata_opd'      => true,
            ],
            // 6. Forum Diskusi
            'forum_diskusi' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => true,
                'walidata_opd'      => true,
            ],
            // 8. Matching PDF
            'matching_pdf' => [
                'superadmin'        => true,
                'admin'             => false,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 9. Laporan Rekap
            'report' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 10. Master Tabel
            'master_tabel' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 11. Master Dimensi
            'master_dimensi' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 12. Master OPD
            'master_opd' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 13. Instansi Pengguna
            'pengguna' => [
                'superadmin'        => true,
                'admin'             => false,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 14. Kelola Admin
            'manage_admin' => [
                'superadmin'        => true,
                'admin'             => false,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 15. Master Tim
            'master_tim' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => false,
                'walidata_vertikal' => false,
                'walidata_opd'      => false,
            ],
            // 16. Ganti Password
            'ganti_password' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => true,
                'walidata_opd'      => true,
            ],
            // 17. Logout
            'logout' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => true,
                'walidata_opd'      => true,
            ],
            // 18. Usulan Tabel Baru (OPD & Vertikal mengajukan usulan, Admin mereview)
            'usulan_tabel' => [
                'superadmin'        => true,
                'admin'             => true,
                'lo'                => true,
                'walidata_vertikal' => true,
                'walidata_opd'      => true,
            ],
        ];

        return $matrix[$permission][$role] ?? false;
    }

    /**
     * Helper to get user-friendly role label
     */
    public static function getRoleLabel(string $role): string
    {
        $labels = [
            'superadmin'        => 'Super Admin',
            'admin'             => 'Admin',
            'lo'                => 'LO',
            'walidata_vertikal' => 'Walidata (Vertikal)',
            'walidata_opd'      => 'Walidata (OPD)',
        ];

        return $labels[$role] ?? ucfirst($role);
    }
}

