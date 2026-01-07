<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\GamificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GamingZoneController extends AbstractController
{
    #[Route('/gaming-zone', name: 'app_gaming_zone')]
    public function index(UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();

        // Real leaderboard data (Top 10)
        $topUsers = $userRepository->findBy([], ['totalPoints' => 'DESC'], 10);

        $leaderboard = [];
        foreach ($topUsers as $index => $user) {
            $leaderboard[] = [
                'rank' => $index + 1,
                'name' => $user->getPseudo(),
                'score' => $user->getTotalPoints(),
                'badges' => $user->getBadges()->count(),
                'avatar' => $user->getAvatar() ? $user->getAvatar() : strtoupper(substr($user->getPseudo(), 0, 1))
            ];
        }

        // Calculate current user rank and points to next goal
        $userRank = 0;
        $pointsToPodium = 0;

        // Find user rank in the whole list (not just top 10)
        // For optimization in a real app with many users, use a custom repository query
        $allUsers = $userRepository->findBy([], ['totalPoints' => 'DESC']);
        foreach ($allUsers as $index => $user) {
            if ($user === $currentUser) {
                $userRank = $index + 1;
                break;
            }
        }

        // Calculate points needed for Top 3
        if ($userRank > 3 && isset($allUsers[2])) { // Index 2 is the 3rd user
            $thirdPlacePoints = $allUsers[2]->getTotalPoints();
            $pointsToPodium = $thirdPlacePoints - $currentUser->getTotalPoints() + 1; // +1 to beat them
        }

        $games = [
            [
                'id' => 'torah-quest',
                'title' => 'Torah Quest',
                'description' => 'Aventure interactive dans les histoires de la Torah',
                'icon' => 'gamepad',
                'color' => 'from-purple-600 to-pink-600',
                'difficulty' => 'Moyen'
            ]
        ];

        return $this->render('gaming_zone/index.html.twig', [
            'leaderboard' => $leaderboard,
            'games' => $games,
            'userRank' => $userRank,
            'pointsToPodium' => $pointsToPodium
        ]);
    }

    #[Route('/gaming-zone/complete/{gameId}', name: 'app_gaming_complete')]
    public function completeGame(string $gameId, GamificationManager $gamificationManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Simulate game completion logic
        $points = 50; // Points for completing a game session
        $gamificationManager->addPoints($user, $points);

        $this->addFlash('success', "Partie terminée ! Tu as gagné $points points ! 🎮");

        return $this->redirectToRoute('app_gaming_zone');
    }
}
