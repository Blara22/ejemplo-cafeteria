<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['error' => 'Método no permitido'], 405);
}

$d        = leerJson();
$email    = trim($d['email'] ?? '');
$password = $d['password'] ?? '';

try {
    $stmt = conectar()->prepare(
        'SELECT id, nombre, email, password_hash, rol FROM usuarios WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        responder(['error' => 'Correo o contraseña incorrectos'], 401);
    }

    responder(abrirSesion($usuario));
} catch (PDOException $e) {
    error_log($e->getMessage());
    responder(['error' => 'No se pudo iniciar sesión'], 500);
}