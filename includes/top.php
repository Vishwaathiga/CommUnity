
<?php
/**
 * CommUnity - Dashboard Layout: Top (Header + Sidebar)
 * Expects: $pageTitle, $activePage, $role
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/functions.php';

if (!isset($role)) $role = current_role();
if (!isset($pageTitle)) $pageTitle = 'Dashboard';

$unread_count = is_logged_in() ? unread_notification_count(current_user_id()) : 0;

$notif_page = match ($role) {
    'admin'     => BASE_URL . 'admin/settings.php',
    'watchman'  => BASE_URL . 'watchman/dashboard.php',
    'resident'  => BASE_URL . 'resident/notifications.php',
    default     => BASE_URL . 'index.php',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
    <link rel="icon" href="<?= e(BASE_URL) ?>assets/images/favicon.png" type="image/png">
</head>
<body class="dashboard-body" data-base-url="<?= e(BASE_URL) ?>">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <a href="<?= e(BASE_URL) ?>" class="brand-link">
            <span class="brand-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                </svg>
            </span>
            <span class="brand-text"><?= e(APP_NAME) ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">

        <?php if ($role === 'admin'): ?>

            <a href="<?= e(BASE_URL) ?>admin/dashboard.php" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="9"/>
                    <rect x="14" y="3" width="7" height="5"/>
                    <rect x="14" y="12" width="7" height="9"/>
                    <rect x="3" y="16" width="7" height="5"/>
                </svg>
                Dashboard
            </a>

            <a href="<?= e(BASE_URL) ?>admin/residents.php" class="nav-link <?= ($activePage ?? '') === 'residents' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Residents
            </a>

            <a href="<?= e(BASE_URL) ?>admin/blocks.php" class="nav-link <?= ($activePage ?? '') === 'blocks' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18M5 21V7l7-4 7 4v14"/>
                </svg>
                Blocks
            </a>

            <a href="<?= e(BASE_URL) ?>admin/users.php" class="nav-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M20 8v6M23 11h-6"/>
                </svg>
                Users
            </a>

<a href="<?= e(BASE_URL) ?>admin/workers.php"
   class="nav-link <?= ($activePage ?? '') === 'workers' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
         stroke="currentColor" stroke-width="2">
        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="10" cy="7" r="4"/>
        <path d="M20 8v6M23 11h-6"/>
    </svg>
    Worker Management
</a>


            <a href="<?= e(BASE_URL) ?>admin/visitors.php" class="nav-link <?= ($activePage ?? '') === 'visitors' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M17 11h6M20 8v6"/>
                </svg>
                Visitors
            </a>

            <a href="<?= e(BASE_URL) ?>admin/deliveries.php" class="nav-link <?= ($activePage ?? '') === 'deliveries' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/>
                </svg>
                Deliveries
            </a>

            <a href="<?= e(BASE_URL) ?>admin/reports.php" class="nav-link <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                </svg>
                Reports
            </a>

            <a href="<?= e(BASE_URL) ?>admin/activity_logs.php" class="nav-link <?= ($activePage ?? '') === 'logs' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
                Activity Logs
            </a>

            <a href="<?= e(BASE_URL) ?>admin/settings.php" class="nav-link <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                Settings
            </a>

            <a href="<?= e(BASE_URL) ?>admin/complaints.php" class="nav-link <?= ($activePage ?? '') === 'complaints' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Complaints
            </a>

            <!-- Announcements -->
            <a href="<?= e(BASE_URL) ?>admin/announcements.php" class="nav-link <?= ($activePage ?? '') === 'announcements' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 11V5l16-2v18L4 15v-4"/>
                    <path d="M4 11H2v4h2M8 16l2 5"/>
                </svg>
                Announcements
            </a>

        <?php elseif ($role === 'watchman'): ?>

            <a href="<?= e(BASE_URL) ?>watchman/dashboard.php" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="9"/>
                    <rect x="14" y="3" width="7" height="5"/>
                    <rect x="14" y="12" width="7" height="9"/>
                    <rect x="3" y="16" width="7" height="5"/>
                </svg>
                Dashboard
            </a>

            <a href="<?= e(BASE_URL) ?>watchman/residents.php" class="nav-link <?= ($activePage ?? '') === 'residents' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Resident Directory
            </a>

            <a href="<?= e(BASE_URL) ?>watchman/visitors.php" class="nav-link <?= ($activePage ?? '') === 'visitors' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M17 11h6M20 8v6"/>
                </svg>
                Visitor Management
            </a>

            <a href="<?= e(BASE_URL) ?>watchman/entry_exit.php" class="nav-link <?= ($activePage ?? '') === 'entry_exit' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                </svg>
                Entry / Exit
            </a>

            <a href="<?= e(BASE_URL) ?>watchman/deliveries.php" class="nav-link <?= ($activePage ?? '') === 'deliveries' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/>
                </svg>
                Deliveries
            </a>

        <?php elseif ($role === 'resident'): ?>

            <a href="<?= e(BASE_URL) ?>resident/dashboard.php" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="9"/>
                    <rect x="14" y="3" width="7" height="5"/>
                    <rect x="14" y="12" width="7" height="9"/>
                    <rect x="3" y="16" width="7" height="5"/>
                </svg>
                Dashboard
            </a>

            <a href="<?= e(BASE_URL) ?>resident/profile.php" class="nav-link <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                My Profile
            </a>

            <a href="<?= e(BASE_URL) ?>resident/complaints.php" class="nav-link <?= ($activePage ?? '') === 'complaints' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Complaints
            </a>

            <a href="<?= e(BASE_URL) ?>resident/visitors.php" class="nav-link <?= ($activePage ?? '') === 'visitors' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M17 11h6M20 8v6"/>
                </svg>
                Visitors
            </a>

            <a href="<?= e(BASE_URL) ?>resident/local_help.php" class="nav-link <?= ($activePage ?? '') === 'local_help' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                Local Help
            </a>

            <a href="<?= e(BASE_URL) ?>resident/help_requests.php" class="nav-link <?= ($activePage ?? '') === 'help_requests' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Help Requests
            </a>

            <a href="<?= e(BASE_URL) ?>resident/resources.php" class="nav-link <?= ($activePage ?? '') === 'resources' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4z"/>
                    <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>
                </svg>
                Resource Sharing
            </a>

            <a href="<?= e(BASE_URL) ?>resident/my_skills.php" class="nav-link <?= ($activePage ?? '') === 'my_skills' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                My Skills
            </a>

            <a href="<?= e(BASE_URL) ?>resident/my_resources.php" class="nav-link <?= ($activePage ?? '') === 'my_resources' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                My Resources
            </a>

            <a href="<?= e(BASE_URL) ?>resident/notifications.php" class="nav-link <?= ($activePage ?? '') === 'notifications' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= e(BASE_URL) ?>resident/my_activity.php" class="nav-link <?= ($activePage ?? '') === 'my_activity' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
                My Activity
            </a>
<a href="<?= e(BASE_URL) ?>resident/announcements.php" class="nav-link <?= ($activePage ?? '') === 'announcements' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 11V5l16-2v18L4 15v-4"/>
        <path d="M4 11H2v4h2M8 16l2 5"/>
    </svg>
    Announcements
</a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="<?= e(BASE_URL) ?>logout.php" class="nav-link logout-link">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            Logout
        </a>
    </div>

</aside>

<!-- Main Content -->
<div class="main-content" id="mainContent">

    <!-- Top Header -->
    <header class="top-header">

        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18"/>
            </svg>
        </button>

        <div class="header-title">
            <h1><?= e($pageTitle) ?></h1>
        </div>

        <div class="header-actions">

            <a href="<?= e($notif_page) ?>" class="notification-bell" id="notificationBell" title="Notifications">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>

                <?php if ($unread_count > 0): ?>
                    <span class="bell-badge" id="bellBadge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>

            <div class="user-info">
                <div class="user-avatar">
                    <img src="<?= e(profile_image_url($_SESSION['profile_image'] ?? null)) ?>" alt="Avatar" onerror="this.src='<?= e(BASE_URL) ?>assets/images/default-avatar.png'">
                </div>

                <div class="user-details">
                    <span class="user-name"><?= e($_SESSION['name'] ?? 'User') ?></span>
                    <span class="user-role"><?= e(ucfirst($role)) ?></span>
                </div>
            </div>

        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

        <?php
        $flash = get_flash();

        if ($flash):
        ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible" id="flashAlert">
                <?= e($flash['message']) ?>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>
