<?php

session_start();

if (isset($_SESSION["user_id"])) {

    switch ($_SESSION["role"]) {

        case "Admin":
            header("Location: admin/dashboard.php");
            break;

        case "Technician":
            header("Location: technician/dashboard.php");
            break;

        case "Employee":
            header("Location: employee/dashboard.php");
            break;

        default:
            header("Location: login.php");
            break;
    }

} else {

    header("Location: login.php");

}

exit;