<?php
$pageTitle = $pageTitle ?? "HelpSync";
$assetPath = $assetPath ?? "../assets";
$darkMode = !empty($_SESSION["dark_mode"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?> | HelpSync</title>

    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetPath); ?>/css/style.css">
</head>

<body class="<?php echo $darkMode ? "dark-mode" : ""; ?>">

<div class="app-layout">