<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$flashToast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

$flashAttr = '';
if ($flashToast && !empty($flashToast['messages'])) {
    $flashAttr = ' data-flash="' . htmlspecialchars(json_encode([
        'type' => $flashToast['type'] ?? 'error',
        'messages' => $flashToast['messages'],
    ]), ENT_QUOTES, 'UTF-8') . '"';
}
?>
<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"<?= $flashAttr ?>></div>