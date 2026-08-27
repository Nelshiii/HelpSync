<?php

require_once "auth.php";
requireLogin();
require_once "../config/database.php";

$section = trim($_GET["section"] ?? "This section");
$pageTitle = $section;
$assetPath = "../assets";
$rolePath = strtolower($_SESSION["role"]);
$sidebarDashboardPath = "../{$rolePath}/dashboard.php";
$sidebarTicketsPath = "../tickets.php";
$activeNav = strtolower($section);

if ($section === "Settings" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $_SESSION["dark_mode"] = ($_POST["dark_mode"] ?? "0") === "1";
    $themeStmt = $pdo->prepare("UPDATE users SET dark_mode = :dark_mode WHERE id = :id");
    $themeStmt->execute(["dark_mode" => $_SESSION["dark_mode"] ? 1 : 0, "id" => $_SESSION["user_id"]]);
    $returnTo = $_POST["return_to"] ?? "";
    if (!is_string($returnTo) || !str_starts_with($returnTo, "/")) {
        $returnTo = "../" . $rolePath . "/dashboard.php";
    }
    header("Location: " . $returnTo);
    exit;
}

if (in_array($section, ["Users", "Categories", "Departments", "Reports"], true) && $_SESSION["role"] !== "Admin") {
    header("Location: ../" . $rolePath . "/dashboard.php");
    exit;
}
$rows = [];
$columns = [];
$reportStats = [];
$monthlyStats = [];
$categoryStats = [];
$selectedMonth = $_GET["month"] ?? date("Y-m");
$monthDate = DateTime::createFromFormat("!Y-m", $selectedMonth);

if (!$monthDate || $monthDate->format("Y-m") !== $selectedMonth) {
    $selectedMonth = date("Y-m");
}

switch ($section) {
    case "Users":
        $stmt = $pdo->query("SELECT u.first_name, u.last_name, u.email, r.name AS role_name, d.name AS department_name, u.status FROM users u INNER JOIN roles r ON r.id = u.role_id LEFT JOIN departments d ON d.id = u.department_id ORDER BY u.last_name, u.first_name");
        $rows = $stmt->fetchAll();
        $columns = ["Name", "Email", "Role", "Department", "Status"];
        break;
    case "Categories":
        $stmt = $pdo->query("SELECT name, description, status FROM categories ORDER BY name");
        $rows = $stmt->fetchAll();
        $columns = ["Category", "Description", "Status"];
        break;
    case "Departments":
        $stmt = $pdo->query("SELECT name, status, created_at FROM departments ORDER BY name");
        $rows = $stmt->fetchAll();
        $columns = ["Department", "Status", "Created"];
        break;
    case "Reports":
        $stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM tickets WHERE created_at >= :month_start AND created_at < DATE_ADD(:month_start, INTERVAL 1 MONTH) GROUP BY status ORDER BY FIELD(status, 'Open', 'In Progress', 'Pending', 'Resolved', 'Closed')");
        $stmt->execute(["month_start" => $selectedMonth . "-01"]);
        $reportStats = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%b') AS month_name, DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total FROM tickets WHERE created_at >= DATE_SUB(:month_start, INTERVAL 5 MONTH) AND created_at < DATE_ADD(:month_start_end, INTERVAL 1 MONTH) GROUP BY month_key, month_name ORDER BY month_key");
        $stmt->execute(["month_start" => $selectedMonth . "-01", "month_start_end" => $selectedMonth . "-01"]);
        $monthlyStats = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT c.name, COUNT(t.id) AS total FROM categories c LEFT JOIN tickets t ON t.category_id = c.id AND t.created_at >= :month_start AND t.created_at < DATE_ADD(:month_start_end, INTERVAL 1 MONTH) GROUP BY c.id, c.name ORDER BY total DESC, c.name ASC");
        $stmt->execute(["month_start" => $selectedMonth . "-01", "month_start_end" => $selectedMonth . "-01"]);
        $categoryStats = $stmt->fetchAll();
        break;
    case "Settings":
        $reportStats = [["label" => "Database", "value" => "Connected"], ["label" => "Signed-in role", "value" => $_SESSION["role"]], ["label" => "PHP version", "value" => PHP_VERSION]];
        break;
}

require_once "header.php";
require_once "sidebar.php";

?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-title">
            <h1><?php echo htmlspecialchars($section); ?></h1>
            <p>HelpSync workspace</p>
        </div>
    </header>

    <section class="dashboard-content">
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3><?php echo htmlspecialchars($section); ?></h3>
                    <p><?php echo $section === "Reports" ? "Review all ticket activity by month." : "Live information from the HelpSync system."; ?></p>
                </div>
            </div>

            <?php if (!empty($columns)): ?>
                <div class="table-container">
                    <table>
                        <thead><tr><?php foreach ($columns as $column): ?><th><?php echo htmlspecialchars($column); ?></th><?php endforeach; ?></tr></thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="<?php echo count($columns); ?>" style="text-align: center; padding: 24px; color: #64748b;">No records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php if ($section === "Users"): ?>
                                            <td><?php echo htmlspecialchars($row["first_name"] . " " . $row["last_name"]); ?></td><td><?php echo htmlspecialchars($row["email"]); ?></td><td><?php echo htmlspecialchars($row["role_name"]); ?></td><td><?php echo htmlspecialchars($row["department_name"] ?? "Unassigned"); ?></td><td><?php echo htmlspecialchars($row["status"]); ?></td>
                                        <?php elseif ($section === "Categories"): ?>
                                            <td><?php echo htmlspecialchars($row["name"]); ?></td><td><?php echo htmlspecialchars($row["description"] ?? ""); ?></td><td><?php echo htmlspecialchars($row["status"]); ?></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars($row["name"]); ?></td><td><?php echo htmlspecialchars($row["status"]); ?></td><td><?php echo date("M d, Y g:i A", strtotime($row["created_at"])); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($section === "Reports"): ?>
                <?php
                    $overallTotal = array_sum(array_map(static fn ($stat) => (int) $stat["total"], $reportStats));
                    $statusTotals = [];
                    foreach ($reportStats as $stat) {
                        $statusTotals[$stat["status"]] = (int) $stat["total"];
                    }
                    $monthlyTotals = array_map(static fn ($stat) => (int) $stat["total"], $monthlyStats);
                    $categoryTotals = array_map(static fn ($stat) => (int) $stat["total"], $categoryStats);
                    $maxMonthly = empty($monthlyTotals) ? 1 : max(1, max($monthlyTotals));
                    $maxCategory = empty($categoryTotals) ? 1 : max(1, max($categoryTotals));
                ?>
                <form class="report-filter" method="get">
                    <input type="hidden" name="section" value="Reports">
                    <label for="report-month">Report month</label>
                    <input id="report-month" type="month" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                    <button type="submit" class="primary-button">View Report</button>
                </form>
                <div class="report-stat-grid">
                    <div class="report-stat"><span>Total Tickets</span><strong><?php echo $overallTotal; ?></strong><small>All submitted requests</small></div>
                    <div class="report-stat"><span>Open</span><strong><?php echo $statusTotals["Open"] ?? 0; ?></strong><small>Awaiting action</small></div>
                    <div class="report-stat"><span>In Progress</span><strong><?php echo $statusTotals["In Progress"] ?? 0; ?></strong><small>Being handled</small></div>
                    <div class="report-stat"><span>Resolved</span><strong><?php echo $statusTotals["Resolved"] ?? 0; ?></strong><small>Completed requests</small></div>
                </div>
                <div class="report-grid">
                    <div class="report-panel">
                        <h3>Ticket Activity</h3>
                        <p>Tickets created through <?php echo date("F Y", strtotime($selectedMonth . "-01")); ?></p>
                        <div class="bar-chart">
                            <?php foreach ($monthlyStats as $stat): ?><div class="bar-column"><span><?php echo (int) $stat["total"]; ?></span><div class="bar-fill" style="height: <?php echo max(8, ((int) $stat["total"] / $maxMonthly) * 150); ?>px"></div><small><?php echo htmlspecialchars($stat["month_name"]); ?></small></div><?php endforeach; ?>
                            <?php if (empty($monthlyStats)): ?><p>No ticket activity available.</p><?php endif; ?>
                        </div>
                    </div>
                    <div class="report-panel">
                        <h3>Top Ticket Categories</h3>
                        <p>Category distribution for the selected month</p>
                        <div class="horizontal-bars">
                            <?php foreach ($categoryStats as $stat): ?><div class="horizontal-bar-row"><div><span><?php echo htmlspecialchars($stat["name"]); ?></span><strong><?php echo (int) $stat["total"]; ?></strong></div><div class="horizontal-bar-track"><div class="horizontal-bar-fill" style="width: <?php echo ((int) $stat["total"] / $maxCategory) * 100; ?>%"></div></div></div><?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($section === "Settings"): ?>
                <?php if (isset($_GET["saved"])): ?><div class="success-message">Settings saved successfully.</div><?php endif; ?>
                <form class="settings-form" method="post">
                    <div>
                        <h3>Appearance</h3>
                        <p>Choose the theme for your HelpSync workspace.</p>
                    </div>
                    <label class="theme-toggle"><input type="checkbox" name="dark_mode" <?php echo !empty($_SESSION["dark_mode"]) ? "checked" : ""; ?>><span class="theme-toggle-track"><span></span></span><strong>Dark mode</strong></label>
                    <button type="submit" class="primary-button">Save Settings</button>
                </form>
                <div class="ticket-summary settings-summary">
                    <?php foreach ($reportStats as $stat): ?><div class="summary-row"><span><?php echo htmlspecialchars($stat["label"]); ?></span><strong><?php echo htmlspecialchars($stat["value"]); ?></strong></div><?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ticket-summary">
                    <?php foreach ($reportStats as $stat): ?><div class="summary-row"><span><?php echo htmlspecialchars($stat["label"]); ?></span><strong><?php echo htmlspecialchars($stat["value"]); ?></strong></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once "footer.php"; ?>