<?php

require_once "includes/auth.php";
requireLogin();

require_once "config/database.php";

$pageTitle = "Tickets";
$assetPath = "assets";
$role = $_SESSION["role"];
$rolePath = strtolower($role);
$sidebarDashboardPath = $rolePath . "/dashboard.php";
$sidebarTicketsPath = "tickets.php";
$activeNav = "tickets";
$statusMessage = "";
$statusError = "";
$conditions = [];
$parameters = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && $role === "Technician") {
    $ticketId = (int) ($_POST["ticket_id"] ?? 0);
    $newStatus = $_POST["status"] ?? "";
    $allowedStatuses = ["Open", "In Progress", "Pending", "Resolved", "Closed"];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $statusError = "That ticket status is not valid.";
    } else {
        $currentStmt = $pdo->prepare("SELECT status FROM tickets WHERE id = :ticket_id AND assigned_to = :tech_id LIMIT 1");
        $currentStmt->execute(["ticket_id" => $ticketId, "tech_id" => $_SESSION["user_id"]]);
        $oldStatus = $currentStmt->fetchColumn();
        $updateStmt = $pdo->prepare("UPDATE tickets SET status = :status, resolved_at = CASE WHEN :status = 'Resolved' THEN CURRENT_TIMESTAMP ELSE NULL END WHERE id = :ticket_id AND assigned_to = :tech_id");
        $updateStmt->execute(["status" => $newStatus, "ticket_id" => $ticketId, "tech_id" => $_SESSION["user_id"]]);
        if ($updateStmt->rowCount() > 0) {
            if ($oldStatus !== $newStatus) {
                $historyStmt = $pdo->prepare("INSERT INTO ticket_updates (ticket_id, user_id, action, old_status, new_status) VALUES (:ticket_id, :user_id, 'Status updated', :old_status, :new_status)");
                $historyStmt->execute(["ticket_id" => $ticketId, "user_id" => $_SESSION["user_id"], "old_status" => $oldStatus, "new_status" => $newStatus]);
            }
            $statusMessage = "Ticket status updated successfully.";
        } else {
            $statusError = "The ticket could not be updated or is not assigned to you.";
        }
    }
}

if ($role === "Employee") {
    $conditions[] = "t.created_by = :user_id";
    $parameters["user_id"] = $_SESSION["user_id"];
} elseif ($role === "Technician") {
    $conditions[] = "t.assigned_to = :user_id";
    $parameters["user_id"] = $_SESSION["user_id"];
}

$whereClause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);

$stmt = $pdo->prepare(" 
    SELECT t.id, t.ticket_number, t.subject, t.description, t.priority, t.status, t.created_at,
           c.name AS category_name,
           CONCAT(u.first_name, ' ', u.last_name) AS requester_name
    FROM tickets t
    INNER JOIN categories c ON c.id = t.category_id
    INNER JOIN users u ON u.id = t.created_by
    {$whereClause}
    ORDER BY t.created_at DESC
");
$stmt->execute($parameters);
$tickets = $stmt->fetchAll();

require_once "includes/header.php";
require_once "includes/sidebar.php";

function statusClass(string $status): string
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
    <header class="topbar">
        <div class="topbar-title">
            <h1>Tickets</h1>
            <p><?php echo $role === "Admin" ? "All support requests" : "Your support requests"; ?></p>
        </div>
    </header>

    <section class="dashboard-content">
        <?php if ($statusMessage !== ""): ?><div class="success-message"><?php echo htmlspecialchars($statusMessage); ?></div><?php endif; ?>
        <?php if ($statusError !== ""): ?><div class="error-message"><?php echo htmlspecialchars($statusError); ?></div><?php endif; ?>
        <div class="dashboard-card recent-tickets">
            <div class="card-header">
                <div>
                    <h3>Ticket List</h3>
                    <p><?php echo count($tickets); ?> ticket<?php echo count($tickets) === 1 ? "" : "s"; ?> found</p>
                </div>
                <?php if ($role === "Employee"): ?>
                    <a href="employee/create_ticket.php" class="primary-button">Create Ticket</a>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Subject</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <?php if ($role !== "Technician"): ?><th>Status</th><?php endif; ?>
                            <th>Date &amp; Time</th>
                            <?php if ($role === "Technician"): ?><th>Change status</th><?php endif; ?>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr><td colspan="<?php echo $role === "Technician" ? "8" : "8"; ?>" style="text-align: center; padding: 24px; color: #64748b;">No tickets found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><strong><a href="ticket.php?number=<?php echo urlencode($ticket["ticket_number"]); ?>"><?php echo htmlspecialchars($ticket["ticket_number"]); ?></a></strong></td>
                                    <td><?php echo htmlspecialchars($ticket["subject"]); ?></td>
                                    <td class="ticket-description" title="<?php echo htmlspecialchars($ticket["description"]); ?>"><?php echo htmlspecialchars($ticket["description"]); ?></td>
                                    <td><?php echo htmlspecialchars($ticket["category_name"]); ?></td>
                                    <td><span class="priority <?php echo strtolower($ticket["priority"]); ?>"><?php echo htmlspecialchars($ticket["priority"]); ?></span></td>
                                    <?php if ($role !== "Technician"): ?><td><span class="status <?php echo statusClass($ticket["status"]); ?>"><?php echo htmlspecialchars($ticket["status"]); ?></span></td><?php endif; ?>
                                    <td><?php echo date("M d, Y g:i A", strtotime($ticket["created_at"])); ?></td>
                                    <?php if ($role === "Technician"): ?>
                                        <td>
                                            <form method="post" class="ticket-status-form">
                                                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket["id"]; ?>">
                                                <select name="status" aria-label="Update ticket status">
                                                    <?php foreach (["Open", "In Progress", "Pending", "Resolved", "Closed"] as $status): ?>
                                                        <option value="<?php echo $status; ?>" <?php echo $ticket["status"] === $status ? "selected" : ""; ?>><?php echo $status; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="secondary-button">Save</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                    <td><a href="ticket.php?number=<?php echo urlencode($ticket["ticket_number"]); ?>" class="ticket-details-button">View details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<?php require_once "includes/footer.php"; ?>