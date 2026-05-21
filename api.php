<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db_url = getenv('DATABASE_URL');
$p      = parse_url($db_url);
$host   = $p['host'];
$port   = $p['port'] ?? 5432;
$dbname = ltrim($p['path'], '/');
$user   = $p['user'];
$pass   = $p['pass'];
$endpoint = explode('.', $host)[0];
$dsn    = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options=endpoint=$endpoint";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed', 'detail' => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// UptimeRobot ping ke liye
if ($method === 'GET' && isset($_GET['ping'])) {
    echo json_encode(['status' => 'ok', 'message' => 'pong']);
    exit;
}

// ================
// GET - Dashboard ke liye latest metrics
// ================
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT m.cpu_percent, m.ram_percent, m.disk_percent, m.uptime, m.recorded_at, s.hostname
        FROM metrics m
        JOIN servers s ON s.id = m.server_id
        ORDER BY m.recorded_at DESC
        LIMIT 20
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

// ================
// POST - Agent se data receive karo
// ================
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!$body) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

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
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
