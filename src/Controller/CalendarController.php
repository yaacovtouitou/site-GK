<?php

namespace App\Controller;

use App\Entity\Badge;
use App\Repository\BadgeRepository;
use App\Service\GamificationManager;
use App\Service\HebraicCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar')]
    public function index(HebraicCalendarService $calendarService, BadgeRepository $badgeRepository): Response
    {
        $today = new \DateTime();
        $hebraicDate = $calendarService->getHebraicDate($today);
        $user = $this->getUser();

        // Mock events - In real app, fetch from DB or Service
        $upcomingEvents = [
            [
                'date' => '10 Chevat',
                'hebraic_day' => 10,
                'hebraic_month' => 'Chevat',
                'title' => 'Youd Chevat',
                'description' => 'Jour de Hiloula du Rabbi précédent',
                'badge' => '10 Chevat',
                'type' => 'chassidique',
                'auto_award' => false
            ],
            [
                'date' => '15 Chevat',
                'hebraic_day' => 15,
                'hebraic_month' => 'Chevat',
                'title' => 'Tou Bichevat',
                'description' => 'Nouvel an des arbres',
                'badge' => 'Tou Bichevat',
                'type' => 'fete',
                'auto_award' => false
            ],
            [
                'date' => '22 Chevat',
                'hebraic_day' => 22,
                'hebraic_month' => 'Chevat',
                'title' => 'haf Bet Chevat',
                'description' => 'Yahrzeit de la Rabbanit Haya Mouchka',
                'badge' => '22 Chevat',
                'type' => 'chassidique',
                'auto_award' => false
            ],
            [
                'date' => '14 Adar ',
                'hebraic_day' => 14,
                'hebraic_month' => 'Adar ',
                'title' => 'Pourim ',
                'description' => 'fete de Pourim ',
                'badge' => 'Pourim', // Reusing Pourim badge
                'type' => 'fete',
                'auto_award' => true // Example of auto award
            ]
        ];

        // Enrich events
        foreach ($upcomingEvents as &$event) {
            $event['badge_image'] = $this->getBadgeImage($event['badge']);

            // Check if user already has the badge
            $event['has_badge'] = false;
            if ($user) {
                foreach ($user->getBadges() as $userBadge) {
                    if ($userBadge->getName() === $event['badge']) {
                        $event['has_badge'] = true;
                        break;
                    }
                }
            }

            // Check if today is the day to claim
            // Note: This comparison depends on exact string matching with API response
            // For MVP, we assume it matches or we force it for demo if needed
            $isToday = ($hebraicDate['day'] == $event['hebraic_day'] &&
                        (str_contains($hebraicDate['month'], $event['hebraic_month']) || $hebraicDate['month'] == $event['hebraic_month']));

            $event['can_claim'] = $isToday && !$event['has_badge'] && !$event['auto_award'];
        }

        return $this->render('calendar/index.html.twig', [
            'hebraicDate' => $hebraicDate,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    #[Route('/calendar/claim/{badgeName}', name: 'app_calendar_claim')]
    #[IsGranted('ROLE_USER')]
    public function claim(
        string $badgeName,
        BadgeRepository $badgeRepository,
        GamificationManager $gamificationManager,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        $badge = $badgeRepository->findOneBy(['name' => $badgeName]);

        if (!$badge) {
            // Create badge on the fly if it doesn't exist (for MVP simplicity)
            $badge = new Badge();
            $badge->setName($badgeName);
            $badge->setDescription("Badge obtenu le " . date('d/m/Y'));
            $badge->setIcon('calendar');
            // Try to find image
            $imagePath = $this->getBadgeImage($badgeName);
            if ($imagePath) {
                $badge->setImage($imagePath);
            }
            $em->persist($badge);
            $em->flush();
        }

        if ($user->getBadges()->contains($badge)) {
            $this->addFlash('warning', 'Tu as déjà ce badge !');
        } else {
            $user->addBadge($badge);
            // Add bonus points for claiming
            $gamificationManager->addPoints($user, 50);
            $em->flush();
            $this->addFlash('success', "Bravo ! Tu as récupéré le badge $badgeName et 50 points !");
        }

        return $this->redirectToRoute('app_calendar');
    }

    private function getBadgeImage(string $badgeName): ?string
    {
        $filename = strtolower(str_replace(' ', '-', $badgeName)) . '.png';
        $publicDir = $this->getParameter('kernel.project_dir') . '/public/images/badge/';

        if (file_exists($publicDir . $filename)) {
            return '/images/badge/' . $filename;
        }
        return null;
    }
}
