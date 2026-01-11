<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\GamificationManager;
use App\Service\HebraicCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GamingZoneController extends AbstractController
{
    #[Route('/gaming-zone', name: 'app_gaming_zone')]
    public function index(
        UserRepository $userRepository,
        HebraicCalendarService $calendarService
    ): Response
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

        // Calculate current user rank
        $userRank = 0;
        $pointsToPodium = 0;
        $allUsers = $userRepository->findBy([], ['totalPoints' => 'DESC']);
        foreach ($allUsers as $index => $user) {
            if ($user === $currentUser) {
                $userRank = $index + 1;
                break;
            }
        }

        if ($userRank > 3 && isset($allUsers[2])) {
            $thirdPlacePoints = $allUsers[2]->getTotalPoints();
            $pointsToPodium = $thirdPlacePoints - $currentUser->getTotalPoints() + 1;
        }

        // Get current Paracha for the weekly game
        $parachaInfo = $calendarService->getCurrentParacha();
        $currentParacha = $parachaInfo['name'];

        $games = [
            [
                'id' => 'paracha-quiz',
                'title' => 'Quiz ' . $currentParacha,
                'description' => 'Teste tes connaissances sur la Paracha de la semaine !',
                'icon' => 'book',
                'color' => 'from-vibrant-orange to-gold',
                'type' => 'Hebdomadaire',
                'points' => 100,
                'energy_cost' => 20
            ],
            [
                'id' => 'torah-quest',
                'title' => 'Casher ou Pas ?',
                'description' => 'Aventure interactive dans les histoires de la Torah',
                'icon' => 'gamepad',
                'color' => 'from-purple-600 to-pink-600',
                'type' => 'Permanent',
                'points' => 50,
                'energy_cost' => 10
            ],
            [
                'id' => 'geoula-run',
                'title' => 'Géoula Run',
                'description' => 'Cours, saute et attrape les Mitzvot ! Évite les obstacles.',
                'icon' => 'run', // Custom icon logic needed in template
                'color' => 'from-blue-500 to-cyan-500',
                'type' => 'Permanent',
                'points' => 150, // High score potential
                'energy_cost' => 15
            ]
        ];

        return $this->render('gaming_zone/index.html.twig', [
            'leaderboard' => $leaderboard,
            'games' => $games,
            'userRank' => $userRank,
            'pointsToPodium' => $pointsToPodium,
            'userEnergy' => $currentUser->getEnergy()
        ]);
    }

    #[Route('/gaming-zone/play/torah-quest', name: 'app_gaming_play_torah_quest')]
    public function playTorahQuest(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $energyCost = 10;

        if ($user->getEnergy() < $energyCost) {
            $this->addFlash('error', "Tu n'as plus assez d'énergie !");
            return $this->redirectToRoute('app_gaming_zone');
        }

        $user->setEnergy($user->getEnergy() - $energyCost);
        $em->persist($user);
        $em->flush();

        return $this->render('gaming_zone/torah_quest.html.twig');
    }

    #[Route('/gaming-zone/play/geoula-run', name: 'app_gaming_play_geoula_run')]
    public function playGeoulaRun(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $energyCost = 15;

        if ($user->getEnergy() < $energyCost) {
            $this->addFlash('error', "Tu n'as plus assez d'énergie !");
            return $this->redirectToRoute('app_gaming_zone');
        }

        $user->setEnergy($user->getEnergy() - $energyCost);
        $em->persist($user);
        $em->flush();

        return $this->render('gaming_zone/geoula_run.html.twig');
    }

    #[Route('/gaming-zone/save-score/{gameId}', name: 'app_gaming_save_score', methods: ['POST'])]
    public function saveScore(string $gameId, Request $request, GamificationManager $gamificationManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not logged in'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $score = $data['score'] ?? 0;

        if ($score > 0) {
            $pointsEarned = 0;

            if ($gameId === 'torah-quest') {
                $pointsEarned = (int) ceil($score / 10);
            } elseif ($gameId === 'geoula-run') {
                // Geoula Run score is distance/points collected.
                // Let's say 1 point per 50 score in game
                $pointsEarned = (int) ceil($score / 50);
            } else {
                $pointsEarned = 10; // Default
            }

            $gamificationManager->addPoints($user, $pointsEarned);
        }

        return $this->json(['success' => true, 'pointsEarned' => $pointsEarned ?? 0]);
    }

    // Deprecated route kept for compatibility
    #[Route('/gaming-zone/complete/{gameId}', name: 'app_gaming_complete')]
    public function completeGame(string $gameId): Response
    {
        if ($gameId === 'torah-quest') {
            return $this->redirectToRoute('app_gaming_play_torah_quest');
        }
        if ($gameId === 'geoula-run') {
            return $this->redirectToRoute('app_gaming_play_geoula_run');
        }

        return $this->redirectToRoute('app_gaming_zone');
    }
}
