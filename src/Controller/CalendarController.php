<?php

namespace App\Controller;

use App\Repository\BadgeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar')]
    public function index(BadgeRepository $badgeRepository): Response
    {
        $user = $this->getUser();
        $userBadges = $user->getBadges();

        // Mock events data (could be moved to DB later)
        // We map the 'collectorBadge' name to real badges in DB if they exist
        $events = [
            [
                'id' => '1',
                'hebrewDate' => 'Yud Aleph Nissan',
                'gregorianDate' => '11 Avril 2026',
                'title' => 'Anniversaire du Rabbi',
                'description' => 'Célébrons l\'anniversaire du Rebbe avec des histoires, chants et activités spéciales',
                'category' => 'chassidic',
                'badgeName' => 'Étoile Nissan',
                'badgeIcon' => '⭐'
            ],
            [
                'id' => '2',
                'hebrewDate' => 'Youd Teth Kislev',
                'gregorianDate' => '19 Décembre 2025',
                'title' => 'Roch Hachana de la Hassidout',
                'description' => 'Jour de libération et de célébration de la lumière chassidique',
                'category' => 'chassidic',
                'badgeName' => 'Flamme de Kislev',
                'badgeIcon' => '🕯️'
            ],
            [
                'id' => '3',
                'hebrewDate' => 'Roch Hachana',
                'gregorianDate' => '23-24 Septembre 2025',
                'title' => 'Nouvel An Juif',
                'description' => 'Début de la nouvelle année avec prières et réflexions',
                'category' => 'yom-tov',
                'badgeName' => 'Couronne Royale',
                'badgeIcon' => '👑'
            ],
            [
                'id' => '4',
                'hebrewDate' => 'Yom Kippour',
                'gregorianDate' => '2 Octobre 2025',
                'title' => 'Jour du Grand Pardon',
                'description' => 'Jour sacré de jeûne et de prière',
                'category' => 'yom-tov',
                'badgeName' => 'Lumière Pure',
                'badgeIcon' => '✨'
            ],
            [
                'id' => '5',
                'hebrewDate' => 'Hanouka',
                'gregorianDate' => '25 Décembre 2025 - 1 Janvier 2026',
                'title' => 'Fête des Lumières',
                'description' => 'Huit jours de miracle et de lumière',
                'category' => 'yom-tov',
                'badgeName' => 'Menorah d\'Or',
                'badgeIcon' => '🕎'
            ],
            [
                'id' => '6',
                'hebrewDate' => 'Pourim',
                'gregorianDate' => '14 Mars 2026',
                'title' => 'Fête de la Joie',
                'description' => 'Célébration du miracle de Pourim avec joie et partage',
                'category' => 'yom-tov',
                'badgeName' => 'Masque Joyeux',
                'badgeIcon' => '🎭'
            ]
        ];

        // Process events to check badge status against DB
        $processedEvents = [];
        $totalCollectorBadges = 0;
        $unlockedCollectorBadges = 0;

        foreach ($events as $event) {
            $isUnlocked = false;

            // Check if user has this badge
            foreach ($userBadges as $userBadge) {
                if ($userBadge->getName() === $event['badgeName']) {
                    $isUnlocked = true;
                    break;
                }
            }

            if ($event['badgeName']) {
                $totalCollectorBadges++;
                if ($isUnlocked) {
                    $unlockedCollectorBadges++;
                }
            }

            $event['collectorBadge'] = [
                'name' => $event['badgeName'],
                'icon' => $event['badgeIcon'],
                'unlocked' => $isUnlocked
            ];

            $processedEvents[] = $event;
        }

        // Get next 4 events
        $upcomingEvents = array_slice($processedEvents, 0, 4);

        return $this->render('calendar/index.html.twig', [
            'upcomingEvents' => $upcomingEvents,
            'unlockedBadgesCount' => $unlockedCollectorBadges,
            'totalEvents' => $totalCollectorBadges // Total badges available in calendar
        ]);
    }
}
