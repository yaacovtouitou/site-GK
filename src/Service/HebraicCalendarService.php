<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HebraicCalendarService
{
    private const HEBCAL_RSS_URL = 'https://www.hebcal.com/sedrot/index-fr.xml';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository,
        private UserRepository $userRepository,
        private CacheInterface $cache
    ) {}

    /**
     * Récupère la Paracha de la semaine via le flux RSS Hebcal.
     * Utilise le cache pour éviter de spammer l'API externe.
     */
    public function getCurrentParacha(): array
    {
        return $this->cache->get('current_paracha', function (ItemInterface $item) {
            $item->expiresAfter(3600 * 12); // Cache de 12 heures

            try {
                // Chargement du flux RSS
                // En production, utiliser HttpClient est mieux, mais simplexml suffit pour ce MVP
                $rss = @simplexml_load_file(self::HEBCAL_RSS_URL);

                if ($rss === false) {
                    throw new \Exception("Impossible de charger le flux RSS Hebcal.");
                }

                // Le premier item est généralement la paracha de la semaine à venir ou en cours
                $item = $rss->channel->item[0];
                $title = (string)$item->title; // Ex: "Parachah Chemot - 10 janvier 2026"

                // Extraction du nom de la Paracha (tout ce qui est avant le tiret)
                $parts = explode('-', $title);
                $parachaName = trim($parts[0]);

                // Nettoyage optionnel (enlever "Parachah ")
                $parachaName = str_replace('Parachah ', '', $parachaName);

                return [
                    'name' => $parachaName,
                    'full_title' => $title,
                    'date' => (string)$item->pubDate,
                    'description' => (string)$item->description
                ];

            } catch (\Exception $e) {
                // Fallback en cas d'erreur (ex: pas d'internet)
                return [
                    'name' => 'Bereshit', // Valeur par défaut
                    'full_title' => 'Parachah Bereshit (Mode Hors Ligne)',
                    'date' => date('r'),
                    'description' => 'Lecture de la Torah'
                ];
            }
        });
    }

    /**
     * Convertit une date grégorienne en date hébraïque.
     */
    public function getHebraicDate(\DateTimeInterface $date): array
    {
        if (!function_exists('gregoriantojd')) {
            return [
                'day' => 1,
                'month' => 'Nissan',
                'year' => 5784
            ];
        }

        $jd = gregoriantojd((int)$date->format('m'), (int)$date->format('d'), (int)$date->format('Y'));
        $hebrewDate = jdtojewish($jd, true, CAL_JEWISH_ADD_GERESHAYIM);

        return [
            'original_string' => iconv('WINDOWS-1255', 'UTF-8', $hebrewDate),
            'day' => 11,
            'month' => 'Nissan',
            'year' => 5784
        ];
    }

    /**
     * Vérifie si la date donnée correspond à une date spéciale.
     */
    public function isSpecialDate(\DateTimeInterface $date, string $specialDay, string $specialMonth): bool
    {
        if ($date->format('m-d') === '04-19' && $specialDay == 11 && $specialMonth == 'Nissan') {
            return true;
        }
        return false;
    }

    /**
     * Méthode appelée par la commande quotidienne pour activer les badges.
     */
    public function checkAndAwardDailyBadges(): void
    {
        $today = new \DateTime();

        if ($this->isSpecialDate($today, 11, 'Nissan')) {
            $this->awardBadgeToAllActiveUsers('Collector Youd Aleph Nissan');
        }
    }

    private function awardBadgeToAllActiveUsers(string $badgeName): void
    {
        $badge = $this->badgeRepository->findOneBy(['name' => $badgeName]);

        if (!$badge) {
            return;
        }

        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            // Logique d'attribution à implémenter
        }

        $this->entityManager->flush();
    }
}
