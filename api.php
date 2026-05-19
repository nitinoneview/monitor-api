<?php
// ================================
// Server Monitor - Metrics API
// Receives JSON from bash agent
// Saves to NeonDB (PostgreSQL)
// ================================
header('Content-Type: application/json');

// --- Database URL parse karke PDO DSN banao ---
$db_url = getenv('DATABASE_URL');
$p      = parse_url($db_url);
$host   = $p['host'];
$port   = $p['port'] ?? 5432;
$dbname = ltrim($p['path'], '/');
$user   = $p['user'];
$pass   = $p['pass'];
$endpoint = explode('.', $host)[0];
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options=endpoint=$endpoint";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed', 'detail' => $e->getMessage()]);
    exit;
}

// --- Sirf POST allow karo ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

// --- JSON body padho ---
$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// --- API key validate karo ---
$api_key  = $body['api_key']  ?? '';
$hostname = $body['hostname'] ?? '';

$stmt = $pdo->prepare("SELECT id FROM servers WHERE hostname = ? AND api_key = ?");
$stmt->execute([$hostname, $api_key]);
$server = $stmt->fetch();

if (!$server) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid api_key or hostname']);
    exit;
}

// --- Metrics save karo ---
$stmt = $pdo->prepare("
    INSERT INTO metrics (server_id, cpu_percent, ram_percent, disk_percent, uptime)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $server['id'],
    $body['cpu_percent']  ?? 0,
    $body['ram_percent']  ?? 0,
    $body['disk_percent'] ?? 0,
    $body['uptime']       ?? ''
]);

echo json_encode(['status' => 'ok', 'message' => 'Metrics saved']);
?>
