<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/App/AuthController.php';
require_once __DIR__ . '/../src/App/AuthMiddleware.php';
require_once __DIR__ . '/../src/App/IncidentController.php';

$route = $_GET['route'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'];

startSession();

function handleApiRequest(string $route, string $method): void
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
    $authController = new AuthController();
    $incidentController = new IncidentController();

    if (!in_array($route, ['login', 'register'], true)) {
        if (!isLoggedIn()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please sign in.']);
            exit;
        }
    }

    switch ($route) {
        case 'login':
            if ($method === 'POST') {
                $result = $authController->login($data);
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
        case 'register':
            if ($method === 'POST') {
                $result = $authController->register($data);
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
        case 'api/incidents':
            if ($method === 'GET') {
                $result = $incidentController->getIncidents();
            } else if ($method === 'POST') {
                $result = $incidentController->createIncident($data);
            } else {
                http_response_code(405);
                $result = ['success' => false, 'message' => 'Method not allowed'];
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
        case 'api/incidents/dispatch':
            if ($method === 'POST') {
                $result = $incidentController->dispatchIncident($data);
            } else {
                http_response_code(405);
                $result = ['success' => false, 'message' => 'Method not allowed'];
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
        case 'api/incidents/resolve':
            if ($method === 'POST') {
                $result = $incidentController->resolveIncident($data);
            } else {
                http_response_code(405);
                $result = ['success' => false, 'message' => 'Method not allowed'];
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
        /* ---- Admin-only CRUD endpoints ---- */
        default:
            // Handle /api/incidents/{id} patterns
            if (preg_match('#^api/incidents/([0-9]+)$#', $route, $m)) {
                // Admin guard
                if (getCurrentUserRole() !== 'admin') {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Forbidden. Admin only.']);
                    exit;
                }
                $id = (int)$m[1];
                if ($method === 'GET') {
                    $result = $incidentController->getIncident($id);
                } elseif ($method === 'PUT') {
                    $result = $incidentController->updateIncident($id, $data);
                } elseif ($method === 'DELETE') {
                    $result = $incidentController->deleteIncident($id);
                } else {
                    http_response_code(405);
                    $result = ['success' => false, 'message' => 'Method not allowed'];
                }
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
    exit;
}

function handlePageRequest(string $route): void
{
    if (!AuthMiddleware::isPublicRoute($route)) {
        AuthMiddleware::requireLogin();
    }

    switch ($route) {
        case 'login':
            if (isLoggedIn()) {
                $userRole = getCurrentUserRole();
                $redirect = $userRole === 'admin' ? 'dashboard' : 'map';
                header('Location: /php-final/public/index.php?route=' . $redirect);
                exit;
            }
            require_once __DIR__ . '/../views/auth/login.php';
            break;
        case 'register':
            if (isLoggedIn()) {
                header('Location: /php-final/public/index.php?route=dashboard');
                exit;
            }
            require_once __DIR__ . '/../views/auth/register.php';
            break;
        case 'forgot-password':
            if (isLoggedIn()) {
                header('Location: /php-final/public/index.php?route=dashboard');
                exit;
            }
            require_once __DIR__ . '/../views/auth/forgot-password.php';
            break;
        case 'dashboard':
            AuthMiddleware::requireAdmin();
            require_once __DIR__ . '/../views/admin/dashboard.php';
            break;
        case 'map':
            AuthMiddleware::requireLogin();
            require_once __DIR__ . '/../views/map.php';
            break;
        case 'logout':
            $authController = new AuthController();
            $result = $authController->logout();
            setFlashMessage($result['message'], 'success');
            header('Location: /php-final/public/index.php?route=login');
            exit;
        default:
            header('Location: /php-final/public/index.php?route=login');
            exit;
    }
}

if (str_starts_with($route, 'api/') || (in_array($route, ['login', 'register'], true) && $method === 'POST')) {
    handleApiRequest($route, $method);
} else {
    handlePageRequest($route);
}



