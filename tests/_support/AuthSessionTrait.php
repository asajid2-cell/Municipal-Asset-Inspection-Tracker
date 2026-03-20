<?php

namespace Tests\Support;

/**
 * Provides authenticated session payloads and file helpers for feature tests.
 */
trait AuthSessionTrait
{
    /**
     * Returns a session payload for a seeded user.
     *
     * @return array<string, array<string, int|string>>
     */
    protected function authSession(string $email = 'admin@northriver.local'): array
    {
        $row = db_connect('default')->table('users')
            ->select('id, department_id, full_name, email, role')
            ->where('email', $email)
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);

        return [
            'auth_user' => [
                'id' => (int) $row['id'],
                'department_id' => (int) $row['department_id'],
                'full_name' => (string) $row['full_name'],
                'email' => (string) $row['email'],
                'role' => (string) $row['role'],
            ],
        ];
    }

    /**
     * Performs a POST request with a seeded authenticated session and uploaded files.
     */
    protected function postWithFiles(string $path, array $params, array $files, string $email = 'admin@northriver.local')
    {
        service('superglobals')->setFilesArray($files);

        try {
            return $this->withSession($this->authSession($email))->post($path, $params);
        } finally {
            service('superglobals')->setFilesArray([]);
        }
    }
}
