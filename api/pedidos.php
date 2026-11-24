<?php

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['error' => 'Método no permitido'], 405);
}

$entrada = leerJson();
$nombre  = trim($entrada['nombre'] ?? '');
$email   = trim($entrada['email'] ?? '');
$items   = $entrada['items'] ?? [];

if (strlen($nombre) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_array($items) || count($items) === 0) {
    responder(['error' => 'Datos incompletos o inválidos'], 422);
}

try {
    $pdo = conectar();

    // 1) Precios REALES desde la BD: el servidor nunca confía en el cliente
    $buscarPrecio = $pdo->prepare('SELECT precio FROM productos WHERE id = :id');
    $lineas = [];
    $total  = 0;

    foreach ($items as $item) {
        $productoId = (int) ($item['id'] ?? 0);
        $cantidad   = (int) ($item['cantidad'] ?? 0);

        $buscarPrecio->execute(['id' => $productoId]);
        $fila = $buscarPrecio->fetch();

        if (!$fila || $cantidad < 1) {
            responder(['error' => 'Producto o cantidad inválidos'], 422);
        }

        $precio = (float) $fila['precio'];
        $total += $precio * $cantidad;
        $lineas[] = ['id' => $productoId, 'cantidad' => $cantidad, 'precio' => $precio];
    }

    // 2) Guardar pedido + ítems: todo o nada
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO pedidos (nombre, email, total) VALUES (:nombre, :email, :total)');
        $stmt->execute(['nombre' => $nombre, 'email' => $email, 'total' => $total]);
        $pedidoId = (int) $pdo->lastInsertId();

        $stmtItem = $pdo->prepare(
            'INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario)
             VALUES (:pedido, :producto, :cantidad, :precio)'
        );
        foreach ($lineas as $l) {
            $stmtItem->execute([
                'pedido'   => $pedidoId,
                'producto' => $l['id'],
                'cantidad' => $l['cantidad'],
                'precio'   => $l['precio'],
            ]);
        }

        $pdo->commit();      // se guardan los dos o ninguno
    } catch (Throwable $e) {
        $pdo->rollBack();    // si algo falló, se deshace todo
        throw $e;
    }

    responder([
        'id'     => $pedidoId,
        'nombre' => $nombre,
        'email'  => $email,
        'items'  => $lineas,
        'total'  => $total,
        'creado' => date('c'),
    ], 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responder(['error' => 'No se pudo guardar el pedido'], 500);
}