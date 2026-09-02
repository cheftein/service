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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Автосервис - Ремонт и диагностика</title>
    <style>
        /* ===== БАЗОВЫЙ СБРОС ===== */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: #f8f9fa; 
            color: #1e293b; 
            padding-bottom: 80px; 
            -webkit-font-smoothing: antialiased;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
        }
        
        /* ===== ШАПКА ===== */
        .header { 
            background: #0b1a2e; 
            color: white; 
            padding: 12px 0; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }
        
        .header .container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px;
        }
        
        .logo { 
            font-size: 1.6rem; 
            font-weight: 700; 
            letter-spacing: -0.5px;
        }
        
        .logo span { 
            color: #facc15; 
        }
        
        .header-phone { 
            font-size: 1.1rem; 
            font-weight: 600; 
            color: #facc15; 
            text-decoration: none; 
            padding: 6px 16px; 
            border: 1px solid #facc15; 
            border-radius: 30px; 
            transition: 0.3s; 
            white-space: nowrap;
        }
        
        .header-phone:hover { 
            background: #facc15; 
            color: #0b1a2e; 
        }
        
        /* ===== ГЕРОЙ (Главный экран) ===== */
        .hero { 
            background: linear-gradient(135deg, #0b1a2e, #1a334a); 
            color: white; 
            padding: 50px 0; 
            border-radius: 0 0 40px 40px; 
            margin-bottom: 40px; 
        }
        
        .hero-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 40px; 
            align-items: center; 
        }
        
        .hero h1 { 
            font-size: 2.6rem; 
            line-height: 1.2;
        }
        
        .hero h1 span { 
            color: #facc15; 
        }
        
        .hero p { 
            font-size: 1.15rem; 
            opacity: 0.9; 
            margin: 20px 0; 
            line-height: 1.6;
        }
        
        .hero-image {
            background: #1e3a5a;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            border: 2px dashed #facc15;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 1.3rem;
            overflow: hidden;
        }
        
        .hero-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        /* ===== КНОПКИ ===== */
        .btn { 
            display: inline-block; 
            background: #facc15; 
            color: #0b1a2e; 
            padding: 14px 36px; 
            font-size: 1.1rem; 
            font-weight: 700; 
            border-radius: 50px; 
            text-decoration: none; 
            border: none; 
            cursor: pointer; 
            transition: 0.3s; 
            text-align: center;
        }
        
        .btn:hover { 
            background: #fde047; 
            transform: scale(1.03); 
        }
        
        .btn-block {
            width: 100%;
            text-align: center;
        }
        
        /* ===== ЗАГОЛОВКИ СЕКЦИЙ ===== */
        .section-title { 
            font-size: 2rem; 
            text-align: center; 
            margin: 40px 0 10px; 
        }
        
        .section-subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 30px;
            font-size: 1.05rem;
        }
        
        /* ===== УСЛУГИ ===== */
        .services-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 25px; 
            margin: 30px 0; 
        }
        
        .service-card { 
            background: white; 
            padding: 30px 20px; 
            border-radius: 20px; 
            text-align: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); 
            transition: 0.3s; 
            border: 1px solid #f1f5f9;
        }
        
        .service-card:hover { 
            transform: translateY(-6px); 
            box-shadow: 0 12px 30px rgba(0,0,0,0.10); 
        }
        
        .service-icon { 
            font-size: 2.8rem; 
            display: block;
            margin-bottom: 10px;
        }
        
        .service-card h3 { 
            margin: 10px 0; 
            font-size: 1.2rem;
        }
        
        .service-card p {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .service-price { 
            display: inline-block; 
            margin-top: 15px; 
            background: #0b1a2e; 
            color: #facc15; 
            padding: 5px 20px; 
            border-radius: 30px; 
            font-weight: 600; 
            font-size: 0.95rem;
        }
        
        /* ===== О НАС ===== */
        .about-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 40px; 
            align-items: center; 
            margin: 30px 0; 
            background: white; 
            padding: 40px; 
            border-radius: 30px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); 
        }
        
        .about-grid h2 {
            font-size: 2rem;
        }
        
        .about-grid p {
            color: #475569;
            margin: 20px 0;
            line-height: 1.7;
        }
        
        .about-grid ul { 
            list-style: none; 
        }
        
        .about-grid ul li { 
            margin: 10px 0; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .about-grid ul li::before { 
            content: "✅ "; 
        }
        
        .about-image {
            background: #e9edf2;
            border-radius: 20px;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 2rem;
            overflow: hidden;
        }
        
        .about-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        /* ===== ОТЗЫВЫ ===== */
        .reviews-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); 
            gap: 25px; 
            margin: 30px 0; 
        }
        
        .review-card { 
            background: white; 
            padding: 25px; 
            border-radius: 20px; 
            border-left: 5px solid #facc15; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.04); 
        }
        
        .review-card p {
            font-style: italic;
            line-height: 1.6;
        }
        
        .review-card .name { 
            font-weight: 700; 
            margin-top: 12px; 
        }
        
        .review-card .car { 
            color: #64748b; 
            font-size: 0.9rem; 
        }
        
        /* ===== ФОРМА ЗАПИСИ ===== */
        .form-section { 
            background: #0b1a2e; 
            color: white; 
            padding: 40px; 
            border-radius: 30px; 
            margin: 40px 0; 
        }
        
        .form-section h2 { 
            color: #facc15; 
            font-size: 1.8rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        .form-section .form-text p {
            opacity: 0.8;
            line-height: 1.7;
            margin-top: 10px;
        }
        
        .form-group { 
            margin-bottom: 15px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
            font-size: 0.9rem;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 14px 16px; 
            border-radius: 12px; 
            border: none; 
            background: #1e3347; 
            color: white; 
            font-size: 1rem; 
            font-family: inherit;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: 2px solid #facc15;
            background: #253d54;
        }
        
        .form-group input::placeholder, 
        .form-group textarea::placeholder { 
            color: #94a3b8; 
        }
        
        .form-group textarea { 
            resize: vertical; 
            min-height: 80px; 
        }
        
        .form-section .btn {
            margin-top: 5px;
        }
        
        /* ===== УСПЕШНОЕ СООБЩЕНИЕ ===== */
        .success-msg { 
            background: #22c55e; 
            color: white; 
            padding: 15px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            text-align: center;
            font-weight: 600;
        }
        
        /* ===== КОНТАКТЫ ===== */
        .contacts-grid { 
            display: grid; 
            grid-template-columns: 1fr 2fr; 
            gap: 30px; 
            margin: 30px 0; 
        }
        
        .contacts-info { 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        
        .contacts-info p { 
            margin: 12px 0; 
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.05rem;
        }
        
        .contacts-info p strong {
            font-weight: 600;
        }
        
        .map-container {
            border-radius: 20px;
            overflow: hidden;
            min-height: 280px;
            background: #e9edf2;
        }
        
        .map-container iframe {
            width: 100%;
            height: 280px;
            border: none;
        }
        
        /* ===== ПЛАВАЮЩАЯ КНОПКА ===== */
        .floating-phone { 
            position: fixed; 
            bottom: 25px; 
            right: 25px; 
            background: #22c55e; 
            color: white; 
            width: 70px; 
            height: 70px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 2.2rem; 
            text-decoration: none; 
            z-index: 999; 
            border: 3px solid white; 
            box-shadow: 0 6px 25px rgba(34,197,94,0.5); 
            transition: 0.3s; 
        }
        
        .floating-phone:hover { 
            transform: scale(1.1); 
            background: #16a34a; 
        }
        
        /* ============================================================ */
        /* ===== АДАПТИВНОСТЬ ДЛЯ ПЛАНШЕТОВ И ТЕЛЕФОНОВ ===== */
        /* ============================================================ */
        
        /* Планшеты (768px - 1024px) */
        @media (max-width: 1024px) {
            .hero h1 { font-size: 2.2rem; }
            .hero-grid { gap: 30px; }
            .about-grid { padding: 30px; gap: 30px; }
            .form-grid { gap: 30px; }
        }
        
        /* Телефоны (до 768px) */
        @media (max-width: 768px) {
            /* Шапка */
            .header .container { 
                flex-direction: column; 
                gap: 8px; 
                text-align: center;
            }
            .logo { font-size: 1.4rem; }
            .header-phone { font-size: 1rem; padding: 5px 14px; }
            
            /* Герой */
            .hero { 
                padding: 35px 0; 
                border-radius: 0 0 30px 30px; 
            }
            .hero-grid { 
                grid-template-columns: 1fr; 
                text-align: center; 
                gap: 25px;
            }
            .hero h1 { 
                font-size: 1.8rem; 
            }
            .hero p { 
                font-size: 1rem; 
                margin: 15px 0;
            }
            .hero-image {
                min-height: 150px;
                padding: 20px;
                font-size: 1rem;
            }
            .hero-image img {
                max-height: 150px;
            }
            
            /* Кнопки */
            .btn { 
                padding: 12px 28px; 
                font-size: 1rem; 
                width: 100%;
                max-width: 300px;
            }
            
            /* Заголовки */
            .section-title { 
                font-size: 1.6rem; 
                margin: 30px 0 10px;
            }
            .section-subtitle {
                font-size: 0.95rem;
                margin-bottom: 20px;
            }
            
            /* Услуги */
            .services-grid { 
                grid-template-columns: 1fr 1fr; 
                gap: 15px;
                margin: 20px 0;
            }
            .service-card { 
                padding: 20px 15px; 
            }
            .service-icon { font-size: 2.2rem; }
            .service-card h3 { font-size: 1rem; }
            .service-card p { font-size: 0.85rem; }
            .service-price { font-size: 0.85rem; padding: 4px 14px; }
            
            /* О нас */
            .about-grid { 
                grid-template-columns: 1fr; 
                padding: 25px; 
                gap: 20px;
                margin: 20px 0;
            }
            .about-grid h2 { font-size: 1.6rem; }
            .about-grid p { font-size: 0.95rem; }
            .about-image { min-height: 140px; font-size: 1.5rem; }
            .about-image img { max-height: 150px; }
            
            /* Отзывы */
            .reviews-grid { 
                grid-template-columns: 1fr; 
                gap: 15px;
                margin: 20px 0;
            }
            .review-card { padding: 20px; }
            .review-card p { font-size: 0.95rem; }
            
            /* Форма */
            .form-section { 
                padding: 25px; 
                margin: 25px 0; 
                border-radius: 20px;
            }
            .form-section h2 { font-size: 1.5rem; }
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .form-group input, 
            .form-group select, 
            .form-group textarea { 
                padding: 12px 14px; 
                font-size: 0.95rem;
            }
            .form-section .btn {
                max-width: 100%;
            }
            
            /* Контакты */
            .contacts-grid { 
                grid-template-columns: 1fr; 
                gap: 20px;
                margin: 20px 0;
            }
            .contacts-info { padding: 20px; }
            .contacts-info p { 
                font-size: 0.95rem; 
                flex-wrap: wrap;
            }
            .map-container { min-height: 200px; }
            .map-container iframe { height: 200px; }
            
            /* Плавающая кнопка */
            .floating-phone { 
                width: 60px; 
                height: 60px; 
                font-size: 1.8rem; 
                bottom: 15px; 
                right: 15px; 
                border-width: 2px;
            }
        }
        
        /* Очень маленькие телефоны (до 480px) */
        @media (max-width: 480px) {
            .container { padding: 0 15px; }
            
            .hero h1 { font-size: 1.5rem; }
            .hero { padding: 25px 0; }
            
            .services-grid { 
                grid-template-columns: 1fr; 
                gap: 12px;
            }
            .service-card { padding: 16px; }
            
            .about-grid { padding: 20px; }
            
            .form-section { padding: 20px; }
            .form-section h2 { font-size: 1.3rem; }
            
            .contacts-info { padding: 16px; }
            .contacts-info p { font-size: 0.9rem; }
            
            .floating-phone { 
                width: 52px; 
                height: 52px; 
                font-size: 1.5rem; 
                bottom: 12px; 
                right: 12px; 
            }
            
            .logo { font-size: 1.2rem; }
            .header-phone { font-size: 0.9rem; padding: 4px 12px; }
        }
        
        /* Для очень больших экранов */
        @media (min-width: 1400px) {
            .container { max-width: 1300px; }
            .hero h1 { font-size: 3.2rem; }
        }
    </style>
</head>
<body>

<!-- ===== ШАПКА ===== -->
<header class="header">
    <div class="container">
        <div class="logo"><span>Авто</span>Тайм</div>
        <a href="tel:<?= preg_replace('/[^0-9]/', '', $settings['phone']) ?>" class="header-phone">📞 <?= $settings['phone'] ?></a>
    </div>
</header>

<!-- ===== ГЕРОЙ ===== -->
<section class="hero">
    <div class="container hero-grid">
        <div>
            <h1><?= $settings['hero_title'] ?></h1>
            <p><?= $settings['hero_subtitle'] ?></p>
            <a href="#form" class="btn">Записаться онлайн →</a>
        </div>
        <div class="hero-image">
            <?php if (count($gallery) > 0): ?>
                <img src="<?= $gallery[0]['path'] ?>" alt="Наш автосервис">
            <?php else: ?>
                🚗 Добавьте фото через админку
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== УСЛУГИ ===== -->
<div class="container">
    <h2 class="section-title">Наши услуги</h2>
    <p class="section-subtitle">Работаем со всеми марками автомобилей</p>
    <div class="services-grid">
        <?php foreach ($services as $service): ?>
        <div class="service-card">
            <span class="service-icon"><?= $service['icon'] ?></span>
            <h3><?= $service['title'] ?></h3>
            <p><?= $service['description'] ?></p>
            <span class="service-price"><?= $service['price'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== О НАС ===== -->
<div class="container">
    <div class="about-grid">
        <div>
            <h2>О нас</h2>
            <p><?= $settings['about_text'] ?></p>
            <ul>
                <li>Сертифицированные мастера с опытом от 10 лет</li>
                <li>Гарантия на все виды работ и запчасти</li>
                <li>Прозрачные цены без скрытых доплат</li>
                <li>Работаем без выходных с 9:00 до 21:00</li>
            </ul>
        </div>
        <div class="about-image">
            <?php 
            $team_photos = glob('uploads/team_*.*');
            if (count($team_photos) > 0): ?>
                <img src="<?= $team_photos[0] ?>" alt="Наша команда">
            <?php else: ?>
                🛠️ Фото команды
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== ОТЗЫВЫ ===== -->
<div class="container">
    <h2 class="section-title">Отзывы наших клиентов</h2>
    <p class="section-subtitle">Реальные отзывы от наших довольных клиентов</p>
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
            <div class="review-card">
                <p>Пока нет отзывов. Добавьте их через админ-панель!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== ФОРМА ЗАПИСИ ===== -->
<div class="container" id="form">
    <div class="form-section">
        <div class="form-grid">
            <div class="form-text">
                <h2>Запишитесь онлайн</h2>
                <p>Выберите удобную дату и время, мы подтвердим запись в течение 15 минут.</p>
                <p style="margin-top:15px;">⚡️ Возможна запись в день обращения</p>
            </div>
            <form method="POST">
                <?php if (isset($order_success)): ?>
                    <div class="success-msg">✅ Заявка отправлена! Мы свяжемся с вами.</div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Ваше имя</label>
                    <input type="text" name="name" placeholder="Например: Иван" required>
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
                <button type="submit" name="order_submit" class="btn btn-block">Отправить заявку</button>
            </form>
        </div>
    </div>
</div>

<!-- ===== КОНТАКТЫ ===== -->
<div class="container">
    <h2 class="section-title">Как нас найти</h2>
    <div class="contacts-grid">
        <div class="contacts-info">
            <p>📍 <strong>Адрес:</strong> <?= $settings['address'] ?></p>
            <p>📞 <strong>Телефон:</strong> <?= $settings['phone'] ?></p>
            <p>🕒 <strong>Режим работы:</strong> <?= $settings['work_hours'] ?></p>
            <p>📧 <strong>Email:</strong> <?= $settings['email'] ?></p>
        </div>
        <div class="map-container">
            <iframe 
                src="https://yandex.ru/map-widget/v1/?ll=47.251604%2C56.098763&z=16&pt=47.251604%2C56.098763&l=map" 
                allowfullscreen>
            </iframe>
        </div>
    </div>
</div>

<!-- ===== ПЛАВАЮЩАЯ КНОПКА ===== -->
<a href="tel:<?= preg_replace('/[^0-9]/', '', $settings['phone']) ?>" class="floating-phone" aria-label="Позвонить">
    📞
</a>

<!-- Ссылка на админку скрыта. Доступ по адресу /admin.php -->

</body>
</html>
