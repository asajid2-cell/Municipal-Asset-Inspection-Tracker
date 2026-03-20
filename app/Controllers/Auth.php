<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Handles the simple internal login flow used by the portfolio app.
 */
class Auth extends BaseController
{
    public function loginForm()
    {
        if ($this->currentUser() !== null) {
            return redirect()->to(site_url('/'));
        }

        return view('auth/login', [
            'pageTitle' => 'Sign In',
            'errors' => session()->getFlashdata('errors') ?? [],
            'demoUsers' => (new UserModel())
                ->select('full_name, email, role')
                ->where('is_active', true)
                ->orderBy('full_name', 'ASC')
                ->findAll(),
        ]);
    }

    public function login()
    {
        $credentials = [
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'password' => (string) $this->request->getPost('password'),
        ];

        $validation = service('validation');
        $validation->setRules([
            'email' => 'required|valid_email',
            'password' => 'required',
        ]);

        if (! $validation->run($credentials)) {
            /** @var array<string, string> $errors */
            $errors = $validation->getErrors();

            return redirect()
                ->to(site_url('login'))
                ->withInput()
                ->with('errors', $errors);
        }

        $user = (new UserModel())->findActiveByEmail($credentials['email']);

        if ($user === null || ! password_verify($credentials['password'], (string) $user['password_hash'])) {
            return redirect()
                ->to(site_url('login'))
                ->withInput()
                ->with('warning', 'Invalid email or password.');
        }

        $this->session->regenerate();
        $this->session->set('auth_user', [
            'id' => (int) $user['id'],
            'organization_id' => (int) ($user['organization_id'] ?? 1),
            'department_id' => (int) $user['department_id'],
            'full_name' => (string) $user['full_name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ]);

        return redirect()
            ->to(site_url('/'))
            ->with('success', 'Signed in.');
    }

    public function logout()
    {
        $this->session->remove('auth_user');
        $this->session->regenerate();

        return redirect()
            ->to(site_url('login'))
            ->with('success', 'Signed out.');
    }
}
