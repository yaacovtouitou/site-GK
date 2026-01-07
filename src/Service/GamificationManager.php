<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\Completion;
use App\Entity\Mission;
use App\Entity\User;
use App\Repository\BadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GamificationManager
{
    // Rank thresholds
    public const RANKS = [
        'Soldat' => 0,
        'Caporal' => 500,
        'Lieutenant' => 1000,
        'Capitaine' => 2500,
        'Général' => 5000
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private BadgeRepository $badgeRepository
    ) {}

    public function completeMission(User $user, Mission $mission): void
    {
        // Add points
        $pointsEarned = $mission->getPoints();
        $this->addPoints($user, $pointsEarned);

        // Record completion
        $completion = new Completion();
        $completion->setUser($user);
        $completion->setMission($mission);
        $completion->setCompletedAt(new \DateTime());
        $completion->setStatus('validated');

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        // Check for mission-related badges
        $this->checkMissionBadges($user);

        // Flash message
        $this->addFlashMessage($pointsEarned);
    }

    public function addPoints(User $user, int $points): void
    {
        $user->setTotalPoints($user->getTotalPoints() + $points);
        $this->checkRankUpgrade($user);
        $this->checkPointBadges($user);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function updateLoginStreak(User $user): void
    {
        $today = new \DateTime('today');
        $lastLogin = $user->getLastLoginDate();

        if (!$lastLogin) {
            $user->setLoginStreak(1);
        } else {
            $lastLoginDate = \DateTime::createFromInterface($lastLogin)->setTime(0, 0);
            $diff = $today->diff($lastLoginDate)->days;

            if ($diff === 1) {
                // Consecutive day
                $user->setLoginStreak($user->getLoginStreak() + 1);
            } elseif ($diff > 1) {
                // Streak broken
                $user->setLoginStreak(1);
            }
            // If diff === 0, same day, do nothing
        }

        $user->setLastLoginDate(new \DateTime());
        $this->checkStreakBadges($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Checks and updates the user's rank based on their total points.
     * Can be called manually to sync rank.
     */
    public function checkRankUpgrade(User $user): void
    {
        $points = $user->getTotalPoints();
        $newRank = 'Soldat';

        // Iterate through ranks to find the highest applicable one
        foreach (self::RANKS as $rank => $threshold) {
            if ($points >= $threshold) {
                $newRank = $rank;
            } else {
                // Since ranks are ordered by threshold, we can stop once we exceed the user's points
                // Actually, we need to continue to find the *highest* threshold met.
                // The array is ordered: Soldat(0), Caporal(500)...
                // If points=1000:
                // >=0 -> Soldat
                // >=500 -> Caporal
                // >=1000 -> Lieutenant
                // >=2500 -> False -> Break
                // Result: Lieutenant. Correct.
            }
        }

        if ($newRank !== $user->getCurrentRank()) {
            $user->setCurrentRank($newRank);
            // Only flash message if it's an upgrade action, but here we just sync.
            // We can persist here or let the caller do it.
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function getNextRankInfo(User $user): array
    {
        $points = $user->getTotalPoints();
        $currentRank = $user->getCurrentRank();

        // Ensure rank is synced before calculating next step
        // This fixes the issue where points are high but rank is low in DB
        $this->checkRankUpgrade($user);
        $currentRank = $user->getCurrentRank(); // Refresh after update

        $nextRank = null;
        $pointsNeeded = 0;
        $progress = 100; // Default if max rank

        $ranks = self::RANKS;
        $rankNames = array_keys($ranks);

        // Find current rank index
        $currentIndex = array_search($currentRank, $rankNames);

        if ($currentIndex !== false && isset($rankNames[$currentIndex + 1])) {
            $nextRank = $rankNames[$currentIndex + 1];
            $nextThreshold = $ranks[$nextRank];
            $currentThreshold = $ranks[$currentRank];

            $pointsNeeded = $nextThreshold - $points;

            // Calculate progress percentage within the current rank level
            $pointsInLevel = $points - $currentThreshold;
            $levelSpan = $nextThreshold - $currentThreshold;

            if ($levelSpan > 0) {
                $progress = ($pointsInLevel / $levelSpan) * 100;
            } else {
                $progress = 0;
            }
        }

        return [
            'nextRank' => $nextRank,
            'pointsNeeded' => $pointsNeeded,
            'progress' => $progress
        ];
    }

    private function checkStreakBadges(User $user): void
    {
        $streak = $user->getLoginStreak();
        if ($streak >= 7) {
            $this->awardBadge($user, 'Ami Fidèle');
        }
        if ($streak >= 30) {
            $this->awardBadge($user, 'Super Constant');
        }
    }

    private function checkPointBadges(User $user): void
    {
        $points = $user->getTotalPoints();
        if ($points >= 1000) {
            $this->awardBadge($user, 'Milliardaire de Mitzvot');
        }
    }

    private function checkMissionBadges(User $user): void
    {
        // Example logic: count completed missions
        $completedCount = $user->getCompletions()->count();
        if ($completedCount >= 10) {
            $this->awardBadge($user, 'Super Apprenant');
        }
    }

    private function awardBadge(User $user, string $badgeName): void
    {
        $badge = $this->badgeRepository->findOneBy(['name' => $badgeName]);
        if ($badge && !$user->getBadges()->contains($badge)) {
            $user->addBadge($badge);
            $this->requestStack->getSession()->getFlashBag()->add('success', "Nouveau Badge débloqué : $badgeName ! 🏆");
        }
    }

    private function addFlashMessage(int $points): void
    {
        $messages = [
            "Super travail ! Tu as gagné $points points ! 🚀",
            "Magnifique ! Continue comme ça ! +$points points ! ⭐",
            "Kol Hakavod ! Tu avances vers la Géoula ! +$points points ! 👑",
        ];

        $message = $messages[array_rand($messages)];
        $this->requestStack->getSession()->getFlashBag()->add('success', $message);
    }
}
