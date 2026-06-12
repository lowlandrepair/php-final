<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please sign in.'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = [];
}

header('Content-Type: application/json');

if ($action === 'get_incidents') {
    $sql = "SELECT i.*,
                   d.unit_type AS dispatch_unit,
                   d.status AS dispatch_status,
                   d.dispatched_at
            FROM incidents i
            LEFT JOIN dispatches d ON d.incident_id = i.id
                 AND d.id = (
                    SELECT id
                    FROM dispatches
                    WHERE incident_id = i.id
                    ORDER BY dispatched_at DESC
                    LIMIT 1
                 )
            ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $incidents
    ]);
    exit;
}

if ($action === 'create_incident') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $incidentType = $data['incident_type'] ?? '';
    $severity = (int)($data['severity'] ?? 0);
    $latitude = (float)($data['latitude'] ?? 0);
    $longitude = (float)($data['longitude'] ?? 0);

    if ($title === '' || $incidentType === '' || $severity < 1 || $severity > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid parameters'
        ]);
        exit;
    }

    try {
        $sql = "INSERT INTO incidents (title, description, latitude, longitude, severity, status, incident_type)
                VALUES (:title, :description, :latitude, :longitude, :severity, 'active', :incident_type)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':severity' => $severity,
            ':incident_type' => $incidentType
        ]);

        echo json_encode([
            'success' => true,
            'incident_id' => $pdo->lastInsertId(),
            'message' => 'Incident reported successfully!'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'update_incident') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden. Admin only.'
        ]);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $incidentType = $data['incident_type'] ?? '';
    $severity = (int)($data['severity'] ?? 0);
    $status = $data['status'] ?? 'active';
    $latitude = (float)($data['latitude'] ?? 0);
    $longitude = (float)($data['longitude'] ?? 0);

    try {
        $sql = "UPDATE incidents
                SET title = :title,
                    description = :description,
                    latitude = :latitude,
                    longitude = :longitude,
                    severity = :severity,
                    incident_type = :incident_type,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':description' => $description,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':severity' => $severity,
            ':incident_type' => $incidentType,
            ':status' => $status
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Incident updated successfully'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'delete_incident') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden. Admin only.'
        ]);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);

    try {
        $sql = "DELETE FROM incidents WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        echo json_encode([
            'success' => true,
            'message' => 'Incident deleted successfully'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'dispatch_incident') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden. Admin only.'
        ]);
        exit;
    }

    $incidentId = (int)($data['incident_id'] ?? 0);
    $unitType = $data['unit_type'] ?? '';
    $allowedUnits = ['police', 'fire', 'medical'];

    if (!in_array($unitType, $allowedUnits, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid unit type'
        ]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE incidents SET status = 'dispatched', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $incidentId]);

        $sql = "INSERT INTO dispatches (incident_id, unit_type, status) VALUES (:incident_id, :unit_type, 'en_route')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':incident_id' => $incidentId,
            ':unit_type' => $unitType
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'resolve_incident') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden. Admin only.'
        ]);
        exit;
    }

    $incidentId = (int)($data['incident_id'] ?? 0);

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE incidents SET status = 'resolved', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $incidentId]);

        $sql = "UPDATE dispatches
                SET status = 'completed', arrived_at = COALESCE(arrived_at, CURRENT_TIMESTAMP)
                WHERE incident_id = :incident_id AND status != 'completed'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':incident_id' => $incidentId]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Action not found'
]);
?>
