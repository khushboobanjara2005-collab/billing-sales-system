<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use DateTimeImmutable;

final class User
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, full_name, email, password_hash FROM users WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(string $name, string $email, ?string $phone, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash)
             VALUES (:full_name, :email, :phone, :password_hash)'
        );
        $statement->execute([
            'full_name' => $name, 'email' => $email, 'phone' => $phone,
            'password_hash' => $passwordHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id'
        );
        $statement->execute(['password_hash' => $hash, 'user_id' => $userId]);
    }

    public function isLoginBlocked(string $key): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT blocked_until FROM auth_login_attempts
             WHERE attempt_key = :attempt_key AND blocked_until > CURRENT_TIMESTAMP'
        );
        $statement->execute(['attempt_key' => $key]);
        return $statement->fetchColumn() !== false;
    }

    public function recordLoginFailure(string $key): void
    {
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT failures, first_failed_at FROM auth_login_attempts
                 WHERE attempt_key = :attempt_key FOR UPDATE'
            );
            $select->execute(['attempt_key' => $key]);
            $attempt = $select->fetch();
            $expired = !is_array($attempt)
                || (new DateTimeImmutable((string) $attempt['first_failed_at'])) < new DateTimeImmutable('-15 minutes');
            $failures = $expired ? 1 : (int) $attempt['failures'] + 1;
            $blockedUntil = $failures >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            if ($expired) {
                $save = $this->pdo->prepare(
                    'INSERT INTO auth_login_attempts
                     (attempt_key, failures, first_failed_at, blocked_until)
                     VALUES (:attempt_key, :failures, CURRENT_TIMESTAMP, :blocked_until)
                     ON DUPLICATE KEY UPDATE failures = VALUES(failures),
                         first_failed_at = VALUES(first_failed_at), blocked_until = VALUES(blocked_until)'
                );
            } else {
                $save = $this->pdo->prepare(
                    'UPDATE auth_login_attempts SET failures = :failures, blocked_until = :blocked_until
                     WHERE attempt_key = :attempt_key'
                );
            }
            $save->execute([
                'attempt_key' => $key, 'failures' => $failures, 'blocked_until' => $blockedUntil,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }

    public function clearLoginFailures(string $key): void
    {
        $statement = $this->pdo->prepare('DELETE FROM auth_login_attempts WHERE attempt_key = :attempt_key');
        $statement->execute(['attempt_key' => $key]);
    }
}
