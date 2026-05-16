<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Real-Time Crime Map Simulator - Live incident tracking and dispatch system">
    <title><?php echo htmlspecialchars(APP_NAME); ?></title>

    <link rel="stylesheet" href="/php-final/public/assets/css/main.css">
    <link rel="stylesheet" href="/php-final/public/assets/css/auth.css">

    <?php if (isset($pageTitle)): ?>
        <meta name="pagename" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <?php endif; ?>
</head>
<body>
    <?php $flashMessage = hasFlashMessage() ? getFlashMessage() : null; ?>
    <?php if ($flashMessage): ?>
        <div id="flashMessage"
             data-message="<?php echo htmlspecialchars($flashMessage['message']); ?>"
             data-type="<?php echo htmlspecialchars($flashMessage['type']); ?>"
             class="sr-only">
        </div>
    <?php endif; ?>


