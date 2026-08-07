<?php
require_once __DIR__ . '/config.php';

// encerra sessão do usuário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

header('Location: inicio.php');
exit;