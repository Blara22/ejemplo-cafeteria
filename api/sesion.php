<?php // api/sesion.php
require __DIR__ . '/auth.php';

responder(['usuario' => usuarioActual()]);   // null si es visitante