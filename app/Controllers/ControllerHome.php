<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Home;
use App\Services\AuthService;
use WpsMicro\Core\Controller;
use WpsMicro\Core\Request;
use WpsMicro\Core\Response;
use WpsMicro\Core\Validator;
use WpsMicro\Core\ViewRenderer;

class ControllerHome extends Controller
{
    /**
     * Home page data model.
     */
    private Home $home;

    /**
     * Authentication service used by the home page.
     */
    private AuthService $auth;

    /**
     * Prepare the controller dependencies.
     */
    public function __construct(
        Request $request,
        ViewRenderer $view,
        Validator $validator,
        Home $home,
        AuthService $auth
    ) {
        $this->home = $home;
        $this->auth = $auth;

        parent::__construct($request, $view, $validator);
    }

    /**
     * Render the home page.
     */
    public function actionIndex(): Response
    {
        return $this->render('home/home.twig', [
            'auth_user' => $this->auth->user(),
            'messages' => $this->home->latestMessages(),
        ]);
    }
}
