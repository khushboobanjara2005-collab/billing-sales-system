<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessContext;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Router;
use App\Core\Validator;
use App\Core\View;
use App\Models\Business;
use App\Models\User;
use PDO;
use PDOException;

final class AuthController
{
    public function __construct(private PDO $pdo, private User $users, private Business $businesses) {}

    public function loginForm(): void
    {
        if (Auth::isAuthenticated()) $this->redirect('dashboard');
        View::render('auth/login', [
            'title' => 'Sign in', 'csrf' => Csrf::token(),
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'error'),
        ] + ['layout' => 'guest']);
    }

    public function login(): void
    {
        $email = strtolower(trim($this->input('email')));
        $password = $this->input('password');
        $attemptKey = hash('sha256', $email . "\0" . (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        if ($this->users->isLoginBlocked($attemptKey)) {
            $this->loginFailed();
        }
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->users->recordLoginFailure($attemptKey);
            $this->loginFailed();
        }
        $this->users->clearLoginFailures($attemptKey);
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['user_id'], password_hash($password, PASSWORD_DEFAULT));
        }
        Auth::login($user);
        $this->selectFirstBusiness((int) $user['user_id']);
        $this->redirect(BusinessContext::id() > 0 ? 'dashboard' : 'businesses/create');
    }

    public function registerForm(): void
    {
        if (Auth::isAuthenticated()) $this->redirect('dashboard');
        if (!$this->initialSetupAllowed()) {
            http_response_code(403);
            echo 'Initial account setup is available only from this computer.';
            return;
        }
        View::render('auth/register', [
            'title' => 'Create account', 'csrf' => Csrf::token(),
            'values' => Flash::pull('old', []), 'errors' => Flash::pull('errors', []),
            'message' => Flash::pull('message'), 'messageType' => 'error',
        ] + ['layout' => 'guest']);
    }

    public function register(): void
    {
        if (!$this->initialSetupAllowed()) {
            http_response_code(403);
            echo 'Initial account setup is available only from this computer.';
            return;
        }
        $result = Validator::registration($_POST);
        if ($result['errors'] !== []) {
            $this->registrationInvalid($result['values'], $result['errors']);
        }
        $values = $result['values'];
        try {
            $this->pdo->beginTransaction();
            if ($this->users->findByEmail($values['email']) !== null) {
                $this->pdo->rollBack();
                $this->registrationInvalid(['full_name' => $values['full_name'], 'email' => $values['email'], 'phone' => $values['phone']], ['email' => 'This email is already registered.']);
            }
            $userId = $this->users->create(
                $values['full_name'], $values['email'], $values['phone'] === '' ? null : $values['phone'],
                password_hash($values['password'], PASSWORD_DEFAULT)
            );
            $businessId = $this->businesses->claimLegacyData($userId);
            $this->pdo->commit();
            Auth::login(['user_id' => $userId, 'full_name' => $values['full_name']]);
            if ($businessId !== null) {
                $business = $this->businesses->findOwned($businessId, $userId);
                BusinessContext::set($businessId, (string) ($business['business_name'] ?? ''));
                $this->refreshBusinesses($userId);
                $this->redirect('businesses/edit');
            }
            $this->redirect('businesses/create');
        } catch (PDOException $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            app_log_error($error);
            $this->registrationInvalid(
                ['full_name' => $values['full_name'], 'email' => $values['email'], 'phone' => $values['phone']],
                ['email' => 'Unable to create the account. Check the details and try again.']
            );
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('auth/login');
    }

    public function forgotPassword(): void
    {
        View::render('auth/forgot-password', [
            'title' => 'Password help', 'csrf' => Csrf::token(),
            'message' => Flash::pull('message'), 'messageType' => 'success',
        ] + ['layout' => 'guest']);
    }

    public function requestPasswordReset(): void
    {
        Flash::set('message', 'Password reset email is not configured in this local installation. Contact the application administrator.');
        Flash::set('message_type', 'success');
        $this->redirect('auth/forgot-password');
    }

    private function selectFirstBusiness(int $userId): void
    {
        $owned = $this->businesses->allOwned($userId);
        BusinessContext::refreshList($owned);
        if ($owned !== []) {
            BusinessContext::set((int) $owned[0]['business_id'], (string) $owned[0]['business_name']);
        } else {
            BusinessContext::clear();
        }
    }

    private function refreshBusinesses(int $userId): void
    {
        BusinessContext::refreshList($this->businesses->allOwned($userId));
    }

    private function initialSetupAllowed(): bool
    {
        return $this->users->countAll() > 0 || in_array(
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            ['127.0.0.1', '::1'],
            true
        );
    }

    private function registrationInvalid(array $values, array $errors): void
    {
        unset($values['password'], $values['password_confirmation']);
        Flash::set('old', $values);
        Flash::set('errors', $errors);
        $this->redirect('auth/register');
    }

    private function loginFailed(): void
    {
        Flash::set('message', 'Email or password is incorrect, or sign-in is temporarily unavailable.');
        Flash::set('message_type', 'error');
        $this->redirect('auth/login');
    }

    private function input(string $key): string
    {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }

    private function redirect(string $route): void
    {
        header('Location: ' . Router::baseUrl() . '/index.php?r=' . $route, true, 303);
        exit;
    }
}
