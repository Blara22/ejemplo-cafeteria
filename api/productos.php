<?php 
require __DIR__. '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';

function validarProducto(array $d): array
{
    $nombre = trim($d['nombre'] ?? '');
    $precio = $d['precio'] ?? null;
    $emoji  = trim($d['emoji'] ?? '☕');

    if (strlen($nombre) < 3 || strlen($nombre) > 30 || !is_numeric($precio) || $precio < 1 || $precio > 500) {
        responder(['error' => 'Datos inválidos'], 422);
    }
    return [$nombre, (float) $precio, $emoji !== '' ? $emoji : '☕'];
}

try {

    $pdo = conectar();
    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if ($method !== 'GET') {
        requerirAdmin();   // consultar el menú es público; modificarlo no
    }

    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT id, nombre, precio, emoji FROM productos WHERE id = :id');
            $stmt->execute(['id' => (int) $_GET['id']]);
            $producto = $stmt->fetch();

            if (!$producto) {
                responder(['error' => 'No se encontró el producto'], 404);
            }
            responder(formatearProducto($producto));
        }

        $buscar = trim($_GET['buscar'] ?? '');
        $stmt = $pdo->prepare(
            'SELECT id, nombre, precio, emoji FROM productos WHERE nombre LIKE :buscar ORDER BY id'
        );
        $stmt->execute(['buscar' => "%$buscar%"]);

        responder(array_map('formatearProducto', $stmt->fetchAll()));
        break;

        case 'POST': 
            [$nombre, $precio, $emoji] = validarProducto(leerJson());

            $stmt = $pdo->prepare(
                'INSERT INTO productos (nombre, precio, emoji) VALUES (:nombre, :precio, :emoji)'
            );
            $stmt->execute(['nombre' => $nombre, 'precio' => $precio, 'emoji' => $emoji]);

            responder([
                'id'     => (int) $pdo->lastInsertId(),
                'nombre' => $nombre,
                'precio' => $precio,
                'emoji'  => $emoji,
            ], 201);
            break;

            case 'PUT':
            if ($id === null) {
                responder(['error' => 'Falta el id'], 400);
            }
            [$nombre, $precio, $emoji] = validarProducto(leerJson());

            // rowCount() vale 0 también cuando no cambia ningún valor,
            // así que verificamos primero que exista.
            $existe = $pdo->prepare('SELECT id FROM productos WHERE id = :id');
            $existe->execute(['id' => $id]);
            if (!$existe->fetch()) {
                responder(['error' => 'No se encontró el producto'], 404);
            }

            $stmt = $pdo->prepare(
                'UPDATE productos SET nombre = :nombre, precio = :precio, emoji = :emoji WHERE id = :id'
            );
            $stmt->execute(['nombre' => $nombre, 'precio' => $precio, 'emoji' => $emoji, 'id' => $id]);

            responder(['id' => $id, 'nombre' => $nombre, 'precio' => $precio, 'emoji' => $emoji]);
            break;
            case 'DELETE':
            if ($id === null) {
                responder(['error' => 'Falta el id'], 400);
            }

            try {
                $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id');
                $stmt->execute(['id' => $id]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {   // violación de llave foránea
                    responder(['error' => 'El producto ya está en pedidos y no se puede eliminar'], 409);
                }
                throw $e;
            }

            if ($stmt->rowCount() === 0) {
                responder(['error' => 'No se encontró el producto'], 404);
            }
            http_response_code(204);   // éxito sin cuerpo
            exit;

        default:
            responder(['error' => 'Método no permitido'], 405);
    }
    
    

}catch(PDOException $ex) {
    responder(['error' => 'Ocurrió un error'], 500);
}