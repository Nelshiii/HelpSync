<?php

require_once "../includes/auth.php";

requireRole("Admin");

require_once "../config/database.php";

$pageTitle = "Dashboard";
$sidebarDashboardPath = "dashboard.php";
$sidebarTicketsPath = "../tickets.php";

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total_tickets,
        SUM(CASE WHEN t.status = 'Open' THEN 1 ELSE 0 END) AS open_tickets,
        SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_tickets,
        SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) AS pending_tickets,
        SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_tickets
    FROM tickets t
    INNER JOIN users u ON u.id = t.created_by
    INNER JOIN roles r ON r.id = u.role_id
    WHERE r.name = 'Employee'
    AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetch();

$totalTickets = (int) ($stats["total_tickets"] ?? 0);
$openTickets = (int) ($stats["open_tickets"] ?? 0);
$inProgressTickets = (int) ($stats["in_progress_tickets"] ?? 0);
$pendingTickets = (int) ($stats["pending_tickets"] ?? 0);
$resolvedTickets = (int) ($stats["resolved_tickets"] ?? 0);

$stmt = $pdo->query("
    SELECT
        t.ticket_number,
        t.subject,
        t.description,
        t.priority,
        t.status,
        c.name AS category_name
    FROM tickets t
    INNER JOIN categories c ON c.id = t.category_id
    INNER JOIN users u ON u.id = t.created_by
    INNER JOIN roles r ON r.id = u.role_id
    WHERE r.name = 'Employee'
    AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY t.created_at DESC
    LIMIT 5
");

$recentTickets = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="main-content">

    <!-- Top Bar -->
    <header class="topbar">

        <button class="mobile-menu-button" id="mobileMenuButton">
            ☰
        </button>

        <div class="topbar-title">
            <h1>Dashboard</h1>
            <p>Overview of your IT support system</p>
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
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["first_name"] . " " . $_SESSION["last_name"]
                        );
                        ?>
                    </strong>

                    <span>
                        <?php echo htmlspecialchars($_SESSION["role"]); ?>
                    </span>
                </div>

            </div>

        </div>

    </header>


    <!-- Dashboard Content -->
    <section class="dashboard-content">

        <div class="welcome-section">

            <div>
                <h2>
                    Welcome back,
                    <?php echo htmlspecialchars($_SESSION["first_name"]); ?>! 👋
                </h2>

                <p>
                    Weekly overview of employee support requests from the last 7 days.
                </p>
            </div>

            <a href="../tickets.php" class="primary-button">
                View Tickets
            </a>

        </div>


        <!-- Statistics -->
        <div class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon total">
                    🎫
                </div>

                <div class="stat-info">
                    <span>Employee Tickets</span>
                    <strong><?php echo $totalTickets; ?></strong>
                    <small>Last 7 days</small>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon open">
                    ●
                </div>

                <div class="stat-info">
                    <span>Open</span>
                    <strong><?php echo $openTickets; ?></strong>
                    <small>Last 7 days</small>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon progress">
                    ◔
                </div>

                <div class="stat-info">
                    <span>In Progress</span>
                    <strong><?php echo $inProgressTickets; ?></strong>
                    <small>Last 7 days</small>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon resolved">
                    ✓
                </div>

                <div class="stat-info">
                    <span>Resolved</span>
                    <strong><?php echo $resolvedTickets; ?></strong>
                    <small>Last 7 days</small>
                </div>

            </div>

        </div>


        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Recent Tickets -->
            <div class="dashboard-card recent-tickets">

                <div class="card-header">

                    <div>
                        <h3>Recent Tickets</h3>
                        <p>Latest support requests</p>
                    </div>

                    <a href="../tickets.php">View all</a>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>
                                <th>Ticket</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($recentTickets)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;">
                                        No tickets found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <?php
                                        $priorityClass = strtolower($ticket["priority"]);
                                        $statusClass = "";
                                        switch ($ticket["status"]) {
                                            case "Open":
                                                $statusClass = "open-status"; break;
                                            case "In Progress":
                                                $statusClass = "progress-status"; break;
                                            case "Resolved":
                                                $statusClass = "resolved-status"; break;
                                            case "Pending":
                                                $statusClass = "pending-status"; break;
                                            default:
                                                $statusClass = "closed-status"; break;
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><a href="../ticket.php?number=<?php echo urlencode($ticket["ticket_number"]); ?>"><?php echo htmlspecialchars($ticket["ticket_number"]); ?></a></strong>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($ticket["subject"]); ?>
                                        </td>

                                        <td class="ticket-description" title="<?php echo htmlspecialchars($ticket["description"]); ?>">
                                            <?php echo htmlspecialchars($ticket["description"]); ?>
                                        </td>

                                        <td>
                                            <span class="priority <?php echo $priorityClass; ?>">
                                                <?php echo htmlspecialchars($ticket["priority"]); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="status <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($ticket["status"]); ?>
                                            </span>
                                        </td>

                                        <td><a href="../ticket.php?number=<?php echo urlencode($ticket["ticket_number"]); ?>" class="ticket-details-button">View details</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- Ticket Summary -->
            <div class="dashboard-card">

                <div class="card-header">

                    <div>
                        <h3>Ticket Summary</h3>
                        <p>Current ticket distribution</p>
                    </div>

                </div>

                <div class="ticket-summary">

                    <div class="summary-row">

                        <div class="summary-label">
                            <span class="summary-dot open-dot"></span>
                            Open
                        </div>

                        <strong><?php echo $openTickets; ?></strong>

                    </div>


                    <div class="summary-row">

                        <div class="summary-label">
                            <span class="summary-dot progress-dot"></span>
                            In Progress
                        </div>

                        <strong><?php echo $inProgressTickets; ?></strong>

                    </div>


                    <div class="summary-row">

                        <div class="summary-label">
                            <span class="summary-dot pending-dot"></span>
                            Pending
                        </div>

                        <strong><?php echo $pendingTickets; ?></strong>

                    </div>


                    <div class="summary-row">

                        <div class="summary-label">
                            <span class="summary-dot resolved-dot"></span>
                            Resolved
                        </div>

                        <strong><?php echo $resolvedTickets; ?></strong>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>