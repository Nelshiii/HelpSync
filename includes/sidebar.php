<?php
$sidebarDashboardPath = $sidebarDashboardPath ?? "dashboard.php";
$sidebarTicketsPath = $sidebarTicketsPath ?? "../tickets.php";
$sidebarProfilePath = $sidebarProfilePath ?? "../profile.php";
$sidebarSettingsPath = $sidebarSettingsPath ?? "../includes/coming_soon.php?section=Settings";
$sidebarLogoutPath = $sidebarLogoutPath ?? "../logout.php";
$activeNav = $activeNav ?? (basename($_SERVER["PHP_SELF"]) === "tickets.php" ? "tickets" : "dashboard");
$userRole = $_SESSION["role"] ?? "";
?>

<aside class="sidebar">

    <div class="sidebar-logo">
        <div class="logo-icon"><img src="../assets/images/logo.png" alt="HelpSync logo"></div>

        <div>
            <h2>HelpSync</h2>
            <span>IT Support System</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <p class="nav-section-title">MAIN</p>

        <a href="<?php echo htmlspecialchars($sidebarDashboardPath); ?>" class="nav-item <?php echo $activeNav === "dashboard" ? "active" : ""; ?>">
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo htmlspecialchars($sidebarTicketsPath); ?>" class="nav-item <?php echo $activeNav === "tickets" ? "active" : ""; ?>">
            <span class="nav-icon">▣</span>
            <span>Tickets</span>
        </a>

        <?php if ($userRole === "Admin"): ?>
        <p class="nav-section-title">MANAGEMENT</p>

        <a href="../includes/coming_soon.php?section=Users" class="nav-item <?php echo $activeNav === "users" ? "active" : ""; ?>">
            <span class="nav-icon">♙</span>
            <span>Users</span>
        </a>

        <p class="nav-section-title">ANALYTICS</p>

        <a href="../includes/coming_soon.php?section=Reports" class="nav-item <?php echo $activeNav === "reports" ? "active" : ""; ?>">
            <span class="nav-icon">▥</span>
            <span>Reports</span>
        </a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-bottom">

        <a href="<?php echo htmlspecialchars($sidebarProfilePath); ?>" class="nav-item <?php echo $activeNav === "profile" ? "active" : ""; ?>">
            <span class="nav-icon">♙</span>
            <span>Profile</span>
        </a>

        <form method="post" action="<?php echo htmlspecialchars($sidebarSettingsPath); ?>" class="sidebar-theme-form">
            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>">
            <input type="hidden" name="dark_mode" value="<?php echo !empty($_SESSION["dark_mode"]) ? "0" : "1"; ?>">
            <button type="submit" class="nav-item sidebar-theme-button">
                <span class="nav-icon"><?php echo !empty($_SESSION["dark_mode"]) ? "☀" : "☾"; ?></span>
                <span><?php echo !empty($_SESSION["dark_mode"]) ? "Light mode" : "Dark mode"; ?></span>
            </button>
        </form>

        <a href="<?php echo htmlspecialchars($sidebarLogoutPath); ?>" class="nav-item logout-item">
            <span class="nav-icon">↪</span>
            <span>Logout</span>
        </a>

    </div>

</aside>