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
            'text' => trim($_POST['review_text']),
            'rating' => (int)($_POST['review_rating'] ?? 5),
            'status' => 'published',
            'admin_response' => trim($_POST['admin_response'] ?? '')
        ];
        if ($review['name'] && $review['text']) {
            add_review($review);
            $success = '✅ Отзыв добавлен!';
        }
    }
    
    // Редактирование отзыва
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review'])) {
        $id = (int)$_POST['edit_review_id'];
        $data = [
            'name' => trim($_POST['edit_review_name']),
            'car' => trim($_POST['edit_review_car']),
            'text' => trim($_POST['edit_review_text']),
            'rating' => (int)($_POST['edit_review_rating'] ?? 5),
            'status' => $_POST['edit_review_status'] ?? 'pending',
            'admin_response' => trim($_POST['edit_admin_response'] ?? '')
        ];
        if ($data['name'] && $data['text'] && $id) {
            update_review($id, $data);
            $success = '✅ Отзыв обновлен!';
        }
    }
    
    // Удаление отзыва
    if (isset($_GET['delete_review'])) {
        delete_review((int)$_GET['delete_review']);
        header('Location: admin.php?tab=reviews');
        exit;
    }
    
    // Обновление статуса отзыва через AJAX
    if (isset($_POST['ajax_review_status'])) {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] ?? '';
        if ($id && $status) {
            update_review_status($id, $status);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    // Удаление заявки
    if (isset($_GET['delete_order'])) {
        delete_order((int)$_GET['delete_order']);
        header('Location: admin.php?tab=orders');
        exit;
    }
    
    // Обновление статуса заявки (обычный GET)
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
            'price' => trim($_POST['service_price']),
            'category' => trim($_POST['service_category'] ?? 'all'),
            'sort_order' => count(get_services()) + 1
        ];
        if ($service['title']) {
            add_service($service);
            $success = '✅ Услуга добавлена!';
        }
    }
    
    // Редактирование услуги
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
        $id = (int)$_POST['edit_service_id'];
        $data = [
            'icon' => trim($_POST['edit_service_icon']),
            'title' => trim($_POST['edit_service_title']),
            'description' => trim($_POST['edit_service_description']),
            'price' => trim($_POST['edit_service_price']),
            'category' => trim($_POST['edit_service_category'] ?? 'all')
        ];
        if ($data['title'] && $id) {
            update_service($id, $data);
            $success = '✅ Услуга обновлена!';
        }
    }
    
    // Удаление услуги
    if (isset($_GET['delete_service'])) {
        delete_service((int)$_GET['delete_service']);
        header('Location: admin.php?tab=services');
        exit;
    }
    
    // Изменение порядка услуги (вверх/вниз)
    if (isset($_GET['move_service']) && isset($_GET['direction'])) {
        $id = (int)$_GET['move_service'];
        $direction = $_GET['direction'];
        $services = get_services();
        $index = array_search($id, array_column($services, 'id'));
        if ($index !== false) {
            if ($direction === 'up' && $index > 0) {
                $temp = $services[$index];
                $services[$index] = $services[$index - 1];
                $services[$index - 1] = $temp;
            } elseif ($direction === 'down' && $index < count($services) - 1) {
                $temp = $services[$index];
                $services[$index] = $services[$index + 1];
                $services[$index + 1] = $temp;
            }
            foreach ($services as $i => &$s) {
                $s['sort_order'] = $i + 1;
            }
            save_data('services.json', $services);
        }
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

// Для редактирования отзыва
$edit_review_id = isset($_GET['edit_review']) ? (int)$_GET['edit_review'] : 0;
$edit_review_data = null;
if ($edit_review_id) {
    foreach ($reviews as $r) {
        if ($r['id'] === $edit_review_id) {
            $edit_review_data = $r;
            break;
        }
    }
}

// Фильтрация отзывов
$review_filter = $_GET['review_filter'] ?? 'all';
$filtered_reviews = $reviews;
if ($review_filter !== 'all') {
    $filtered_reviews = array_filter($filtered_reviews, function($r) use ($review_filter) {
        return ($r['status'] ?? 'published') === $review_filter;
    });
}
// Сортировка: сначала новые
usort($filtered_reviews, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// Статистика отзывов
$total_reviews = count($reviews);
$published_count = count(array_filter($reviews, function($r) { return ($r['status'] ?? 'published') === 'published'; }));
$pending_count = count(array_filter($reviews, function($r) { return ($r['status'] ?? 'published') === 'pending'; }));
$rejected_count = count(array_filter($reviews, function($r) { return ($r['status'] ?? 'published') === 'rejected'; }));

// Средний рейтинг
$avg_rating = 0;
if ($total_reviews > 0) {
    $sum_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum_rating / $total_reviews, 1);
}

// Для редактирования услуги
$edit_service_id = isset($_GET['edit_service']) ? (int)$_GET['edit_service'] : 0;
$edit_service_data = null;
if ($edit_service_id) {
    foreach ($services as $s) {
        if ($s['id'] === $edit_service_id) {
            $edit_service_data = $s;
            break;
        }
    }
}

// Поиск по услугам
$search_service = $_GET['search_service'] ?? '';
$filtered_services = $services;
if ($search_service) {
    $search_lower = mb_strtolower($search_service);
    $filtered_services = array_filter($filtered_services, function($s) use ($search_lower) {
        return mb_strpos(mb_strtolower($s['title']), $search_lower) !== false ||
               mb_strpos(mb_strtolower($s['description']), $search_lower) !== false;
    });
}

// Сортировка по полю sort_order
usort($filtered_services, function($a, $b) {
    return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
});

// Категории для фильтра
$categories = ['all' => 'Все', 'repair' => '🔧 Ремонт', 'diagnostic' => '🖥️ Диагностика', 'replacement' => '⛽ Замена', 'tires' => '🛞 Шиномонтаж'];
$category_filter = $_GET['category'] ?? 'all';
if ($category_filter !== 'all') {
    $filtered_services = array_filter($filtered_services, function($s) use ($category_filter) {
        return ($s['category'] ?? 'all') === $category_filter;
    });
}

// Статистика услуг
$total_services = count($services);
$categories_count = [];
foreach ($categories as $key => $label) {
    if ($key === 'all') continue;
    $categories_count[$key] = count(array_filter($services, function($s) use ($key) {
        return ($s['category'] ?? 'all') === $key;
    }));
}
$max_price = 0;
$max_price_service = '';
foreach ($services as $s) {
    $price_num = (int)preg_replace('/[^0-9]/', '', $s['price']);
    if ($price_num > $max_price) {
        $max_price = $price_num;
        $max_price_service = $s['title'];
    }
}

// Экспорт услуг в CSV
if (isset($_GET['export_services']) && $_GET['export_services'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=uslugi_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Иконка', 'Название', 'Описание', 'Цена', 'Категория']);
    foreach ($filtered_services as $s) {
        fputcsv($output, [
            $s['id'],
            $s['icon'],
            $s['title'],
            $s['description'],
            $s['price'],
            $categories[$s['category'] ?? 'all'] ?? 'Без категории'
        ]);
    }
    fclose($output);
    exit;
}

// Экспорт отзывов в CSV
if (isset($_GET['export_reviews']) && $_GET['export_reviews'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=otzyvy_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Имя', 'Автомобиль', 'Рейтинг', 'Отзыв', 'Статус', 'Дата']);
    foreach ($filtered_reviews as $r) {
        fputcsv($output, [
            $r['id'],
            $r['name'],
            $r['car'] ?? '',
            $r['rating'] ?? 5,
            $r['text'],
            $r['status'] ?? 'published',
            $r['created_at'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}
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
        .btn-success { background:#22c55e; color:white; }
        .btn-success:hover { background:#16a34a; }
        .btn-primary { background:#3b82f6; color:white; }
        .btn-primary:hover { background:#2563eb; }
        .success { background:#bbf7d0; color:#15803d; padding:15px; border-radius:10px; margin-bottom:20px; }
        .error { background:#fee2e2; color:#dc2626; padding:15px; border-radius:10px; margin-bottom:20px; }
        .table { width:100%; border-collapse:collapse; }
        .table th, .table td { padding:12px; text-align:left; border-bottom:1px solid #e2e8f0; }
        .table th { background:#f8fafc; font-weight:600; }
        .table .service-icon { font-size:1.8rem; }
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
        .status-published { background:#bbf7d0; color:#15803d; }
        .status-pending { background:#fef3c7; color:#92400e; }
        .status-rejected { background:#fee2e2; color:#dc2626; }
        .logout-link { color:#ef4444; margin-top:20px; display:block; text-decoration:none; }
        .logout-link:hover { color:#dc2626; }
        .flex-between { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
        
        /* Стили для вкладки Заявки */
        .stats-orders { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:25px; }
        .stat-order { background:white; padding:15px 20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); text-align:center; }
        .stat-order .num { font-size:2rem; font-weight:700; }
        .stat-order .label { color:#64748b; font-size:0.9rem; }
        .stat-order .num.new { color:#92400e; }
        .stat-order .num.processed { color:#1e40af; }
        .stat-order .num.done { color:#15803d; }
        
        .filters { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .filters a { padding:8px 20px; border-radius:20px; text-decoration:none; font-size:0.9rem; transition:0.3s; background:#f1f5f9; color:#64748b; }
        .filters a:hover { background:#e2e8f0; }
        .filters a.active { background:#0b1a2e; color:white; }
        .filters a.active-new { background:#fef3c7; color:#92400e; }
        .filters a.active-processed { background:#dbeafe; color:#1e40af; }
        .filters a.active-done { background:#bbf7d0; color:#15803d; }
        .filters a.active-category { background:#0b1a2e; color:white; }
        .filters a.active-published { background:#bbf7d0; color:#15803d; }
        .filters a.active-pending { background:#fef3c7; color:#92400e; }
        .filters a.active-rejected { background:#fee2e2; color:#dc2626; }
        
        .order-row-new { background:#fef3c7; }
        .order-row-processed { background:#dbeafe; }
        .order-row-done { background:#f0fdf4; }
        
        .order-card-mobile { background:white; padding:15px; border-radius:12px; margin-bottom:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-left:4px solid #e2e8f0; }
        .order-card-mobile.new { border-left-color:#f59e0b; }
        .order-card-mobile.processed { border-left-color:#3b82f6; }
        .order-card-mobile.done { border-left-color:#22c55e; }
        .order-card-mobile .row { display:flex; justify-content:space-between; padding:5px 0; }
        .order-card-mobile .actions { margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; }
        
        .status-select { padding:4px 8px; border-radius:6px; border:1px solid #e2e8f0; font-size:0.85rem; }
        
        .export-buttons { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .export-buttons .btn-sm { background:#f1f5f9; color:#0b1a2e; padding:8px 20px; border-radius:20px; text-decoration:none; font-size:0.9rem; border:none; cursor:pointer; transition:0.3s; }
        .export-buttons .btn-sm:hover { background:#e2e8f0; }
        .export-buttons .btn-sm.export-csv { background:#22c55e; color:white; }
        .export-buttons .btn-sm.export-csv:hover { background:#16a34a; }
        .export-buttons .btn-sm.print { background:#3b82f6; color:white; }
        .export-buttons .btn-sm.print:hover { background:#2563eb; }
        
        @keyframes pulse-new {
            0%, 100% { opacity:1; }
            50% { opacity:0.6; }
        }
        .new-order-blink { animation:pulse-new 1.5s infinite; font-weight:700; }
        
        /* Стили для карточек отзывов */
        .review-card-admin { background:white; border-radius:15px; padding:20px; margin-bottom:15px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-left:4px solid #22c55e; }
        .review-card-admin.pending { border-left-color:#f59e0b; }
        .review-card-admin.rejected { border-left-color:#ef4444; opacity:0.7; }
        .review-card-admin .review-header { display:flex; align-items:center; gap:15px; flex-wrap:wrap; }
        .review-card-admin .review-avatar { width:50px; height:50px; border-radius:50%; background:#facc15; display:flex; align-items:center; justify-content:center; font-size:1.8rem; flex-shrink:0; }
        .review-card-admin .review-name { font-weight:700; font-size:1.1rem; }
        .review-card-admin .review-car { color:#64748b; font-size:0.9rem; }
        .review-card-admin .review-rating { color:#f59e0b; font-size:1.2rem; margin-left:auto; }
        .review-card-admin .review-text { margin:15px 0; color:#1e293b; padding:10px 0; border-top:1px solid #e2e8f0; }
        .review-card-admin .review-date { color:#94a3b8; font-size:0.8rem; }
        .review-card-admin .admin-response { background:#f1f5f9; padding:12px 15px; border-radius:10px; margin-top:10px; border-left:3px solid #3b82f6; }
        .review-card-admin .review-actions { display:flex; gap:10px; margin-top:10px; flex-wrap:wrap; }
        .review-card-admin .review-actions a { text-decoration:none; }
        
        /* Стили для карточек услуг на мобильных */
        .service-card-mobile { background:white; padding:15px; border-radius:12px; margin-bottom:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-left:4px solid #facc15; }
        .service-card-mobile .row { display:flex; justify-content:space-between; padding:5px 0; }
        .service-card-mobile .actions { margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; }
        .service-card-mobile .icon-big { font-size:2.5rem; }
        
        .service-category-badge { display:inline-block; padding:2px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .category-repair { background:#fef3c7; color:#92400e; }
        .category-diagnostic { background:#dbeafe; color:#1e40af; }
        .category-replacement { background:#bbf7d0; color:#15803d; }
        .category-tires { background:#fce7f3; color:#9d174d; }
        .category-all { background:#e2e8f0; color:#475569; }
        
        @media(max-width:768px){
            body { flex-direction:column; }
            .sidebar { width:100%; min-height:auto; }
            .stats { grid-template-columns:1fr 1fr; }
            .stats-orders { grid-template-columns:1fr 1fr; }
            .filters { gap:5px; }
            .filters a { padding:5px 12px; font-size:0.8rem; }
            .export-buttons { gap:5px; }
            .review-card-admin .review-header { flex-wrap:wrap; }
            .review-card-admin .review-rating { margin-left:0; }
        }
        @media print {
            .sidebar, .filters, .export-buttons, .no-print { display:none !important; }
            .main { padding:0 !important; }
            .order-row-new, .order-row-processed, .order-row-done { background:none !important; }
        }
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
                <div class="stat"><div class="num"><?= $total_reviews ?></div><div class="label">Отзывов</div></div>
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
                            <?php 
                            $long_fields = ['about_text', 'hero_subtitle', 'address'];
                            if (in_array($key, $long_fields) || strlen($value) > 60): 
                            ?>
                                <textarea name="<?= $key ?>" rows="3" style="width:100%; padding:12px; border:2px solid #e2e8f0; border-radius:10px; font-size:1rem; font-family:inherit; resize:vertical; min-height:80px;"><?= htmlspecialchars($value) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>" style="width:100%; padding:12px; border:2px solid #e2e8f0; border-radius:10px; font-size:1rem;">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="save_settings" class="btn">💾 Сохранить все</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'services'): ?>
            <?php
            $all_services = get_services();
            usort($all_services, function($a, $b) {
                return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
            });
            
            $search_service = $_GET['search_service'] ?? '';
            $filtered_services = $all_services;
            if ($search_service) {
                $search_lower = mb_strtolower($search_service);
                $filtered_services = array_filter($filtered_services, function($s) use ($search_lower) {
                    return mb_strpos(mb_strtolower($s['title']), $search_lower) !== false ||
                           mb_strpos(mb_strtolower($s['description']), $search_lower) !== false;
                });
            }
            
            $category_filter = $_GET['category'] ?? 'all';
            if ($category_filter !== 'all') {
                $filtered_services = array_filter($filtered_services, function($s) use ($category_filter) {
                    return ($s['category'] ?? 'all') === $category_filter;
                });
            }
            
            $categories = [
                'all' => '📋 Все',
                'repair' => '🔧 Ремонт',
                'diagnostic' => '🖥️ Диагностика',
                'replacement' => '⛽ Замена',
                'tires' => '🛞 Шиномонтаж'
            ];
            
            $stats_categories = [];
            foreach ($categories as $key => $label) {
                if ($key === 'all') continue;
                $stats_categories[$key] = count(array_filter($all_services, function($s) use ($key) {
                    return ($s['category'] ?? 'all') === $key;
                }));
            }
            
            $max_price = 0;
            $max_price_service = '—';
            foreach ($all_services as $s) {
                $price_num = (int)preg_replace('/[^0-9]/', '', $s['price']);
                if ($price_num > $max_price) {
                    $max_price = $price_num;
                    $max_price_service = $s['title'];
                }
            }
            ?>
            
            <div class="flex-between" style="margin-bottom:20px;">
                <h1>🔧 Управление услугами</h1>
                <div style="display:flex; gap:10px;">
                    <a href="?tab=services&export_services=csv<?= $search_service ? '&search_service='.urlencode($search_service) : '' ?><?= $category_filter !== 'all' ? '&category='.$category_filter : '' ?>" class="btn btn-sm btn-success" onclick="return confirm('Экспортировать услуги в CSV?')">📥 CSV</a>
                    <button class="btn btn-sm btn-primary" onclick="window.print()">🖨️ Печать</button>
                </div>
            </div>
            
            <div class="stats" style="margin-bottom:20px;">
                <div class="stat">
                    <div class="num"><?= count($all_services) ?></div>
                    <div class="label">📊 Всего услуг</div>
                </div>
                <?php foreach ($stats_categories as $key => $count): ?>
                    <div class="stat">
                        <div class="num"><?= $count ?></div>
                        <div class="label"><?= $categories[$key] ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="stat">
                    <div class="num" style="font-size:1.2rem; color:#facc15;">💎 <?= $max_price_service ?></div>
                    <div class="label">Самая дорогая</div>
                </div>
            </div>
            
            <div class="filters">
                <?php foreach ($categories as $key => $label): ?>
                    <a href="?tab=services&category=<?= $key ?><?= $search_service ? '&search_service='.urlencode($search_service) : '' ?>" class="<?= $category_filter === $key ? 'active-category' : '' ?>">
                        <?= $label ?> (<?= $key === 'all' ? count($all_services) : ($stats_categories[$key] ?? 0) ?>)
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="export-buttons">
                <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; flex:1;">
                    <input type="hidden" name="tab" value="services">
                    <?php if ($category_filter !== 'all'): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="search_service" placeholder="🔍 Поиск по названию или описанию..." value="<?= htmlspecialchars($search_service) ?>" style="padding:8px 15px; border:2px solid #e2e8f0; border-radius:20px; flex:1; min-width:200px;">
                    <button type="submit" class="btn-sm" style="background:#0b1a2e; color:white;">🔍 Найти</button>
                    <?php if ($search_service): ?>
                        <a href="?tab=services<?= $category_filter !== 'all' ? '&category='.$category_filter : '' ?>" class="btn-sm" style="background:#ef4444; color:white; text-decoration:none;">✕ Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <h3><?= $edit_service_data ? '✏️ Редактировать услугу' : '➕ Добавить услугу' ?></h3>
                <form method="POST">
                    <?php if ($edit_service_data): ?>
                        <input type="hidden" name="edit_service_id" value="<?= $edit_service_data['id'] ?>">
                    <?php endif; ?>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Иконка (эмодзи)</label>
                            <input type="text" name="<?= $edit_service_data ? 'edit_service_icon' : 'service_icon' ?>" placeholder="🔧" value="<?= $edit_service_data ? htmlspecialchars($edit_service_data['icon']) : '' ?>" style="font-size:1.5rem; width:80px;">
                        </div>
                        <div class="form-group">
                            <label>Категория</label>
                            <select name="<?= $edit_service_data ? 'edit_service_category' : 'service_category' ?>">
                                <?php foreach ($categories as $key => $label): ?>
                                    <?php if ($key === 'all') continue; ?>
                                    <option value="<?= $key ?>" <?= $edit_service_data && ($edit_service_data['category'] ?? 'all') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Название услуги</label>
                            <input type="text" name="<?= $edit_service_data ? 'edit_service_title' : 'service_title' ?>" placeholder="Например: Ремонт двигателя" value="<?= $edit_service_data ? htmlspecialchars($edit_service_data['title']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Цена</label>
                            <input type="text" name="<?= $edit_service_data ? 'edit_service_price' : 'service_price' ?>" placeholder="от 5 000 ₽" value="<?= $edit_service_data ? htmlspecialchars($edit_service_data['price']) : '' ?>">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Описание</label>
                            <textarea name="<?= $edit_service_data ? 'edit_service_description' : 'service_description' ?>" placeholder="Краткое описание услуги..." rows="2"><?= $edit_service_data ? htmlspecialchars($edit_service_data['description']) : '' ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="<?= $edit_service_data ? 'edit_service' : 'add_service' ?>" class="btn">
                        <?= $edit_service_data ? '💾 Сохранить изменения' : '➕ Добавить услугу' ?>
                    </button>
                    <?php if ($edit_service_data): ?>
                        <a href="?tab=services" class="btn" style="background:#64748b; color:white; text-decoration:none; margin-left:10px;">✕ Отмена</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <h3>📋 Список услуг (<?= count($filtered_services) ?>)</h3>
                
                <?php if (count($filtered_services) > 0): ?>
                    <div style="overflow-x:auto;">
                        <table class="table" id="servicesTable">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th style="width:60px;">Иконка</th>
                                    <th>Название</th>
                                    <th>Категория</th>
                                    <th>Цена</th>
                                    <th style="width:120px;">Порядок</th>
                                    <th style="width:180px;">Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_services as $index => $s): ?>
                                <tr id="service-row-<?= $s['id'] ?>">
                                    <td><?= $s['id'] ?></td>
                                    <td style="font-size:1.8rem; text-align:center;"><?= $s['icon'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($s['title']) ?></strong>
                                        <br><span style="color:#64748b; font-size:0.85rem;"><?= htmlspecialchars($s['description']) ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $cat_key = $s['category'] ?? 'all';
                                        $cat_class = 'category-all';
                                        if ($cat_key === 'repair') $cat_class = 'category-repair';
                                        elseif ($cat_key === 'diagnostic') $cat_class = 'category-diagnostic';
                                        elseif ($cat_key === 'replacement') $cat_class = 'category-replacement';
                                        elseif ($cat_key === 'tires') $cat_class = 'category-tires';
                                        ?>
                                        <span class="service-category-badge <?= $cat_class ?>">
                                            <?= $categories[$cat_key] ?? 'Без категории' ?>
                                        </span>
                                    </td>
                                    <td><strong><?= $s['price'] ?></strong></td>
                                    <td>
                                        <div style="display:flex; gap:5px; align-items:center;">
                                            <?php if ($index > 0): ?>
                                                <a href="?tab=services&move_service=<?= $s['id'] ?>&direction=up" style="text-decoration:none; font-size:1.2rem;" title="Переместить вверх">⬆️</a>
                                            <?php else: ?>
                                                <span style="opacity:0.3; font-size:1.2rem;">⬆️</span>
                                            <?php endif; ?>
                                            <span style="color:#64748b; font-size:0.8rem;"><?= $index + 1 ?></span>
                                            <?php if ($index < count($filtered_services) - 1): ?>
                                                <a href="?tab=services&move_service=<?= $s['id'] ?>&direction=down" style="text-decoration:none; font-size:1.2rem;" title="Переместить вниз">⬇️</a>
                                            <?php else: ?>
                                                <span style="opacity:0.3; font-size:1.2rem;">⬇️</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                            <a href="?tab=services&edit_service=<?= $s['id'] ?>" class="btn btn-sm btn-primary" style="text-decoration:none; color:white; padding:4px 12px; border-radius:6px; font-size:0.8rem;">✏️</a>
                                            <a href="?tab=services&delete_service=<?= $s['id'] ?>" onclick="return confirm('Удалить услугу &quot;<?= htmlspecialchars($s['title']) ?>&quot;?')" style="color:#ef4444; text-decoration:none; padding:4px 8px; border:1px solid #ef4444; border-radius:6px; font-size:0.8rem;">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:#64748b; text-align:center; padding:30px;">📭 Услуг не найдено</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>👁️ Превью услуг (как на сайте)</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; margin-top:15px;">
                    <?php 
                    $preview_services = array_slice($all_services, 0, 4);
                    foreach ($preview_services as $s): 
                    ?>
                    <div style="background:#f8f9fa; padding:20px; border-radius:15px; text-align:center; border:1px solid #e9edf2;">
                        <div style="font-size:2.5rem;"><?= $s['icon'] ?></div>
                        <h4 style="margin:10px 0 5px;"><?= htmlspecialchars($s['title']) ?></h4>
                        <p style="color:#64748b; font-size:0.85rem;"><?= htmlspecialchars($s['description']) ?></p>
                        <span style="display:inline-block; margin-top:10px; background:#0b1a2e; color:#facc15; padding:4px 15px; border-radius:20px; font-weight:600; font-size:0.85rem;"><?= $s['price'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'reviews'): ?>
            <?php
            // Статистика отзывов
            $total_reviews = count($reviews);
            $published_count = count(array_filter($reviews, function($r) { return ($r['status'] ?? 'published') === 'published'; }));
            $pending_count = count(array_filter($reviews, function($r) { return ($r['status'] ?? 'published') === 'pending'; }));
            $rejected_count = count(array_filter($reviews, function($r) { return ($r['status'] ?? 'published') === 'rejected'; }));
            
            // Средний рейтинг
            $avg_rating = 0;
            if ($total_reviews > 0) {
                $sum_rating = array_sum(array_column($reviews, 'rating'));
                $avg_rating = round($sum_rating / $total_reviews, 1);
            }
            
            // Фильтрация
            $review_filter = $_GET['review_filter'] ?? 'all';
            $filtered_reviews = $reviews;
            if ($review_filter !== 'all') {
                $filtered_reviews = array_filter($filtered_reviews, function($r) use ($review_filter) {
                    return ($r['status'] ?? 'published') === $review_filter;
                });
            }
            // Поиск
            $review_search = $_GET['review_search'] ?? '';
            if ($review_search) {
                $search_lower = mb_strtolower($review_search);
                $filtered_reviews = array_filter($filtered_reviews, function($r) use ($search_lower) {
                    return mb_strpos(mb_strtolower($r['name']), $search_lower) !== false ||
                           mb_strpos(mb_strtolower($r['text']), $search_lower) !== false ||
                           mb_strpos(mb_strtolower($r['car'] ?? ''), $search_lower) !== false;
                });
            }
            usort($filtered_reviews, function($a, $b) {
                return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
            });
            ?>
            
            <div class="flex-between" style="margin-bottom:20px;">
                <h1>💬 Управление отзывами</h1>
                <div style="display:flex; gap:10px;">
                    <a href="?tab=reviews&export_reviews=csv<?= $review_filter !== 'all' ? '&review_filter='.$review_filter : '' ?><?= $review_search ? '&review_search='.urlencode($review_search) : '' ?>" class="btn btn-sm btn-success" onclick="return confirm('Экспортировать отзывы в CSV?')">📥 CSV</a>
                    <button class="btn btn-sm btn-primary" onclick="window.print()">🖨️ Печать</button>
                </div>
            </div>
            
            <!-- СТАТИСТИКА -->
            <div class="stats" style="margin-bottom:20px;">
                <div class="stat">
                    <div class="num"><?= $total_reviews ?></div>
                    <div class="label">📊 Всего отзывов</div>
                </div>
                <div class="stat">
                    <div class="num" style="color:#15803d;"><?= $published_count ?></div>
                    <div class="label">✅ Опубликовано</div>
                </div>
                <div class="stat">
                    <div class="num" style="color:#92400e;"><?= $pending_count ?></div>
                    <div class="label">⏳ На модерации</div>
                </div>
                <div class="stat">
                    <div class="num" style="color:#dc2626;"><?= $rejected_count ?></div>
                    <div class="label">❌ Отклонено</div>
                </div>
                <div class="stat">
                    <div class="num" style="color:#f59e0b;">⭐ <?= $avg_rating ?></div>
                    <div class="label">Средний рейтинг</div>
                </div>
            </div>
            
            <!-- ФИЛЬТРЫ -->
            <div class="filters">
                <a href="?tab=reviews" class="<?= $review_filter === 'all' ? 'active' : '' ?>">📋 Все (<?= $total_reviews ?>)</a>
                <a href="?tab=reviews&review_filter=published" class="<?= $review_filter === 'published' ? 'active-published' : '' ?>">✅ Опубликованы (<?= $published_count ?>)</a>
                <a href="?tab=reviews&review_filter=pending" class="<?= $review_filter === 'pending' ? 'active-pending' : '' ?>">⏳ На модерации (<?= $pending_count ?>)</a>
                <a href="?tab=reviews&review_filter=rejected" class="<?= $review_filter === 'rejected' ? 'active-rejected' : '' ?>">❌ Отклонены (<?= $rejected_count ?>)</a>
            </div>
            
            <!-- ПОИСК -->
            <div class="export-buttons">
                <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; flex:1;">
                    <input type="hidden" name="tab" value="reviews">
                    <?php if ($review_filter !== 'all'): ?>
                        <input type="hidden" name="review_filter" value="<?= htmlspecialchars($review_filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="review_search" placeholder="🔍 Поиск по имени, тексту или авто..." value="<?= htmlspecialchars($review_search) ?>" style="padding:8px 15px; border:2px solid #e2e8f0; border-radius:20px; flex:1; min-width:200px;">
                    <button type="submit" class="btn-sm" style="background:#0b1a2e; color:white;">🔍 Найти</button>
                    <?php if ($review_search): ?>
                        <a href="?tab=reviews<?= $review_filter !== 'all' ? '&review_filter='.$review_filter : '' ?>" class="btn-sm" style="background:#ef4444; color:white; text-decoration:none;">✕ Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- ДОБАВЛЕНИЕ ОТЗЫВА -->
            <div class="card">
                <h3><?= $edit_review_data ? '✏️ Редактировать отзыв' : '➕ Добавить отзыв' ?></h3>
                <form method="POST">
                    <?php if ($edit_review_data): ?>
                        <input type="hidden" name="edit_review_id" value="<?= $edit_review_data['id'] ?>">
                    <?php endif; ?>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Имя клиента</label>
                            <input type="text" name="<?= $edit_review_data ? 'edit_review_name' : 'review_name' ?>" placeholder="Например: Алексей" value="<?= $edit_review_data ? htmlspecialchars($edit_review_data['name']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Автомобиль</label>
                            <input type="text" name="<?= $edit_review_data ? 'edit_review_car' : 'review_car' ?>" placeholder="Например: Kia Rio, 2020" value="<?= $edit_review_data ? htmlspecialchars($edit_review_data['car'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Рейтинг (звёзды)</label>
                            <select name="<?= $edit_review_data ? 'edit_review_rating' : 'review_rating' ?>">
                                <option value="5" <?= $edit_review_data && ($edit_review_data['rating'] ?? 5) == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5)</option>
                                <option value="4" <?= $edit_review_data && ($edit_review_data['rating'] ?? 5) == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (4)</option>
                                <option value="3" <?= $edit_review_data && ($edit_review_data['rating'] ?? 5) == 3 ? 'selected' : '' ?>>⭐⭐⭐ (3)</option>
                                <option value="2" <?= $edit_review_data && ($edit_review_data['rating'] ?? 5) == 2 ? 'selected' : '' ?>>⭐⭐ (2)</option>
                                <option value="1" <?= $edit_review_data && ($edit_review_data['rating'] ?? 5) == 1 ? 'selected' : '' ?>>⭐ (1)</option>
                            </select>
                        </div>
                        <?php if ($edit_review_data): ?>
                            <div class="form-group">
                                <label>Статус</label>
                                <select name="edit_review_status">
                                    <option value="published" <?= ($edit_review_data['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>✅ Опубликован</option>
                                    <option value="pending" <?= ($edit_review_data['status'] ?? 'published') === 'pending' ? 'selected' : '' ?>>⏳ На модерации</option>
                                    <option value="rejected" <?= ($edit_review_data['status'] ?? 'published') === 'rejected' ? 'selected' : '' ?>>❌ Отклонён</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Текст отзыва</label>
                            <textarea name="<?= $edit_review_data ? 'edit_review_text' : 'review_text' ?>" placeholder="Текст отзыва..." rows="3" required><?= $edit_review_data ? htmlspecialchars($edit_review_data['text']) : '' ?></textarea>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Ответ администратора</label>
                            <textarea name="<?= $edit_review_data ? 'edit_admin_response' : 'admin_response' ?>" placeholder="Ответ на отзыв..." rows="2"><?= $edit_review_data ? htmlspecialchars($edit_review_data['admin_response'] ?? '') : '' ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="<?= $edit_review_data ? 'edit_review' : 'add_review' ?>" class="btn">
                        <?= $edit_review_data ? '💾 Сохранить изменения' : '➕ Добавить отзыв' ?>
                    </button>
                    <?php if ($edit_review_data): ?>
                        <a href="?tab=reviews" class="btn" style="background:#64748b; color:white; text-decoration:none; margin-left:10px;">✕ Отмена</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- ВСЕ ОТЗЫВЫ (КАРТОЧКИ) -->
            <div class="card">
                <h3>📋 Все отзывы (<?= count($filtered_reviews) ?>)</h3>
                
                <?php if (count($filtered_reviews) > 0): ?>
                    <?php foreach ($filtered_reviews as $r): ?>
                    <div class="review-card-admin <?= $r['status'] ?? 'published' ?>" id="review-card-<?= $r['id'] ?>">
                        <div class="review-header">
                            <div class="review-avatar">👤</div>
                            <div>
                                <div class="review-name"><?= htmlspecialchars($r['name']) ?></div>
                                <div class="review-car">🚗 <?= htmlspecialchars($r['car'] ?? 'Не указан') ?></div>
                            </div>
                            <div class="review-rating">
                                <?php 
                                $rating = $r['rating'] ?? 5;
                                echo str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
                                ?>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $r['status'] ?? 'published' ?>">
                                    <?php 
                                    $status_labels = ['published' => '✅ Опубликован', 'pending' => '⏳ На модерации', 'rejected' => '❌ Отклонён'];
                                    echo $status_labels[$r['status'] ?? 'published'] ?? 'Опубликован';
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="review-text"><?= nl2br(htmlspecialchars($r['text'])) ?></div>
                        
                        <?php if (!empty($r['admin_response'])): ?>
                            <div class="admin-response">
                                <strong>👤 Администратор:</strong>
                                <?= nl2br(htmlspecialchars($r['admin_response'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:10px;">
                            <div class="review-date">📅 <?= isset($r['created_at']) ? date('d.m.Y H:i', strtotime($r['created_at'])) : '—' ?></div>
                            <div class="review-actions">
                                <a href="?tab=reviews&edit_review=<?= $r['id'] ?>" class="btn btn-sm btn-primary" style="text-decoration:none; color:white; padding:4px 12px; border-radius:6px; font-size:0.8rem;">✏️ Редактировать</a>
                                <a href="?tab=reviews&delete_review=<?= $r['id'] ?>" onclick="return confirm('Удалить отзыв?')" class="btn btn-sm btn-danger" style="text-decoration:none; color:white; padding:4px 12px; border-radius:6px; font-size:0.8rem;">🗑️ Удалить</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#64748b; text-align:center; padding:30px;">📭 Отзывов не найдено</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'orders'): ?>
            <?php
            $filter = $_GET['filter'] ?? 'all';
            $search = $_GET['search'] ?? '';
            
            $filtered_orders = $orders;
            if ($filter !== 'all') {
                $filtered_orders = array_filter($filtered_orders, function($o) use ($filter) {
                    return $o['status'] === $filter;
                });
            }
            if ($search) {
                $search_lower = mb_strtolower($search);
                $filtered_orders = array_filter($filtered_orders, function($o) use ($search_lower) {
                    return mb_strpos(mb_strtolower($o['name']), $search_lower) !== false ||
                           mb_strpos($o['phone'], $search_lower) !== false;
                });
            }
            usort($filtered_orders, function($a, $b) {
                if ($a['status'] === 'new' && $b['status'] !== 'new') return -1;
                if ($a['status'] !== 'new' && $b['status'] === 'new') return 1;
                return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
            });
            
            $total = count($orders);
            $new_count = count(array_filter($orders, function($o) { return $o['status'] === 'new'; }));
            $processed_count = count(array_filter($orders, function($o) { return $o['status'] === 'processed'; }));
            $done_count = count(array_filter($orders, function($o) { return $o['status'] === 'done'; }));
            $filtered_count = count($filtered_orders);
            
            if (isset($_POST['ajax_status_update'])) {
                $id = (int)$_POST['id'];
                $status = $_POST['status'] ?? '';
                if ($id && $status) {
                    update_order_status($id, $status);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false]);
                }
                exit;
            }
            
            if (isset($_GET['export']) && $_GET['export'] === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=zayavki_' . date('Y-m-d') . '.csv');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['ID', 'Имя', 'Телефон', 'Услуга', 'Дата', 'Статус', 'Комментарий']);
                foreach ($filtered_orders as $o) {
                    fputcsv($output, [
                        $o['id'],
                        $o['name'],
                        $o['phone'],
                        $o['service'] ?? '',
                        $o['date'] ?? '',
                        $o['status'],
                        $o['comment'] ?? ''
                    ]);
                }
                fclose($output);
                exit;
            }
            ?>
            
            <h1 style="margin-bottom:10px;">📩 Заявки</h1>
            <p style="color:#64748b; margin-bottom:20px;">Управление входящими заявками от клиентов</p>
            
            <div class="stats-orders">
                <div class="stat-order">
                    <div class="num"><?= $total ?></div>
                    <div class="label">📊 Всего</div>
                </div>
                <div class="stat-order">
                    <div class="num new <?= $new_count > 0 ? 'new-order-blink' : '' ?>"><?= $new_count ?></div>
                    <div class="label">🆕 Новые</div>
                </div>
                <div class="stat-order">
                    <div class="num processed"><?= $processed_count ?></div>
                    <div class="label">🔄 В работе</div>
                </div>
                <div class="stat-order">
                    <div class="num done"><?= $done_count ?></div>
                    <div class="label">✅ Выполнено</div>
                </div>
            </div>
            
            <div class="filters">
                <a href="?tab=orders" class="<?= $filter === 'all' ? 'active' : '' ?>">📋 Все (<?= $total ?>)</a>
                <a href="?tab=orders&filter=new" class="<?= $filter === 'new' ? 'active-new' : '' ?>">🆕 Новые (<?= $new_count ?>)</a>
                <a href="?tab=orders&filter=processed" class="<?= $filter === 'processed' ? 'active-processed' : '' ?>">🔄 В работе (<?= $processed_count ?>)</a>
                <a href="?tab=orders&filter=done" class="<?= $filter === 'done' ? 'active-done' : '' ?>">✅ Выполнено (<?= $done_count ?>)</a>
            </div>
            
            <div class="export-buttons">
                <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; flex:1;">
                    <input type="hidden" name="tab" value="orders">
                    <?php if ($filter !== 'all'): ?>
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="🔍 Поиск по имени или телефону..." value="<?= htmlspecialchars($search) ?>" style="padding:8px 15px; border:2px solid #e2e8f0; border-radius:20px; flex:1; min-width:200px;">
                    <button type="submit" class="btn-sm" style="background:#0b1a2e; color:white;">🔍 Найти</button>
                    <?php if ($search): ?>
                        <a href="?tab=orders<?= $filter !== 'all' ? '&filter='.$filter : '' ?>" class="btn-sm" style="background:#ef4444; color:white; text-decoration:none;">✕ Сбросить</a>
                    <?php endif; ?>
                </form>
                <a href="?tab=orders&export=csv<?= $filter !== 'all' ? '&filter='.$filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="btn-sm export-csv" onclick="return confirm('Экспортировать все отфильтрованные заявки в CSV?')">📥 CSV</a>
                <button class="btn-sm print" onclick="window.print()">🖨️ Печать</button>
            </div>
            
            <div class="card" style="overflow-x:auto;" id="ordersTable">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>📛 Имя</th>
                            <th>📞 Телефон</th>
                            <th>🔧 Услуга</th>
                            <th>📅 Дата</th>
                            <th>🕐 Создано</th>
                            <th>📌 Статус</th>
                            <th>⚡ Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($filtered_count > 0): ?>
                            <?php foreach ($filtered_orders as $o): ?>
                            <tr class="order-row-<?= $o['status'] ?>" id="order-<?= $o['id'] ?>">
                                <td><?= $o['id'] ?></td>
                                <td><strong><?= htmlspecialchars($o['name']) ?></strong></td>
                                <td><a href="tel:<?= htmlspecialchars($o['phone']) ?>" style="color:#3b82f6; text-decoration:none;"><?= htmlspecialchars($o['phone']) ?></a></td>
                                <td><?= htmlspecialchars($o['service'] ?? '-') ?></td>
                                <td><?= $o['date'] ? date('d.m.Y H:i', strtotime($o['date'])) : '-' ?></td>
                                <td style="font-size:0.85rem; color:#64748b;">
                                    <?php 
                                    if (isset($o['created_at'])) {
                                        $created = strtotime($o['created_at']);
                                        if (date('Y-m-d') === date('Y-m-d', $created)) {
                                            echo 'Сегодня, ' . date('H:i', $created);
                                        } elseif (date('Y-m-d', strtotime('-1 day')) === date('Y-m-d', $created)) {
                                            echo 'Вчера, ' . date('H:i', $created);
                                        } else {
                                            echo date('d.m.Y H:i', $created);
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <select class="status-select" onchange="updateStatus(<?= $o['id'] ?>, this.value)">
                                        <option value="new" <?= $o['status'] === 'new' ? 'selected' : '' ?>>🆕 Новый</option>
                                        <option value="processed" <?= $o['status'] === 'processed' ? 'selected' : '' ?>>🔄 В работе</option>
                                        <option value="done" <?= $o['status'] === 'done' ? 'selected' : '' ?>>✅ Выполнено</option>
                                    </select>
                                </td>
                                <td>
                                    <a href="?tab=orders&delete_order=<?= $o['id'] ?>" onclick="return confirm('Удалить заявку #<?= $o['id'] ?>?')" style="color:#ef4444; text-decoration:none; font-size:1.2rem;">🗑️</a>
                                    <?php if ($o['status'] === 'new'): ?>
                                        <span style="color:#f59e0b; font-size:1.2rem; margin-left:5px;" title="Новая заявка!">🔔</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; padding:30px; color:#64748b;">📭 Заявок не найдено</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
                function updateStatus(id, status) {
                    const selects = document.querySelectorAll('.status-select');
                    selects.forEach(function(s) {
                        const row = s.closest('tr');
                        if (row && row.id === 'order-' + id) {
                            s.disabled = true;
                        }
                    });
                    
                    fetch('admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'ajax_status_update=1&id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status)
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            const row = document.getElementById('order-' + id);
                            if (row) {
                                row.className = 'order-row-' + status;
                            }
                            const selects2 = document.querySelectorAll('.status-select');
                            selects2.forEach(function(s) { s.disabled = false; });
                            setTimeout(function() { window.location.reload(); }, 500);
                        } else {
                            alert('Ошибка при обновлении статуса');
                        }
                    })
                    .catch(function(error) {
                        alert('Ошибка: ' + error);
                    });
                }
            </script>
        <?php endif; ?>

        <?php if ($tab === 'gallery'): ?>
            <h1 style="margin-bottom:20px;">🖼️ Галерея</h1>
            <div class="card">
                <h3>📤 Загрузить фото</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="photo" accept="image/*" required style="display:block; margin-bottom:10px;">
                    <button type="submit" class="btn">📤 Загрузить</button>
                </form>
            </div>
            <div class="gallery-grid">
                <?php if (count($gallery) > 0): ?>
                    <?php foreach ($gallery as $img): ?>
                    <div class="gallery-item">
                        <img src="<?= $img['path'] ?>" alt="Фото">
                        <div class="info">
                            <a href="?tab=gallery&delete_photo=<?= urlencode($img['filename']) ?>" onclick="return confirm('Удалить фото?')" style="color:#ef4444; text-decoration:none;">🗑️ Удалить</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#64748b;">Нет загруженных фото</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>
