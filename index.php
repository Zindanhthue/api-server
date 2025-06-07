<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

$host = 'fdb1028.awardspace.net';
$user = '4645096_keytool';
$pass = 'sQrerEMB#bC3eHz';
$db = '4645096_keytool';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Lỗi kết nối DB: ' . $mysqli->connect_error);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}

// Login
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($username === 'dnn' && $password === 'dnn') {
            $_SESSION['logged_in'] = true;
            redirect('index.php');
        } else {
            $login_error = "Sai tên đăng nhập hoặc mật khẩu!";
        }
    }
    // Login form
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Đăng nhập Admin</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                height: 100vh;
                background: linear-gradient(135deg, #6a11cb, #2575fc);
                display: flex;
                justify-content: center;
                align-items: center;
                font-family: 'Poppins', sans-serif;
                color: white;
            }
            .login-container {
                background: rgba(255, 255, 255, 0.12);
                padding: 40px 30px;
                border-radius: 15px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
                width: 320px;
                text-align: center;
                backdrop-filter: blur(12px);
            }
            .login-container h2 {
                margin-bottom: 25px;
                font-weight: 600;
                letter-spacing: 1.2px;
                text-shadow: 0 1px 3px rgba(0,0,0,0.6);
            }
            input[type="text"], input[type="password"] {
                width: 100%;
                padding: 12px 15px;
                margin-bottom: 20px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                background: rgba(255,255,255,0.2);
                color: white;
                box-shadow: inset 0 2px 5px rgba(0,0,0,0.3);
                transition: background-color 0.3s ease;
            }
            input[type="text"]:focus, input[type="password"]:focus {
                background: rgba(255,255,255,0.4);
                outline: none;
            }
            button {
                width: 100%;
                padding: 14px;
                font-size: 18px;
                border: none;
                border-radius: 10px;
                background: #ffc107;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 6px 12px rgba(255,193,7,0.6);
                transition: background-color 0.3s ease;
                color: #000;
                letter-spacing: 1px;
            }
            button:hover {
                background: #ffca2c;
                box-shadow: 0 8px 16px rgba(255,203,44,0.8);
            }
            .alert {
                background: #dc3545;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: 600;
                letter-spacing: 0.5px;
                text-shadow: 0 1px 2px rgba(0,0,0,0.4);
            }
            @media (max-width: 400px) {
                .login-container {
                    width: 90vw;
                    padding: 30px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container" role="main">
            <h2><i class="fa-solid fa-user-lock"></i> Đăng nhập Admin</h2>
            <?php if (!empty($login_error)) : ?>
                <div class="alert" role="alert"><?=htmlspecialchars($login_error)?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off" aria-label="Form đăng nhập">
                <input type="text" name="username" placeholder="Tên đăng nhập" required autofocus aria-required="true" aria-label="Tên đăng nhập" />
                <input type="password" name="password" placeholder="Mật khẩu" required aria-required="true" aria-label="Mật khẩu" />
                <button type="submit" name="login" aria-label="Đăng nhập">Đăng nhập</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- Đã đăng nhập --- //

// Xử lý tạo key mới
$alert_msg = '';
if (isset($_POST['create_key'])) {
    $duration = intval($_POST['duration']);
    $unit = $_POST['unit'] ?? 'minutes';

    switch ($unit) {
        case 'seconds': $seconds = $duration; break;
        case 'minutes': $seconds = $duration * 60; break;
        case 'hours': $seconds = $duration * 3600; break;
        default: $seconds = $duration * 60;
    }

    $created_at = date('Y-m-d H:i:s');
    $expires_at = date('Y-m-d H:i:s', time() + $seconds);
    $api_key = 'DNN#' . bin2hex(random_bytes(5));

    $stmt = $mysqli->prepare("INSERT INTO api_keys (api_key, created_at, expires_at, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param('sss', $api_key, $created_at, $expires_at);
    $stmt->execute();

    $alert_msg = "Tạo key thành công: <strong>$api_key</strong>";
}

// Xử lý thao tác reset, disable, delete
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'delete') {
        $mysqli->query("DELETE FROM api_keys WHERE id = $id");
    } elseif ($action === 'disable') {
        $mysqli->query("UPDATE api_keys SET status = 'disabled' WHERE id = $id");
    } elseif ($action === 'reset') {
        $new_expire = date('Y-m-d H:i:s', time() + 3600); // Reset 1 giờ
        $mysqli->query("UPDATE api_keys SET expires_at = '$new_expire', status = 'active' WHERE id = $id");
    }
    redirect('index.php');
}

// Tự động cập nhật trạng thái key hết hạn
$now = date('Y-m-d H:i:s');
$mysqli->query("UPDATE api_keys SET status = 'disabled' WHERE expires_at <= '$now' AND status = 'active'");

// Lấy danh sách key
$result = $mysqli->query("SELECT * FROM api_keys ORDER BY id DESC");
$keys = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quản lý API Key - Admin</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
  * {
    box-sizing: border-box;
  }
  body {
    margin: 0;
    background: linear-gradient(135deg, #7b2ff7, #f107a3);
    font-family: 'Poppins', sans-serif;
    color: white;
  }
  .container {
    max-width: 1100px;
    margin: auto;
    padding: 20px 15px 80px 15px;
  }
  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
  }
  header h1 {
    font-weight: 700;
    font-size: 2rem;
    letter-spacing: 2px;
    text-shadow: 0 0 10px rgba(0,0,0,0.4);
  }
  a.logout-btn {
    background: #ffc107;
    color: #222;
    text-decoration: none;
    font-weight: 700;
    padding: 12px 25px;
    border-radius: 30px;
    box-shadow: 0 6px 20px rgba(255,193,7,0.5);
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  a.logout-btn:hover {
    background: #ffd64d;
  }
  form.create-form {
    background: rgba(255,255,255,0.15);
    padding: 20px 25px;
    border-radius: 25px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
    align-items: center;
    box-shadow: 0 8px 32px rgba(255,255,255,0.15);
    margin-bottom: 25px;
  }
  form.create-form input[type="number"] {
    flex: 1 1 120px;
    padding: 15px 20px;
    font-size: 1.1rem;
    border-radius: 20px;
    border: none;
    font-weight: 600;
    background: rgba(255,255,255,0.3);
    color: #111;
    transition: background-color 0.3s ease;
    text-align: center;
  }
  form.create-form input[type="number"]:focus {
    background: rgba(255,255,255,0.5);
    outline: none;
  }
  form.create-form select {
    flex: 1 1 120px;
    padding: 14px 20px;
    font-size: 1.1rem;
    border-radius: 20px;
    border: none;
    font-weight: 600;
    background: rgba(255,255,255,0.3);
    color: #111;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  form.create-form select:hover {
    background: rgba(255,255,255,0.5);
  }
  form.create-form button {
    flex: 1 1 140px;
    padding: 15px 0;
    font-size: 1.2rem;
    border-radius: 30px;
    border: none;
    font-weight: 700;
    background: #ffc107;
    color: #222;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(255,193,7,0.7);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
  }
  form.create-form button:hover {
    background: #ffd64d;
    box-shadow: 0 10px 30px rgba(255,203,44,0.9);
  }

  .alert {
    background: #fff9c4;
    color: #222;
    padding: 15px 20px;
    border-radius: 20px;
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
    box-shadow: 0 6px 18px rgba(255,243,51,0.6);
  }

  table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    background: transparent;
  }
  thead tr {
    background: rgba(255,255,255,0.25);
    border-radius: 25px;
    box-shadow: 0 4px 16px rgba(255,255,255,0.2);
  }
  thead th {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    padding: 15px 12px;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }
  tbody tr {
    background: rgba(255,255,255,0.1);
    border-radius: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    transition: background-color 0.25s ease;
  }
  tbody tr:hover {
    background: rgba(255,255,255,0.3);
  }
  tbody td {
    padding: 14px 12px;
    text-align: center;
    font-weight: 600;
    color: #eee;
  }

  .badge-active {
    background: #28a745;
    color: white;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 30px;
    box-shadow: 0 3px 12px #28a745cc;
    text-shadow: 0 1px 1px #145214;
  }
  .badge-disabled {
    background: #dc3545;
    color: white;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 30px;
    box-shadow: 0 3px 12px #dc3545cc;
    text-shadow: 0 1px 1px #7b1a1a;
  }
  .action-btn {
    cursor: pointer;
    color: white;
    font-size: 1.25rem;
    margin: 0 8px;
    transition: color 0.3s ease;
    text-shadow: 0 1px 2px rgba(0,0,0,0.4);
  }
  .action-btn:hover {
    color: #ffc107;
  }
  .action-btn.delete:hover {
    color: #ff6f61;
  }
  .action-btn.disable:hover {
    color: #dc3545;
  }
  .action-btn.reset:hover {
    color: #28a745;
  }
  .action-btn[title] {
    position: relative;
  }
  .action-btn[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 130%;
    left: 50%;
    transform: translateX(-50%);
    background: #222;
    color: #ffc107;
    padding: 6px 10px;
    font-size: 0.85rem;
    border-radius: 6px;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.7);
    pointer-events: none;
    opacity: 1;
    transition: opacity 0.2s ease;
    z-index: 100;
  }

  /* Countdown timer style */
  .countdown {
    font-weight: 700;
    font-size: 1rem;
    color: #fff;
    background: #00000077;
    border-radius: 12px;
    padding: 5px 12px;
    box-shadow: 0 3px 10px #000000bb;
    letter-spacing: 1.5px;
    font-family: 'Courier New', Courier, monospace;
  }

  /* Footer credit */
  footer {
    text-align: center;
    padding: 15px 10px;
    font-size: 0.85rem;
    color: #ffc107dd;
    font-weight: 600;
    text-shadow: 0 0 6px #ffc107cc;
    position: fixed;
    bottom: 0;
    width: 100%;
    background: linear-gradient(135deg, #7b2ff7, #f107a3);
    user-select: none;
  }

  @media (max-width: 768px) {
    table, thead, tbody, tr, th, td {
      display: block;
    }
    thead tr {
      position: absolute;
      top: -9999px;
      left: -9999px;
    }
    tbody tr {
      margin-bottom: 15px;
      background: rgba(255,255,255,0.15);
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      padding: 15px;
    }
    tbody td {
      text-align: right;
      padding-left: 50%;
      position: relative;
      font-size: 0.9rem;
    }
    tbody td::before {
      content: attr(data-label);
      position: absolute;
      left: 15px;
      font-weight: 700;
      color: #ffc107cc;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 0.85rem;
      top: 14px;
    }
    .action-btn {
      font-size: 1.5rem;
      margin: 0 6px 6px 0;
    }
  }
</style>
</head>
<body>
<div class="container" role="main" aria-label="Trang quản lý API key">
  <header>
    <h1><i class="fa-solid fa-key"></i> Quản lý API Key</h1>
    <a href="?logout=1" class="logout-btn" aria-label="Đăng xuất"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
  </header>

  <?php if ($alert_msg): ?>
    <div class="alert" role="alert" tabindex="0"><?= $alert_msg ?></div>
  <?php endif; ?>

  <form method="post" class="create-form" aria-label="Form tạo API key mới">
    <input type="number" name="duration" min="1" max="1000000" value="60" aria-label="Thời gian" required />
    <select name="unit" aria-label="Đơn vị thời gian">
      <option value="seconds">Giây</option>
      <option value="minutes" selected>Phút</option>
      <option value="hours">Giờ</option>
    </select>
    <button type="submit" name="create_key" aria-label="Tạo API key mới"><i class="fa-solid fa-plus"></i> Tạo Key</button>
  </form>

  <table aria-describedby="table-description">
    <caption id="table-description" class="sr-only">Danh sách API key với trạng thái và thao tác quản lý</caption>
    <thead>
      <tr>
        <th>ID</th>
        <th>API Key</th>
        <th>Ngày tạo</th>
        <th>Ngày hết hạn</th>
        <th>Thời gian còn lại</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($keys as $key): ?>
      <tr>
        <td data-label="ID"><?= $key['id'] ?></td>
        <td data-label="API Key"><?= htmlspecialchars($key['api_key']) ?></td>
        <td data-label="Ngày tạo"><?= $key['created_at'] ?></td>
        <td data-label="Ngày hết hạn"><?= $key['expires_at'] ?></td>
        <td data-label="Thời gian còn lại">
          <span class="countdown" data-expire="<?= strtotime($key['expires_at']) ?>"></span>
        </td>
        <td data-label="Trạng thái">
          <?php if ($key['status'] === 'active'): ?>
            <span class="badge-active" aria-label="Hoạt động">Hoạt động</span>
          <?php else: ?>
            <span class="badge-disabled" aria-label="Vô hiệu hóa">Vô hiệu hóa</span>
          <?php endif; ?>
        </td>
        <td data-label="Thao tác" aria-live="polite">
          <a href="?action=reset&id=<?= $key['id'] ?>" class="action-btn reset" title="Reset 1 giờ" aria-label="Reset key <?= $key['api_key'] ?>"><i class="fa-solid fa-rotate"></i></a>
          <?php if ($key['status'] === 'active'): ?>
            <a href="?action=disable&id=<?= $key['id'] ?>" class="action-btn disable" title="Vô hiệu hóa" aria-label="Vô hiệu hóa key <?= $key['api_key'] ?>"><i class="fa-solid fa-ban"></i></a>
          <?php else: ?>
            <a href="?action=reset&id=<?= $key['id'] ?>" class="action-btn reset" title="Kích hoạt lại" aria-label="Kích hoạt lại key <?= $key['api_key'] ?>"><i class="fa-solid fa-check"></i></a>
          <?php endif; ?>
          <a href="?action=delete&id=<?= $key['id'] ?>" class="action-btn delete" title="Xóa" aria-label="Xóa key <?= $key['api_key'] ?>" onclick="return confirm('Bạn có chắc muốn xóa key này?');"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<footer role="contentinfo">
  &copy; Đặng Nguyên
</footer>

<script>
  // Đếm ngược thời gian còn lại
  function updateCountdowns() {
    const elements = document.querySelectorAll('.countdown');
    const now = Math.floor(Date.now() / 1000);
    elements.forEach(el => {
      const expire = parseInt(el.getAttribute('data-expire'), 10);
      let diff = expire - now;
      if (diff < 0) diff = 0;
      const h = Math.floor(diff / 3600);
      const m = Math.floor((diff % 3600) / 60);
      const s = diff % 60;
      el.textContent = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
    });
  }
  updateCountdowns();
  setInterval(updateCountdowns, 1000);
</script>
</body>
</html>
