<?php

declare(strict_types=1);

const ALLOW_METHODS = ['GET', 'POST'];
const INDEX_URI = '';
const INDEX_ROUNTE = 'home';

function normalizeUri(string $uri): string
{
    // 1. ตัดพารามิเตอร์ด้านหลังออก (เช่น ?event_id=1) 
    $parsedUri = parse_url($uri, PHP_URL_PATH);
    
    // 2. จัดรูปแบบข้อความ
    $cleanUri = strtolower(trim($parsedUri, '/'));

    // 3. ป้องกันการส่ง .php ติดมาด้วย (เพื่อไม่ให้ getFilePath กลายเป็น .php.php)
    $cleanUri = str_replace('.php', '', $cleanUri);

    return $cleanUri == INDEX_URI ? INDEX_ROUNTE : $cleanUri;
}

function notFound()
{
    http_response_code(404);
    renderView('404');
    exit;
}

function getFilePath(string $uri): string
{
    // โค้ดเดิมของคุณ: จะวิ่งไปหาไฟล์ในโฟลเดอร์ routes/ เสมอ
    return ROUTE_DIR . '/' . normalizeUri($uri) . '.php';
}

function dispatch(string $uri, string $method): void
{
    $uri = normalizeUri($uri);

    if (!in_array(strtoupper($method), ALLOW_METHODS)) {
        notFound();
    }

    $filePath = getFilePath($uri);
    
    // เช็คว่าหาไฟล์เจอไหม
    if (file_exists($filePath)) {
        include($filePath);
        return;
    } else {
        // ถ้าหาไม่เจอ ให้โยนไปหน้า 404
        notFound();
    }
}
?>