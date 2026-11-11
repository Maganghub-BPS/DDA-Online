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
     * @param string|null $periode Period string (e.g. "2024", "2021,2022,2023,2024,2025")
     * @param string $lang Language code ('id' or 'en')
     * @return string Formatted title
     */
    function format_judul_tabel(?string $judul, ?string $periode, string $lang = 'id'): string
    {
        $judul = trim($judul ?? '');
        $periode = trim($periode ?? '');

        if ($judul === '') {
            return '';
        }
        if ($periode === '') {
            return trim(preg_replace('/\s+/', ' ', str_ireplace('[PERIODE]', '', $judul)));
        }

        // Jika periode berisi deretan tahun dipisah koma (misal: "2021,2022,2023,2024,2025" atau "2021, 2022")
        if (preg_match('/^\d{4}(?:\s*,\s*\d{4})+$/', $periode)) {
            if (!function_exists('formatDynamicTableTitle')) {
                helper('my');
            }
            if (function_exists('formatDynamicTableTitle')) {
                $years = array_map('intval', array_map('trim', explode(',', $periode)));
                return formatDynamicTableTitle($judul, $years, $lang);
            }
        }

        if (stripos($judul, '[PERIODE]') !== false) {
            return str_ireplace('[PERIODE]', $periode, $judul);
        }

        return rtrim($judul, " ,") . ', ' . $periode;
    }
}
