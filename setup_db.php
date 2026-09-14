<?php
$pdo = new PDO('mysql:host=localhost', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS keuangan_laravel');
echo "Database created";
