<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối MySQL
$host = "fdb1028.awardspace.net"; // thay bằng host của bạn
$user = "u4645096_keytool";       // thay bằng user của bạn
$pass = "mật_khẩu_mysql";         // thay bằng mật khẩu database
$db   = "u4645096_keytool";       // tên database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Đăng nhập
if (isset($_POST['username'], $_POST['password'])) {
    if ($_POST['username'] === 'dnn' && $_POST['password'] === 'dnn') {
        $_SESSION['admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Xử lý tạo key
if (isset($_POST['create_key']) && $_SESSION['admin']) {
    $duration = intval($_POST['duration']);
    $unit = $_POST['unit'];
    $now = new DateTime();
    $created_at = $now->format('Y-m-d H:i:s');

    switch ($unit) {
        case 'second': $now->modify("+$duration seconds"); break;
        case 'minute': $now->modify("+$duration minutes"); break;
        case 'hour':   $now->modify("+$duration hours");   break;
    }

    $expires_at = $now->format('Y-m-d H:i:s');
    $api_key = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("INSERT INTO api_keys (api_key, created_at, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $api_key, $created_at, $expires_at);
    $stmt->execute();
}

// Thao tác key
if ($_SESSION['admin'] && isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'delete') {
        $conn->query("DELETE FROM api_keys WHERE id=$id");
    } elseif ($_GET['action'] === 'disable') {
        $conn->query("UPDATE api_keys SET status='disabled' WHERE id=$id");
    } elseif ($_GET['action'] === 'reset') {
        $conn->query("UPDATE api_keys SET status='active' WHERE id=$id");
    }
}

// Cập nhật trạng thái
$conn->query("UPDATE api_keys SET status='expired' WHERE expires_at < NOW() AND status='active'");

function countdown($to) {
    $now = new DateTime();
    $end = new DateTime($to);
    $interval = $now->diff($end);
    return $interval->format('%a ngày %h:%i:%s');
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Key Tool - DNN</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 20px; }
        .box { max-width: 700px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        input, select { padding: 5px; margin: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }
        th { background: #eee; }
        .btn { padding: 5px 10px; }
    </style>
</head>
<body>
<div class="box">
    <?php if (!isset($_SESSION['admin'])): ?>
        <h2>🔒 Đăng nhập</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input name="username" placeholder="Tài khoản" required><br>
            <input name="password" type="password" placeholder="Mật khẩu" required><br>
            <button class="btn">Đăng nhập</button>
        </form>
    <?php else: ?>
        <h2>🔧 Quản lý Key API <a href="?logout" style="float:right;">Đăng xuất</a></h2>
        <form method="post">
            <label>Tạo key hết hạn sau:</label>
            <input type="number" name="duration" value="1" min="1" required>
            <select name="unit">
                <option value="second">Giây</option>
                <option value="minute">Phút</option>
                <option value="hour">Giờ</option>
            </select>
            <button name="create_key" class="btn">Tạo Key</button>
        </form>
        <table>
            <tr>
                <th>API Key</th>
                <th>Ngày tạo</th>
                <th>Hết hạn</th>
                <th>Còn lại</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM api_keys ORDER BY id DESC");
            while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $row['api_key'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td><?= $row['expires_at'] ?></td>
                    <td><?= ($row['status'] === 'active') ? countdown($row['expires_at']) : '-' ?></td>
                    <td><?= $row['status'] ?></td>
                    <td>
                        <a href="?action=reset&id=<?= $row['id'] ?>">Reset</a> |
                        <a href="?action=disable&id=<?= $row['id'] ?>">Disable</a> |
                        <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Xoá key này?')">Xoá</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
