<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['error' => 'Método no permitido'], 405);
}

$d        = leerJson();
$nombre   = trim($d['nombre'] ?? '');
$email    = trim($d['email'] ?? '');
$password = $d['password'] ?? '';

if (strlen($nombre) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    responder(['error' => 'Datos inválidos'], 422);
}

try {
    $pdo  = conectar();
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash) VALUES (:nombre, :email, :hash)'
    );
    $stmt->execute([
        'nombre' => $nombre,
        'email'  => $email,
        'hash'   => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $id = (int) $pdo->lastInsertId();

    responder(abrirSesion(['id' => $id, 'nombre' => $nombre, 'email' => $email]), 201);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {   // correo repetido (UNIQUE)
        responder(['error' => 'Ese correo ya está registrado'], 409);
    }
    error_log($e->getMessage());
    responder(['error' => 'No se pudo registrar'], 500);
}