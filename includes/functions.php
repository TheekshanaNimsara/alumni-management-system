<?php
// ============================================================
// University Alumni Network - General Reusable Functions
// ============================================================
require_once __DIR__ . '/../config/db.php';

function sanitize_output($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function time_ago($datetime) {
    if (empty($datetime)) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $timestamp);
}

function get_user_avatar_url($profile_picture = null) {
    $baseUrl = get_base_url();
    if (!empty($profile_picture) && $profile_picture !== 'default-avatar.svg') {
        $cleanPic = basename($profile_picture);
        if ($cleanPic === $profile_picture && file_exists(__DIR__ . '/../uploads/profiles/' . $cleanPic)) {
            return $baseUrl . 'uploads/profiles/' . htmlspecialchars($cleanPic, ENT_QUOTES, 'UTF-8');
        }
    }
    return $baseUrl . 'assets/images/default-avatar.svg';
}

