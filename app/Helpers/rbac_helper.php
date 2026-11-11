<?php

use App\Libraries\RbacService;

if (!function_exists('can')) {
    /**
     * Shorthand helper to check current logged-in user permission
     *
     * @param string $permission
     * @return bool
     */
    function can(string $permission): bool
    {
        return RbacService::can($permission);
    }
}

if (!function_exists('current_user_role')) {
    /**
     * Returns the resolved RBAC role of the current user:
     * - 'superadmin'
     * - 'admin'
     * - 'lo'
     * - 'walidata_vertikal'
     * - 'walidata_opd'
     *
     * @return string
     */
    function current_user_role(): string
    {
        return RbacService::resolveRole();
    }
}

if (!function_exists('current_user_role_label')) {
    /**
     * Returns the readable label for the current user's role
     *
     * @return string
     */
    function current_user_role_label(): string
    {
        return RbacService::getRoleLabel(current_user_role());
    }
}

