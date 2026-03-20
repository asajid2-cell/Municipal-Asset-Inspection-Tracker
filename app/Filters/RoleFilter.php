<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts mutation routes to specific roles.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('auth_user');

        if (! is_array($user)) {
            return redirect()
                ->to(site_url('login'))
                ->with('warning', 'Sign in to continue.');
        }

        $allowedRoles = is_array($arguments) ? $arguments : [];
        $role = (string) ($user['role'] ?? '');

        if (in_array($role, $allowedRoles, true)) {
            return null;
        }

        return redirect()
            ->to(site_url('/'))
            ->with('warning', 'Your account has view-only access for that action.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
