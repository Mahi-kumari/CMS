<?php
declare(strict_types=1);

require __DIR__ . "/../../config/crm.php";

$data = [
    "courses" => [],
    "locations" => [],
    "sources" => []
];

$mysqli = null;
try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

if ($res = $mysqli->query("SELECT course_name FROM courses WHERE is_active = 1 OR is_active IS NULL ORDER BY course_name")) {
    while ($row = $res->fetch_assoc()) {
        $data["courses"][] = $row["course_name"];
    }
    $res->free();
}

if ($res = $mysqli->query("SELECT location_name FROM locations WHERE is_active = 1 OR is_active IS NULL ORDER BY location_name")) {
    while ($row = $res->fetch_assoc()) {
        $data["locations"][] = $row["location_name"];
    }
    $res->free();
}

if ($res = $mysqli->query("SELECT source_name FROM sources WHERE is_active = 1 OR is_active IS NULL ORDER BY source_name")) {
    while ($row = $res->fetch_assoc()) {
        $data["sources"][] = $row["source_name"];
    }
    $res->free();
}

$mysqli->close();

header("Content-Type: application/json");
echo json_encode($data);
exit;
?>
