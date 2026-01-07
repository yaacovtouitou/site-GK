<?php

namespace App\Controller;

use App\Repository\BadgeRepository;
use App\Repository\CompletionRepository;
use App\Repository\MissionRepository;
use App\Service\GamificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        BadgeRepository $badgeRepository,
        CompletionRepository $completionRepository,
        MissionRepository $missionRepository,
        GamificationManager $gamificationManager
    ): Response
    {
        $user = $this->getUser();
        $allBadges = $badgeRepository->findAll();
        $userBadges = $user->getBadges();

        $badgesData = [];
        foreach ($allBadges as $badge) {
            $badgesData[] = [
                'name' => $badge->getName(),
                'description' => 'Description du badge', // Add description to Badge entity if needed
                'unlocked' => $userBadges->contains($badge),
                'icon' => 'trophy' // Add icon to Badge entity if needed
            ];
        }

        // Get Rank Info
        $rankInfo = $gamificationManager->getNextRankInfo($user);

        $stats = [
            ['label' => 'Points Totaux', 'value' => number_format($user->getTotalPoints()), 'color' => 'text-vibrant-orange'],
            ['label' => 'Niveau', 'value' => $user->getCurrentRank(), 'color' => 'text-gold'], // Display Rank Name instead of Level Number
            ['label' => 'Série de Jours', 'value' => $user->getLoginStreak() . ' 🔥', 'color' => 'text-royal-blue']
        ];

        // Calculate weekly missions progress
        $oneWeekAgo = new \DateTime('-7 days');
        $weeklyCompletions = $completionRepository->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.user = :user')
            ->andWhere('c.completedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $oneWeekAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $weeklyTarget = 15;

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'badges' => $badgesData,
            'stats' => $stats,
            'unlockedCount' => $userBadges->count(),
            'totalBadges' => count($allBadges),
            'weeklyCompletions' => $weeklyCompletions,
            'weeklyTarget' => $weeklyTarget,
            'rankInfo' => $rankInfo
        ]);
    }


    #[Route('/arabe')]
    public function easteregg()
    {
        return $this->redirect('https://www.pornhub.com');
    }
}
