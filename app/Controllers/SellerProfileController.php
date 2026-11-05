<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Router;
use App\Core\Validator;
use App\Core\View;
use App\Models\SellerProfile;
use PDOException;

final class SellerProfileController
{
    public function __construct(private SellerProfile $profiles) {}

    public function edit(): void
    {
        $profile = $this->profiles->find();
        $old = Flash::pull('old', []);
        View::render('seller-profile/form', [
            'title' => 'Seller Profile', 'profile' => is_array($old) ? array_replace($profile, $old) : $profile,
            'errors' => Flash::pull('errors', []), 'csrf' => Csrf::token(),
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'error'),
        ]);
    }

    public function update(): void
    {
        $result = Validator::sellerProfile($_POST);
        if ($result['errors'] !== []) {
            Flash::set('old', $result['values']);
            Flash::set('errors', $result['errors']);
            $this->redirect();
        }
        try {
            $this->profiles->save($result['values']);
            Flash::set('message', 'Seller profile updated.');
            Flash::set('message_type', 'success');
        } catch (PDOException $error) {
            app_log_error($error);
            Flash::set('message', 'Unable to save seller profile. Please try again.');
            Flash::set('message_type', 'error');
        }
        $this->redirect();
    }

    private function redirect(): void
    {
        header('Location: ' . Router::baseUrl() . '/index.php?r=seller-profile/edit', true, 303);
        exit;
    }
}
