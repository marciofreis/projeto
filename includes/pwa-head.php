<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php
$theme = function_exists('themePalette') ? themePalette() : ['primary' => '#1f5e50'];
$iconColor = function_exists('iconColorParam') ? iconColorParam() : '1f5e50';
?>
<meta name="theme-color" content="<?= e($theme['primary']) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="<?= e($clinicName ?? 'Podocare') ?>">
<meta name="application-name" content="<?= e($clinicName ?? 'Podocare') ?>">
<link rel="manifest" href="manifest.php">
<link rel="apple-touch-icon" href="icon.php?s=180&c=<?= e($iconColor) ?>">
<link rel="icon" type="image/png" sizes="192x192" href="icon.php?s=192&c=<?= e($iconColor) ?>">
<?= function_exists('themeStyleTag') ? themeStyleTag() : '' ?>
