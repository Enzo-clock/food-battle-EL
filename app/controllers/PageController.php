<?php
// filepath: foodbattle-starter/app/controllers/PageController.php

require_once __DIR__ . '/../includes/functions.php';

/** Pages de contenu statique : mentions légales, confidentialité, contact. */
class PageController
{
    public function legal(): void
    {
        $title = 'Mentions légales';
        require __DIR__ . '/../views/pages/legal.php';
    }

    public function privacy(): void
    {
        $title = 'Confidentialité (RGPD)';
        require __DIR__ . '/../views/pages/privacy.php';
    }

    public function contact(): void
    {
        $title = 'Contact';
        require __DIR__ . '/../views/pages/contact.php';
    }
}
