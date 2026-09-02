<?php
// functions.php — работа с JSON (с типами данных)

function load_data(string $filename): array {
    $file = __DIR__ . '/data/' . $filename;
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

function save_data(string $filename, array $data): void {
    $file = __DIR__ . '/data/' . $filename;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function get_setting(string $key): string {
    $settings = load_data('settings.json');
    return $settings[$key] ?? '';
}

function update_setting(string $key, string $value): void {
    $settings = load_data('settings.json');
    $settings[$key] = $value;
    save_data('settings.json', $settings);
}

function get_services(): array {
    return load_data('services.json');
}

function get_reviews(): array {
    return load_data('reviews.json');
}

function get_orders(): array {
    return load_data('orders.json');
}

function add_order(array $data): bool {
    $orders = get_orders();
    $data['id'] = count($orders) > 0 ? max(array_column($orders, 'id')) + 1 : 1;
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['status'] = 'new';
    $orders[] = $data;
    save_data('orders.json', $orders);
    return true;
}

function add_review(array $data): bool {
    $reviews = get_reviews();
    $data['id'] = count($reviews) > 0 ? max(array_column($reviews, 'id')) + 1 : 1;
    $reviews[] = $data;
    save_data('reviews.json', $reviews);
    return true;
}

function delete_review(int $id): bool {
    $reviews = get_reviews();
    $reviews = array_filter($reviews, function($item) use ($id) {
        return $item['id'] != $id;
    });
    save_data('reviews.json', array_values($reviews));
    return true;
}

function delete_order(int $id): bool {
    $orders = get_orders();
    $orders = array_filter($orders, function($item) use ($id) {
        return $item['id'] != $id;
    });
    save_data('orders.json', array_values($orders));
    return true;
}

function update_order_status(int $id, string $status): bool {
    $orders = get_orders();
    foreach ($orders as &$order) {
        if ($order['id'] == $id) {
            $order['status'] = $status;
            break;
        }
    }
    save_data('orders.json', $orders);
    return true;
}

function add_service(array $data): bool {
    $services = get_services();
    $data['id'] = count($services) > 0 ? max(array_column($services, 'id')) + 1 : 1;
    $services[] = $data;
    save_data('services.json', $services);
    return true;
}

function delete_service(int $id): bool {
    $services = get_services();
    $services = array_filter($services, function($item) use ($id) {
        return $item['id'] != $id;
    });
    save_data('services.json', array_values($services));
    return true;
}

function upload_file(array $file): string|bool {
    $target_dir = __DIR__ . '/uploads/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $filename = time() . '_' . basename($file['name']);
    $target_path = $target_dir . $filename;
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    return false;
}

function get_gallery(): array {
    $files = glob(__DIR__ . '/uploads/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    $gallery = [];
    foreach ($files as $file) {
        $gallery[] = [
            'filename' => basename($file),
            'path' => 'uploads/' . basename($file)
        ];
    }
    return $gallery;
}

function delete_photo(string $filename): bool {
    $file = __DIR__ . '/uploads/' . $filename;
    if (file_exists($file)) {
        unlink($file);
        return true;
    }
    return false;
}
?>
