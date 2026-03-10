<?php
declare(strict_types=1);
session_start();

// เปิดโหมดแสดง Error ทุกชนิดบนหน้าจอ
ini_set('display_errors', '1');
error_reporting(E_ALL);

const INCLUDES_DIR = __DIR__ . '/entrypj/Include';
const ROUTE_DIR = __DIR__ . '/entrypj/routes';
const TEMPLATES_DIR = __DIR__ . '/entrypj/templates';
const DATABASES_DIR = __DIR__ . '/entrypj/databases';

// เช็คก่อนว่ามีไฟล์เหล่านี้อยู่จริงไหม ป้องกัน Error 500
if (!file_exists(INCLUDES_DIR . '/entrypj/router.php')) die("❌ หาไฟล์ <b>router.php</b> ไม่เจอ! ตรวจสอบว่าอัปโหลดเข้าโฟลเดอร์ Include หรือยัง");
if (!file_exists(INCLUDES_DIR . '/entrypj/view.php')) die("❌ หาไฟล์ <b>view.php</b> ไม่เจอ!");
if (!file_exists(INCLUDES_DIR . '/entrypj/database.php')) die("❌ หาไฟล์ <b>database.php</b> ไม่เจอ!");

require_once INCLUDES_DIR . '/entrypj/router.php';
require_once INCLUDES_DIR . '/entrypj/view.php';
require_once INCLUDES_DIR . '/entrypj/database.php';

// ทดสอบการเชื่อมต่อฐานข้อมูลตั้งแต่เข้าหน้าแรก
getConnection();

// เริ่มระบบ Router
dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>