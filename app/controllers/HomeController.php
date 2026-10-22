<?php
// filepath: foodbattle-starter/app/controllers/HomeController.php

class HomeController
{
    public function index(): void
    {
        global $db;

        require __DIR__ . '/../views/homepage.php';
    }
}