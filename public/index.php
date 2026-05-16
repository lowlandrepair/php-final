<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

$route = $_GET['route'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'];

startSession();

function handleApiRequest(string $route, string $method): void
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
    $authController = new AuthController();

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
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
    exit;
}

function handlePageRequest(string $route): void
{
    $middleware = new AuthMiddleware();

    if (!AuthMiddleware::isPublicRoute($route)) {
        $middleware->execute();
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
        case 'dashboard':
            AuthMiddleware::requireAdmin();
            echo '<h1>Admin Dashboard</h1><p>Coming in Phase 3</p>';
            break;
        case 'map':
            AuthMiddleware::requireLogin();
            echo '<h1>Live Map</h1><p>Coming in Phase 4</p>';
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

if (in_array($route, ['login', 'register']) && $method === 'POST') {
    handleApiRequest($route, $method);
} else {
    handlePageRequest($route);
}


