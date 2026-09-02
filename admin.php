<?php
require_once 'functions.php';
session_start();

$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($login === 'admin' && $password === '123456') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Неверный логин или пароль!';
    }
}

// Обработка действий (только для админа)
if ($is_logged_in) {
    // Сохранение настроек
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        $settings = load_data('settings.json');
        foreach ($_POST as $key => $value) {
            if ($key !== 'save_settings' && isset($settings[$key])) {
                $settings[$key] = trim($value);
            }
        }
        save_data('settings.json', $settings);
        $success = '✅ Настройки сохранены!';
    }
    
    // Добавление отзыва
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
        $review = [
            'name' => trim($_POST['review_name']),
            'car' => trim($_POST['review_car']),
            'text' => trim($_POST['review_text'])
        ];
        if ($review['name'] && $review['text']) {
            add_review($review);
            $success = '✅ Отзыв добавлен!';
        }
    }
    
    // Удаление отзыва
    if (isset($_GET['delete_review'])) {
        delete_review((int)$_GET['delete_review']);
        header('Location: admin.php?tab=reviews');
        exit;
    }
    
    // Удаление заявки
    if (isset($_GET['delete_order'])) {
        delete_order((int)$_GET['delete_order']);
        header('Location: admin.php?tab=orders');
        exit;
    }
    
    // Обновление статуса заявки
    if (isset($_GET['update_status']) && isset($_GET['status'])) {
        update_order_status((int)$_GET['update_status'], $_GET['status']);
        header('Location: admin.php?tab=orders');
        exit;
    }
    
    // Добавление услуги
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
        $service = [
            'icon' => trim($_POST['service_icon']),
            'title' => trim($_POST['service_title']),
            'description' => trim($_POST['service_description']),
            'price' => trim($_POST['service_price'])
        ];
        if ($service['title']) {
            add_service($service);
            $success = '✅ Услуга добавлена!';
        }
    }
    
    // Удаление услуги
    if (isset($_GET['delete_service'])) {
        delete_service((int)$_GET['delete_service']);
        header('Location: admin.php?tab=services');
        exit;
    }
    
    // Загрузка фото
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
        $filename = upload_file($_FILES['photo']);
        if ($filename) {
            $success = '✅ Фото загружено!';
        } else {
            $error = '❌ Ошибка загрузки (разрешены: jpg, png, gif, webp)';
        }
    }
    
    // Удаление фото
    if (isset($_GET['delete_photo'])) {
        delete_photo($_GET['delete_photo']);
        header('Location: admin.php?tab=gallery');
        exit;
    }
}

$tab = $_GET['tab'] ?? 'dashboard';
$settings = load_data('settings.json');
$services = get_services();
$reviews = get_reviews();
$orders = get_orders();
$gallery = get_gallery();
$new_orders_count = count(array_filter($orders, function($o) { return $o['status'] === 'new'; }));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; display:flex; min-height:100vh; }
        .sidebar { width:250px; background:#0b1a2e; color:white; min-height:100vh; padding:30px 20px; flex-shrink:0; }
        .sidebar h2 { color:#facc15; margin-bottom:30px; }
        .sidebar a { display:block; color:#94a3b8; text-decoration:none; padding:12px 15px; border-radius:10px; margin-bottom:5px; transition:0.3s; }
        .sidebar a:hover { background:#1e3347; color:white; }
        .sidebar a.active { background:#facc15; color:#0b1a2e; font-weight:700; }
        .sidebar .badge { background:#ef4444; color:white; padding:2px 10px; border-radius:20px; font-size:0.7rem; margin-left:8px; }
        .main { flex:1; padding:30px; }
        .card { background:white; border-radius:15px; padding:25px; margin-bottom:25px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-bottom:15px; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:20px; margin-bottom:30px; }
        .stat { background:white; padding:20px; border-radius:15px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .stat .num { font-size:2.5rem; font-weight:700; color:#0b1a2e; }
        .stat .label { color:#64748b; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; font-weight:600; margin-bottom:5px; }
        .form-group input, .form-group textarea, .form-group select { width:100%; padding:12px; border:2px solid #e2e8f0; border-radius:10px; font-size:1rem; font-family:inherit; }
        .form-group textarea { min-height:80px; resize:vertical; }
        .btn { background:#facc15; color:#0b1a2e; padding:12px 30px; border:none; border-radius:10px; font-weight:700; cursor:pointer; transition:0.3s; }
        .btn:hover { background:#fde047; }
        .btn-danger { background:#ef4444; color:white; }
        .btn-danger:hover { background:#dc2626; }
        .btn-sm { padding:5px 15px; font-size:0.85rem; }
        .success { background:#bbf7d0; color:#15803d; padding:15px; border-radius:10px; margin-bottom:20px; }
        .error { background:#fee2e2; color:#dc2626; padding:15px; border-radius:10px; margin-bottom:20px; }
        .table { width:100%; border-collapse:collapse; }
        .table th, .table td { padding:12px; text-align:left; border-bottom:1px solid #e2e8f0; }
        .table th { background:#f8fafc; font-weight:600; }
        .gallery-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:15px; }
        .gallery-item { background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .gallery-item img { width:100%; height:150px; object-fit:cover; }
        .gallery-item .info { padding:10px; text-align:center; }
        .login-box { max-width:400px; margin:100px auto; background:white; padding:40px; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .login-box h1 { margin-bottom:10px; }
        .login-box input { width:100%; padding:14px; border:2px solid #e2e8f0; border-radius:10px; margin-bottom:15px; font-size:1rem; }
        .login-box .btn { width:100%; text-align:center; }
        .status-badge { padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:600; }
        .status-new { background:#fef3c7; color:#92400e; }
        .status-processed { background:#dbeafe; color:#1e40af; }
        .status-done { background:#bbf7d0; color:#15803d; }
        .logout-link { color:#ef4444; margin-top:20px; display:block; text-decoration:none; }
        .logout-link:hover { color:#dc2626; }
        .flex-between { display:flex; justify-content:space-between; align-items:center; }
        @media(max-width:768px){ body { flex-direction:column; } .sidebar { width:100%; min-height:auto; } .stats { grid-template-columns:1fr 1fr; } }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <!-- ВХОД -->
    <div class="login-box">
        <h1>⚙️ Вход в админку</h1>
        <p style="color:#64748b; margin-bottom:20px;">Введите логин и пароль</p>
        <?php if (isset($login_error)): ?>
            <div style="background:#fee2e2; color:#dc2626; padding:12px; border-radius:10px; margin-bottom:15px;"><?= $login_error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" class="btn">Войти</button>
        </form>
        <p style="text-align:center; margin-top:15px; font-size:0.8rem; color:#94a3b8;">
            По умолчанию: admin / 123456
        </p>
    </div>
<?php else: ?>
    <!-- САЙДБАР -->
    <div class="sidebar">
        <h2>⚙️ Админка</h2>
        <a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">📊 Главная</a>
        <a href="?tab=settings" class="<?= $tab === 'settings' ? 'active' : '' ?>">✏️ Тексты</a>
        <a href="?tab=services" class="<?= $tab === 'services' ? 'active' : '' ?>">🔧 Услуги</a>
        <a href="?tab=reviews" class="<?= $tab === 'reviews' ? 'active' : '' ?>">💬 Отзывы</a>
        <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">📩 Заявки <?= $new_orders_count > 0 ? '<span class="badge">'.$new_orders_count.'</span>' : '' ?></a>
        <a href="?tab=gallery" class="<?= $tab === 'gallery' ? 'active' : '' ?>">🖼️ Галерея</a>
        <a href="?logout=1" class="logout-link">🚪 Выход</a>
    </div>

    <!-- КОНТЕНТ -->
    <div class="main">
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard'): ?>
            <h1 style="margin-bottom:20px;">👋 Добро пожаловать!</h1>
            <div class="stats">
                <div class="stat"><div class="num"><?= count($services) ?></div><div class="label">Услуг</div></div>
                <div class="stat"><div class="num"><?= count($reviews) ?></div><div class="label">Отзывов</div></div>
                <div class="stat"><div class="num"><?= $new_orders_count ?></div><div class="label">Новых заявок</div></div>
                <div class="stat"><div class="num"><?= count($gallery) ?></div><div class="label">Фото</div></div>
            </div>
            <div class="card">
                <h3>📌 Быстрые ссылки</h3>
                <p><a href="../index.php" target="_blank">🔗 Открыть сайт</a></p>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'settings'): ?>
            <h1 style="margin-bottom:20px;">✏️ Редактировать тексты</h1>
            <div class="card">
                <form method="POST">
                    <?php foreach ($settings as $key => $value): ?>
                        <div class="form-group">
                            <label><?= htmlspecialchars($key) ?></label>
                            <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="save_settings" class="btn">💾 Сохранить все</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'services'): ?>
            <h1 style="margin-bottom:20px;">🔧 Управление услугами</h1>
            <div class="card">
                <h3>➕ Доба
