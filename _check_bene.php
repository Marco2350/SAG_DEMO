<?php
require 'core/Env.php';
Env::load('.env');
$pdo = new PDO('mysql:host=' . Env::get('DB_HOST') . ';dbname=' . Env::get('DB_NAME') . ';charset=utf8mb4',
    Env::get('DB_USER'), Env::get('DB_PASS'),
    [PDO::ATTR_TIMEOUT => 8, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "beneficiarios por proyecto/estado:\n";
foreach ($pdo->query("SELECT id_proyecto, estado, COUNT(*) c FROM sag_beneficiarios GROUP BY id_proyecto, estado") as $r)
    echo "  p{$r['id_proyecto']} {$r['estado']}: {$r['c']}\n";
echo "formatos de DNI (muestra):\n";
foreach ($pdo->query("SELECT DISTINCT dni FROM sag_beneficiarios WHERE dni IS NOT NULL LIMIT 10") as $r)
    echo "  '{$r['dni']}'\n";
echo "organizaciones activas p1: " . $pdo->query("SELECT COUNT(*) FROM sag_organizaciones WHERE id_proyecto=1")->fetchColumn() . "\n";
