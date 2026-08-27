<?php

require_once "includes/auth.php";
requireLogin();
require_once "config/database.php";

$number = trim($_GET["number"] ?? "");
$role = $_SESSION["role"];
$stmt = $pdo->prepare("SELECT t.*, c.name AS category_name, CONCAT(requester.first_name, ' ', requester.last_name) AS requester_name, requester.email AS requester_email, CONCAT(technician.first_name, ' ', technician.last_name) AS technician_name FROM tickets t INNER JOIN categories c ON c.id = t.category_id INNER JOIN users requester ON requester.id = t.created_by LEFT JOIN users technician ON technician.id = t.assigned_to WHERE t.ticket_number = :number LIMIT 1");
$stmt->execute(["number" => $number]);
$ticket = $stmt->fetch();

if (!$ticket || ($role === "Employee" && (int) $ticket["created_by"] !== (int) $_SESSION["user_id"]) || ($role === "Technician" && (int) $ticket["assigned_to"] !== (int) $_SESSION["user_id"])) {
    http_response_code(404);
    exit("Ticket not found.");
}

$historyStmt = $pdo->prepare("SELECT tu.action, tu.old_status, tu.new_status, tu.created_at, CONCAT(u.first_name, ' ', u.last_name) AS user_name FROM ticket_updates tu INNER JOIN users u ON u.id = tu.user_id WHERE tu.ticket_id = :ticket_id ORDER BY tu.created_at DESC");
$historyStmt->execute(["ticket_id" => $ticket["id"]]);
$history = $historyStmt->fetchAll();

$pageTitle = $ticket["ticket_number"];
$assetPath = "assets";
$rolePath = strtolower($role);
$sidebarDashboardPath = $rolePath . "/dashboard.php";
$sidebarTicketsPath = "tickets.php";
$sidebarProfilePath = "profile.php";
$sidebarSettingsPath = "includes/coming_soon.php?section=Settings";
$sidebarLogoutPath = "logout.php";
$activeNav = "tickets";
require_once "includes/header.php";
require_once "includes/sidebar.php";

function ticketStatusClass(string $status): string
{
    return match ($status) {
        "Open" => "open-status",
        "In Progress" => "progress-status",
        "Resolved" => "resolved-status",
        "Pending" => "pending-status",
        default => "closed-status"
    };
}

?>

<main class="main-content">
    <header class="topbar"><div class="topbar-title"><h1><?php echo htmlspecialchars($ticket["ticket_number"]); ?></h1><p>Ticket details and activity</p></div></header>
    <section class="dashboard-content">
        <div class="page-header"><div><h2><?php echo htmlspecialchars($ticket["subject"]); ?></h2><p>Submitted by <?php echo htmlspecialchars($ticket["requester_name"]); ?></p></div><a href="tickets.php" class="secondary-button">Back to Tickets</a></div>
        <div class="ticket-detail-grid">
            <div class="dashboard-card ticket-detail-card">
                <div class="card-header"><div><h3>Request Details</h3><p>Information provided by the employee</p></div><span class="status <?php echo ticketStatusClass($ticket["status"]); ?>"><?php echo htmlspecialchars($ticket["status"]); ?></span></div>
                <div class="ticket-detail-body"><div class="ticket-meta-grid"><div><small>Category</small><strong><?php echo htmlspecialchars($ticket["category_name"]); ?></strong></div><div><small>Priority</small><strong><?php echo htmlspecialchars($ticket["priority"]); ?></strong></div><div><small>Requester email</small><strong><?php echo htmlspecialchars($ticket["requester_email"]); ?></strong></div><div><small>Assigned technician</small><strong><?php echo htmlspecialchars($ticket["technician_name"] ?? "Unassigned"); ?></strong></div><div><small>Created</small><strong><?php echo date("M d, Y g:i A", strtotime($ticket["created_at"])); ?></strong></div></div><div class="ticket-description-block"><small>Description</small><p><?php echo nl2br(htmlspecialchars($ticket["description"])); ?></p></div></div>
            </div>
            <div class="dashboard-card ticket-detail-card"><div class="card-header"><div><h3>Activity History</h3><p>Recent updates on this ticket</p></div></div><div class="activity-list"><?php if (empty($history)): ?><p class="empty-state">No activity recorded yet.</p><?php else: ?><?php foreach ($history as $event): ?><div class="activity-item"><span class="activity-dot"></span><div><strong><?php echo htmlspecialchars($event["action"]); ?></strong><p><?php echo htmlspecialchars($event["user_name"]); ?><?php if ($event["old_status"] && $event["new_status"]): ?> changed status from <?php echo htmlspecialchars($event["old_status"]); ?> to <?php echo htmlspecialchars($event["new_status"]); ?><?php endif; ?></p><small><?php echo date("M d, Y g:i A", strtotime($event["created_at"])); ?></small></div></div><?php endforeach; ?><?php endif; ?></div></div>
        </div>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>