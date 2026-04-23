<?php

// Simple PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Provider\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use Provider\Infrastructure\Persistence\JsonPaymentRepository;
use Provider\Infrastructure\Integrations\HttpWebhookSender;
use Provider\Application\UseCases\TokenizeCardUseCase;
use Provider\Application\UseCases\ChargePaymentUseCase;
use Provider\Infrastructure\Http\Controllers\TokenController;
use Provider\Infrastructure\Http\Controllers\ChargeController;

// Simple Router and Dependency Injection
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Initialize Infrastructure
$repository = new JsonPaymentRepository(__DIR__ . '/../data/payments.json');
$webhookSender = new HttpWebhookSender();

// Initialize Use Cases
$tokenizeUseCase = new TokenizeCardUseCase();
$chargeUseCase = new ChargePaymentUseCase($repository, $webhookSender);

// Routing
if ($method === 'POST' && $uri === '/api/tokens') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    (new TokenController($tokenizeUseCase))->handle($data);
} elseif ($method === 'POST' && $uri === '/api/charges') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    (new ChargeController($chargeUseCase))->handle($data);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
