<?php
require_once 'functions.php';

$settings = load_data('settings.json');
$services = get_services();
$reviews = get_reviews();
$gallery = get_gallery();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_submit'])) {
    $order_data = [
        'name' => trim($_POST['name']),
        'phone' => trim($_POST['phone']),
        'service' => trim($_POST['service']),
        'date' => $_POST['date'] ?? null,
        'comment' => trim($_POST['comment'] ?? '')
    ];
    if ($order_data['name'] && $order_data['phone']) {
        add_order($order_data);
        $order_success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автосервис - Ремонт и диагностика</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f8f9fa; color:#1e293b; padding-bottom:80px; }
        .container { max-width:1200px; margin:0 auto; padding:0 20px; }
        .header { background:#0b1a2e; color:white; padding:15px 0; position:sticky; top:0; z-index:100; }
        .header .container { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { font-size:1.8rem; font-weight:700; }
        .logo span { color:#facc15; }
        .header-phone { font-size:1.3rem; font-weight:600; color:#facc15; text-decoration:none; padding:8px 18px; border:1px solid #facc15; border-radius:30px; transition:0.3s; }
        .header-phone:hover { background:#facc15; color:#0b1a2e; }
        .hero { background:linear-gradient(135deg,#0b1a2e,#1a334a); color:white; padding:60px 0; border-radius:0 0 40px 40px; margin-bottom:40px; }
        .hero-grid { display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center; }
        .hero h1 { font-size:2.8rem; }
        .hero h1 span { color:#facc15; }
        .hero p { font-size:1.2rem; opacity:0.9; margin:20px 0; }
        .btn { display:inline-block; background:#facc15; color:#0b1a2e; padding:16px 40px; font-size:1.2rem; font-weight:700; border-radius:50px; text-decoration:none; border:none; cursor:pointer; transition:0.3s; }
        .btn:hover { background:#fde047; transform:scale(1.05); }
        .section-title { font-size:2.2rem; text-align:center; margin:40px 0 15px; }
        .services-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:25px; margin:30px 0; }
        .service-card { background:white; padding:30px; border-radius:20px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.06); transition:0.3s; }
        .service-card:hover { transform:translateY(-8px); box-shadow:0 12px 30px rgba(0,0,0,0.12); }
        .service-icon { font-size:3rem; }
        .service-card h3 { margin:10px 0; }
        .service-price { display:inline-block; margin-top:15px; background:#0b1a2e; color:#facc15; padding:5px 20px; border-radius:30px; font-weight:600; }
        .reviews-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:25px; margin:30px 0; }
        .review-card { background:white; padding:25px; border-radius:20px; border-left:5px solid #facc15; box-shadow:0 2px 10px rgba(0,0,0,0.04); }
        .review-card .name { font-weight:700; margin-top:10px; }
        .review-card .car { color:#64748b; font-size:0.9rem; }
        .form-section { background:#0b1a2e; color:white; padding:50px; border-radius:30px; margin:40px 0; }
        .form-section h2 { color:#facc15; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; font-weight:500; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:14px; border-radius:12px; border:none; background:#1e3347; color:white; font-size:1rem; }
        .form-group input::placeholder, .form-group textarea::placeholder { color:#94a3b8; }
        .form-group textarea { resize:vertical; min-height:80px; }
        .floating-phone { position:fixed; bottom:25px; right:25px; background:#22c55e; color:white; width:70px; height:70px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2.2rem; text-decoration:none; z-index:999; border:3px solid white; box-shadow:0 6px 25px rgba(34,197,94,0.5); transition:0.3s; }
        .floating-phone:hover { transform:scale(1.1); background:#16a34a; }
        .success-msg { background:#22c55e; color:white; padding:15px; border-radius:10px; margin-bottom:20px; }
        .contacts-grid { display:grid; grid-template-columns:1fr 2fr; gap:30px; margin:30px 0; }
        .contacts-info { background:white; padding:30px; border-radius:20px; }
        .contacts-info p { margin:12px 0; }
        .about-grid { display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center; margin:30px 0; background:white; padding:50px; border-radius:30px; box-shadow:0 4px 15px rgba(0,0,0,0.04); }
        .about-grid ul { list-style:none; }
        .about-grid ul li { margin:10px 0; }
        .about-grid ul li::before { content:"✅ "; }
        .map-placeholder { background:#cbd5e1; border-radius:20px; min-height:200px; display:flex; align-items:center; justify-content:center; color:#475569; border:2px dashed #64748b; }
        @media(max-width:768px){ .hero-grid { grid-template-columns:1fr; text-align:center; } .hero h1 { font-size:2rem; } .about-grid { grid-template-columns:1fr; } .contacts-grid { grid-template-columns:1fr; } .header .container { flex-direction:column; gap:10px; } .floating-phone { width:60px; height:60px; font-size:1.8rem; bottom:15px; right:15px; } }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <div class="logo"><span>Авто</span>Мастер</div>
        <a href="tel:<?= preg_replace('/[^0-9]/', '', $settings['phone']) ?>" class="header-phone">📞 <?= $settings['phone'] ?></a>
    </div>
</header>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <h1><?= $settings['hero_title'] ?></h1>
            <p><?= $settings['hero_subtitle'] ?></p>
            <a href="#form" class="btn">Записаться онлайн →</a>
        </div>
        <div style="background:#1e3a5a; border-radius:20px; padding:40px; text-align:center; border:2px dashed #facc15; min-height:200px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:1.5rem;">
            <?php if (count($gallery) > 0): ?>
                <img src="<?= $gallery[0]['path'] ?>" style="max-width:100%; max-height:200px; border-radius:10px; object-fit:cover;">
            <?php else: ?>
                🚗 Добавьте фото через админку
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container">
    <h2 class="section-title">Наши услуги</h2>
    <p style="text-align:center; color:#64748b; margin-bottom:30px;">Работаем со всеми марками автомобилей</p>
    <div class="services-grid">
        <?php foreach ($services as $service): ?>
        <div class="service-card">
            <div class="service-icon"><?= $service['icon'] ?></div>
            <h3><?= $service['title'] ?></h3>
            <p style="color:#475569;"><?= $service['description'] ?></p>
            <span class="service-price"><?= $service['price'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="container">
    <div class="about-grid">
        <div>
            <h2 style="font-size:2rem;">О нас</h2>
            <p style="color:#475569; margin:20px 0;"><?= $settings['about_text'] ?></p>
            <ul>
                <li>Сертифицированные мастера с опытом от 10 лет</li>
                <li>Гарантия на все виды работ и запчасти</li>
                <li>Прозрачные цены без скрытых доплат</li>
                <li>Работаем в будние дни с 9:00 до 19:00</li>
            </ul>
        </div>
        <div style="background:#e9edf2; border-radius:20px; min-height:200px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:2rem;">
            <?php 
            $team_photos = glob('uploads/team_*.*');
            if (count($team_photos) > 0): ?>
                <img src="<?= $team_photos[0] ?>" style="max-width:100%; max-height:200px; border-radius:10px; object-fit:cover;">
            <?php else: ?>
                🛠️ Фото команды
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="section-title">Отзывы наших клиентов</h2>
    <div class="reviews-grid">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <p>«<?= $review['text'] ?>»</p>
                <div class="name"><?= $review['name'] ?></div>
                <div class="car">📷 <?= $review['car'] ?></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="review-card"><p>Пока нет отзывов. Добавьте их через админ-панель!</p></div>
        <?php endif; ?>
    </div>
</div>

<div class="container" id="form">
    <div class="form-section">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:start;">
            <div>
                <h2 style="margin-bottom:15px;">Запишитесь онлайн</h2>
                <p style="opacity:0.8;">Выберите удобную дату и время, мы подтвердим запись в течение 15 минут.</p>
                <p style="opacity:0.8; margin-top:15px;">⚡️ Возможна запись в день обращения</p>
            </div>
            <form method="POST">
                <?php if (isset($order_success)): ?>
                    <div class="success-msg">✅ Заявка отправлена! Мы свяжемся с вами.</div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Ваше имя</label>
                    <input type="text" name="name" placeholder="Иван" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required>
                </div>
                <div class="form-group">
                    <label>Услуга</label>
                    <select name="service">
                        <?php foreach ($services as $service): ?>
                        <option value="<?= $service['title'] ?>"><?= $service['title'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата и время</label>
                    <input type="datetime-local" name="date">
                </div>
                <div class="form-group">
                    <label>Комментарий</label>
                    <textarea name="comment" placeholder="Марка, модель, год, неисправность..." rows="3"></textarea>
                </div>
                <button type="submit" name="order_submit" class="btn" style="width:100%; text-align:center;">Отправить заявку</button>
            </form>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="section-title">Как нас найти</h2>
    <div class="contacts-grid">
        <div class="contacts-info">
            <p>📍 <strong>Адрес:</strong> <?= $settings['address'] ?></p>
            <p>📞 <strong>Телефон:</strong> <?= $settings['phone'] ?></p>
            <p>🕒 <strong>Режим работы:</strong> <?= $settings['work_hours'] ?></p>
            <p>📧 <strong>Email:</strong> <?= $settings['email'] ?></p>
        </div>
        <div class="map-placeholder">
            🗺️ Здесь будет Яндекс.Карта
        </div>
    </div>
</div>

<a href="tel:<?= preg_replace('/[^0-9]/', '', $settings['phone']) ?>" class="floating-phone">📞</a>

<!-- Ссылка на админку скрыта. Доступ по адресу /admin.php -->

</body>
</html>
