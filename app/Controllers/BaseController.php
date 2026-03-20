<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Helpers shared across the server-rendered application.
     *
     * @var list<string>
     */
    protected $helpers = ['form', 'url'];

    /**
     * Session service shared across authenticated controllers.
     */
    protected $session;

    /**
     * Currently authenticated user payload stored in session.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $currentUser = null;

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = service('session');
        $user = $this->session->get('auth_user');
        $this->currentUser = is_array($user) ? $user : null;
    }

    /**
     * Returns the authenticated user array when a session is active.
     *
     * @return array<string, mixed>|null
     */
    protected function currentUser(): ?array
    {
        return $this->currentUser;
    }

    /**
     * Returns the logged-in user ID for audit logging.
     */
    protected function currentUserId(): ?int
    {
        if ($this->currentUser === null) {
            return null;
        }

        return (int) $this->currentUser['id'];
    }

    /**
     * Returns the logged-in user role for access checks in controllers.
     */
    protected function currentUserRole(): ?string
    {
        if ($this->currentUser === null) {
            return null;
        }

        return (string) $this->currentUser['role'];
    }

    /**
     * Returns the current tenant/organization ID.
     */
    protected function currentOrganizationId(): int
    {
        if ($this->currentUser === null) {
            return 1;
        }

        return (int) ($this->currentUser['organization_id'] ?? 1);
    }
}
