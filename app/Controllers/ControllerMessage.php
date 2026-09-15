<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Home;
use App\Services\AuthService;
use WpsMicro\Core\Controller;
use WpsMicro\Core\Exceptions\HttpNotFoundException;
use WpsMicro\Core\Request;
use WpsMicro\Core\Response;
use WpsMicro\Core\Router;
use WpsMicro\Core\Session;
use WpsMicro\Core\Validator;
use WpsMicro\Core\ViewRenderer;

class ControllerMessage extends Controller
{
    /** Prepare the private message workflow. */
    public function __construct(
        Request $request,
        ViewRenderer $view,
        Validator $validator,
        private Home $messages,
        private AuthService $auth,
        private Session $session,
        private Router $router,
    ) {
        parent::__construct($request, $view, $validator);
    }

    /** List only the current user's messages. */
    public function actionIndex(): Response
    {
        $user = $this->auth->requireUser();
        $total = $this->messages->countForUser((int) $user['id']);
        $pages = max(1, (int) ceil($total / 10));
        $requestedPage = filter_var($this->request->query('page', 1), FILTER_VALIDATE_INT);
        $page = max(1, min($pages, $requestedPage === false ? 1 : $requestedPage));

        return $this->render('messages/index.twig', [
            'auth_user' => $user,
            'messages' => $this->messages->pageForUser((int) $user['id'], $page),
            'page' => $page, 'pages' => $pages, 'total' => $total,
        ]);
    }

    /** Display an empty message form. */
    public function actionCreate(): Response
    {
        return $this->render('messages/form.twig', ['auth_user' => $this->auth->requireUser(), 'message' => null]);
    }

    /** Validate and store a message without accepting an owner from the client. */
    public function actionStore(): Response
    {
        $user = $this->auth->requireUser();
        $data = $this->validate(['title' => 'required|string|max:255', 'body' => 'required|string|max:10000']);
        $this->messages->createForUser((int) $user['id'], $data['title'], $data['body']);

        return $this->saved('Message created.');
    }

    /** Display a form only for an owned record. */
    public function actionEdit(string $id): Response
    {
        $user = $this->auth->requireUser();

        return $this->render('messages/form.twig', [
            'auth_user' => $user,
            'message' => $this->ownedMessage($id, (int) $user['id']),
        ]);
    }

    /** Authorize before validating or updating a message. */
    public function actionUpdate(string $id): Response
    {
        $user = $this->auth->requireUser();
        $message = $this->ownedMessage($id, (int) $user['id']);
        $data = $this->validate(['title' => 'required|string|max:255', 'body' => 'required|string|max:10000']);
        $this->messages->updateForUser((int) $message['id'], (int) $user['id'], $data['title'], $data['body']);

        return $this->saved('Message updated.');
    }

    /** Delete an owned message through a CSRF-protected request. */
    public function actionDelete(string $id): Response
    {
        $user = $this->auth->requireUser();
        $message = $this->ownedMessage($id, (int) $user['id']);
        $this->messages->deleteForUser((int) $message['id'], (int) $user['id']);

        return $this->saved('Message deleted.');
    }

    /**
     * Use 404 for both missing and foreign records to avoid disclosing their existence.
     *
     * @return array{id: int, title: string, body: string, created_at: string}
     */
    private function ownedMessage(string $id, int $userId): array
    {
        $integer = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $message = $integer === false ? null : $this->messages->findForUser($integer, $userId);

        return $message ?? throw new HttpNotFoundException();
    }

    /** Redirect after a successful mutation. */
    private function saved(string $message): Response
    {
        $this->session->flash('success', $message);

        return $this->redirect($this->router->url('messages.index'));
    }
}
