<?php

/**
 * get_districts.php
 * -----------------------------------------------------------------------
 * AJAX endpoint used by profile.js to populate the district dropdown
 * whenever the user changes the state dropdown.
 *
 * Request:  GET get_districts.php?state_id=12
 * Response: JSON array, e.g. [{"id": 101, "name": "Ernakulam"}, ...]
 * -----------------------------------------------------------------------
 */
session_start();

header('Content-Type: application/json');

// --- Auth guard -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$stateId = isset($_GET['state_id']) ? (int) $_GET['state_id'] : 0;

if ($stateId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = mysqli_prepare($con, 'SELECT id, name FROM districts WHERE state_id = ? ORDER BY name ASC');
mysqli_stmt_bind_param($stmt, 'i', $stateId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$districts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $districts[] = ['id' => (int) $row['id'], 'name' => $row['name']];
}
mysqli_stmt_close($stmt);

echo json_encode($districts);
