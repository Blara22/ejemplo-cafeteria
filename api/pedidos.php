<?php
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['error' => 'Método no permitido'], 405);
}

$entrada = json_decode(file_get_contents('php://input'), true);
$nombre = trim($entrada['nombre'] ?? '');
$email = trim($entrada['email'] ?? '');
$items = $entrada['items'] ?? [];

if (strlen($nombre) < 4 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_array($items) || count($items) === 0) {
    responder(['error' => 'Datos incompletos o inválidos'], 422);
}

$productos = require __DIR__.'/data/catalogo-productos.php';
$total = 0;
foreach ($items as $item) {
    foreach ($productos as $p) {
        if ($p['id'] === (int) ($item['id'] ?? 0)) {
            $total += $p['precio'] * (int) ($item['cantidad'] ?? 0);
        }
    }
}

$pedido = [
    'id' => uniqid(),
    'nombre' => $nombre,
    'email' => $email,
    'items' => $items,
    'total' => $total,
    'creado' => date('c'),
];

$archivo = __DIR__ . '/data/pedidos.json';
$pedidos = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];
$pedidos[] = $pedido;
file_put_contents(
    $archivo,
    json_encode($pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

responder($pedido, 201);