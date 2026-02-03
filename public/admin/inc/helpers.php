<?php
declare(strict_types=1);

// Datei: public/admin/inc/helpers.php

if (!function_exists('h')) {
    function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('build_query')) {
    function build_query(array $overrides = []): string
    {
        $base = $_GET;
        foreach ($overrides as $k => $v) {
            if ($v === null) unset($base[$k]);
            else $base[$k] = (string)$v;
        }
        return http_build_query($base);
    }
}

if (!function_exists('sort_link')) {
    function sort_link(string $col): string
    {
        $currentSort = (string)($_GET['sort'] ?? 'id');
        $currentDir  = strtolower((string)($_GET['dir'] ?? 'asc'));
        $newDir = 'asc';
        if ($currentSort === $col && $currentDir === 'asc') $newDir = 'desc';
        return '/admin/applications.php?' . build_query(['sort' => $col, 'dir' => $newDir, 'page' => 1]);
    }
}

if (!function_exists('sort_indicator')) {
    function sort_indicator(string $col): string
    {
        $currentSort = (string)($_GET['sort'] ?? 'id');
        $currentDir  = strtolower((string)($_GET['dir'] ?? 'asc'));
        if ($currentSort !== $col) return '';
        return $currentDir === 'asc' ? ' ▲' : ' ▼';
    }
}
