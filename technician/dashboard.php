<?php

require_once "../includes/auth.php";

requireRole("Technician");

require_once "../config/database.php";

$pageTitle = "Dashboard";
$sidebarDashboardPath = "dashboard.php";
$sidebarTicketsPath = "../tickets.php";
$statusMessage = "";
$statusError = "";

$techId = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ticket_id"], $_POST["status"])) {
    $ticketId = (int) $_POST["ticket_id"];
    $newStatus = $_POST["status"];
    $allowedStatuses = ["Open", "In Progress", "Pending", "Resolved", "Closed"];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $statusError = "That ticket status is not valid.";
    } else {
        $updateStmt = $pdo->prepare("UPDATE tickets SET status = :status, resolved_at = CASE WHEN :status = 'Resolved' THEN CURRENT_TIMESTAMP ELSE NULL END WHERE id = :ticket_id AND assigned_to = :tech_id");
        $updateStmt->execute([
            "status" => $newStatus,
            "ticket_id" => $ticketId,
            "tech_id" => $techId
        ]);

        if ($updateStmt->rowCount() > 0) {
            $statusMessage = "Ticket status updated successfully.";
        } else {
            $statusError = "The ticket could not be updated or is not assigned to you.";
        }
    }
}

$stats = $pdo->prepare("
    SELECT
        COUNT(*) AS assigned_total,
        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_tickets,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_tickets,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_tickets,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_tickets,
        SUM(CASE WHEN priority IN ('High', 'Critical') THEN 1 ELSE 0 END) AS urgent_tickets
    FROM tickets
    WHERE assigned_to = :tech_id
");

$stats->execute(["tech_id" => $techId]);
$statsRow = $stats->fetch();

$assignedTotal = (int) ($statsRow["assigned_total"] ?? 0);
$openTickets = (int) ($statsRow["open_tickets"] ?? 0);
$inProgressTickets = (int) ($statsRow["in_progress_tickets"] ?? 0);
$pendingTickets = (int) ($statsRow["pending_tickets"] ?? 0);
$resolvedTickets = (int) ($statsRow["resolved_tickets"] ?? 0);
$urgentTickets = (int) ($statsRow["urgent_tickets"] ?? 0);

$recentTickets = $pdo->prepare("
    SELECT
        t.id,
        t.ticket_number,
        t.subject,
        t.description,
        t.priority,
        t.status,
        t.created_at,
        c.name AS category_name
    FROM tickets t
    INNER JOIN categories c ON c.id = t.category_id
    WHERE t.assigned_to = :tech_id
    ORDER BY t.created_at DESC
    LIMIT 5
");

$recentTickets->execute(["tech_id" => $techId]);
$recentTickets = $recentTickets->fetchAll();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="main-content">

    <header class="topbar">

        <button class="mobile-menu-button" id="mobileMenuButton">
            ☰
        </button>

        <div class="topbar-title">
            <h1>Technician Dashboard</h1>
            <p>Monitor and resolve support requests</p>
        </div>

        <div class="topbar-actions">
            <button class="notification-button">
                🔔
                <span class="notification-dot"></span>
            </button>

            <div class="user-menu">
                <div class="user-avatar">
                    <?php if (!empty($_SESSION["profile_image"])): ?><img class="profile-avatar-image" src="../assets/<?php echo htmlspecialchars($_SESSION["profile_image"]); ?>" alt="Profile picture"><?php else: ?><?php echo strtoupper(substr($_SESSION["first_name"], 0, 1)); ?><?php endif; ?>
                </div>

                <div class="user-info">
                    <strong>
                        <?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?>
                    </strong>
                    <span>Technician</span>
                </div>
            </div>
        </div>

    </header>

    <section class="dashboard-content">

        <?php if ($statusMessage !== ""): ?><div class="success-message"><?php echo htmlspecialchars($statusMessage); ?></div><?php endif; ?>
        <?php if ($statusError !== ""): ?><div class="error-message"><?php echo htmlspecialchars($statusError); ?></div><?php endif; ?>

        <div class="welcome-section">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>! 👋</h2>
                <p>Here is the live status of your assigned support tasks.</p>
            </div>

            <a href="../tickets.php" class="primary-button">View Queue</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">🎫</div>
                <div class="stat-info">
                    <span>Assigned</span>
                    <strong><?php echo $assignedTotal; ?></strong>
                    <small>Open tickets</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon open">●</div>
                <div class="stat-info">
                    <span>Urgent</span>
                    <strong><?php echo $urgentTickets; ?></strong>
                    <small>High priority</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon progress">◔</div>
                <div class="stat-info">
                    <span>In Progress</span>
                    <strong><?php echo $inProgressTickets; ?></strong>
                    <small>Being handled</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon resolved">✓</div>
                <div class="stat-info">
                    <span>Resolved</span>
                    <strong><?php echo $resolvedTickets; ?></strong>
                    <small>This week</small>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <div>
                        <h3>Queue Summary</h3>
                        <p>Current workload</p>
                    </div>
                </div>

                <div class="ticket-summary">
                    <div class="summary-row">
                        <div class="summary-label">
                            <span class="summary-dot open-dot"></span>
                            <span>Open</span>
                        </div>
                        <strong><?php echo $openTickets; ?></strong>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label">
                            <span class="summary-dot progress-dot"></span>
                            <span>In Progress</span>
                        </div>
                        <strong><?php echo $inProgressTickets; ?></strong>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label">
                            <span class="summary-dot pending-dot"></span>
                            <span>Pending</span>
                        </div>
                        <strong><?php echo $pendingTickets; ?></strong>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label">
                            <span class="summary-dot resolved-dot"></span>
                            <span>Resolved</span>
                        </div>
                        <strong><?php echo $resolvedTickets; ?></strong>
                    </div>
                </div>
            </div>
        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>