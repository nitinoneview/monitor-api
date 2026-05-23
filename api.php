<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db_url   = getenv('DATABASE_URL');
$p        = parse_url($db_url);
$host     = $p['host'];
$port     = $p['port'] ?? 5432;
$dbname   = ltrim($p['path'], '/');
$user     = $p['user'];
$pass     = $p['pass'];
$endpoint = explode('.', $host)[0];
$dsn      = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options=endpoint=$endpoint";

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

// UptimeRobot HEAD request
if ($method === 'HEAD') {
    http_response_code(200);
    exit;
}

// UptimeRobot ping
if ($method === 'GET' && isset($_GET['ping'])) {
    echo json_encode(['status' => 'ok', 'message' => 'pong']);
    exit;
}

// ================
// GET - Dashboard ke liye
// ================
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT
            m.cpu_percent, m.ram_percent, m.disk_percent, m.uptime,
            m.iowait, m.swap_percent,
            m.load_1, m.load_5, m.load_15,
            m.processes, m.threads, m.users,
            m.recorded_at, s.hostname
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

    // --- Metrics save karo ---
    $stmt = $pdo->prepare("
        INSERT INTO metrics (
            server_id, cpu_percent, ram_percent, disk_percent, uptime,
            iowait, swap_percent,
            load_1, load_5, load_15,
            processes, threads, users
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $server['id'],
        $body['cpu_percent']  ?? 0,
        $body['ram_percent']  ?? 0,
        $body['disk_percent'] ?? 0,
        $body['uptime']       ?? '',
        $body['iowait']       ?? 0,
        $body['swap_percent'] ?? 0,
        $body['load_1']       ?? 0,
        $body['load_5']       ?? 0,
        $body['load_15']      ?? 0,
        $body['processes']    ?? 0,
        $body['threads']      ?? 0,
        $body['users']        ?? 0,
    ]);

    // 7 din se purane records delete
    $pdo->exec("DELETE FROM metrics WHERE recorded_at < NOW() - INTERVAL '7 days'");

    echo json_encode(['status' => 'ok', 'message' => 'Metrics saved']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
