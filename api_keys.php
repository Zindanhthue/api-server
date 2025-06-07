<?php
// api_keys.php - Endpoint trả về danh sách key API đang active

header('Content-Type: application/json');

// Kết nối database (thay bằng cấu hình DB của bạn)
$host = 'fdb1028.awardspace.net';
$user = '4645096_keytool';
$pass = 'sQrerEMB#bC3eHz';
$dbname = '4645096_keytool';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Không kết nối được database']);
    exit();
}

// Lấy thời gian hiện tại theo múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');
$now = date('Y-m-d H:i:s');

// Lấy danh sách key đang active và chưa hết hạn
$stmt = $conn->prepare("SELECT api_key, expires_at FROM api_keys WHERE status = 'active' AND expires_at > ?");
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

$keys = [];
while ($row = $result->fetch_assoc()) {
    $keys[] = [
        'api_key' => $row['api_key'],
        'expires_at' => $row['expires_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'count' => count($keys),
    'keys' => $keys
]);
