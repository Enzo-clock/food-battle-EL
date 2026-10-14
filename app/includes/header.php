<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/functions.php';
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($title ?? 'FoodBattle') ?> · FoodBattle</title><link rel="stylesheet" href="/assets/style.css"></head><body><header><a class="logo" href="/">FoodBattle 🍔</a><nav><a href="/">Découvrir</a><a href="/classement.php">Classement</a><a href="/dashboard.php">Dashboard</a></nav></header><main>
