<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('format_judul_tabel')) {
    /**
     * Format table title with period (dwibahasa).
     * 
     * @param string|null $judul Title string (ID or EN)
     * @param string|null $periode Period string (periode_id or periode_en)
     * @return string Formatted title
     */
    function format_judul_tabel(?string $judul, ?string $periode): string
    {
        $judul = trim($judul ?? '');
        $periode = trim($periode ?? '');

        if ($judul === '') {
            return '';
        }
        if ($periode === '') {
            return trim(preg_replace('/\s+/', ' ', str_replace('[PERIODE]', '', $judul)));
        }
        if (strpos($judul, '[PERIODE]') !== false) {
            return str_replace('[PERIODE]', $periode, $judul);
        }

        return rtrim($judul, " ,") . ', ' . $periode;
    }
}
