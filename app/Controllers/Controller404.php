<?php

declare(strict_types=1);

namespace App\Controllers;

use WpsMicro\Core\Controller;
use WpsMicro\Core\Response;

class Controller404 extends Controller
{
    /**
     * Render the 404 page.
     */
    public function actionIndex(): Response
    {
        return $this->render('404.twig', [], 404);
    }
}
