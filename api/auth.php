<?php
require_once __DIR__ . '/helpers.php';

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Guarda en la sesión SOLO lo necesario (nunca el hash)
function abrirSesion(array $u): array
{
    iniciarSesion();
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'     => (int) $u['id'],
        'nombre' => $u['nombre'],
        'email'  => $u['email'],
        'rol'    => $u['rol'] ?? 'cliente',
    ];
    return $_SESSION['usuario'];
}

function usuarioActual(): ?array
{
    iniciarSesion();
    return $_SESSION['usuario'] ?? null;
}

function requerirLogin(): array
{
    $u = usuarioActual();
    if ($u === null) {
        responder(['error' => 'Debes iniciar sesión'], 401);
    }
    return $u;
}

function requerirAdmin(): array
{
    $u = requerirLogin();
    if ($u['rol'] !== 'admin') {
        responder(['error' => 'No tienes permiso para esta acción'], 403);
    }
    return $u;
}