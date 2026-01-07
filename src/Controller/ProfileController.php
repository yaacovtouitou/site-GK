<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @deprecated This controller is deprecated and replaced by DashboardController.
 * Keeping it here to avoid autoloader errors until file deletion.
 */
class ProfileController extends AbstractController
{
    #[Route('/profile-deprecated', name: 'app_profile_deprecated')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_dashboard');
    }
}
