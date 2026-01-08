<?php

namespace App\Controller\Api;

use App\Entity\Completion;
use App\Entity\Mission; // We might need a dummy mission or a specific entity for reading logs
use App\Repository\MissionRepository;
use App\Service\GamificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/api/library')]
class LibraryApiController extends AbstractController
{
    #[Route('/complete', name: 'api_library_complete', methods: ['POST'])]
    public function complete(
        EntityManagerInterface $em,
        GamificationManager $gamificationManager
    ): JsonResponse
    {
        $user = $this->getUser();
        $pointsEarned = 50; // Points for reading a Paracha

        // Add points using the manager (handles rank upgrades etc.)
        $gamificationManager->addPoints($user, $pointsEarned);

        // Optional: Log this action in Completion table if you want to limit it to once per week
        // For now, we just award points as requested.

        return $this->json([
            'success' => true,
            'pointsEarned' => $pointsEarned,
            'totalPoints' => $user->getTotalPoints(),
            'message' => "Hazak ! Tu as gagné $pointsEarned points !"
        ]);
    }
}
