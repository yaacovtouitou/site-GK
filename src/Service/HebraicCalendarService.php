<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HebraicCalendarService
{
    private const HEBCAL_RSS_URL = 'https://www.hebcal.com/sedrot/index-fr.xml';
    private const HEBCAL_CONVERTER_URL = 'https://www.hebcal.com/converter';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository,
        private UserRepository $userRepository,
        private CacheInterface $cache,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function getCurrentParacha(): array
    {
        return $this->cache->get('current_paracha_v2', function (ItemInterface $item) {
            $item->expiresAfter(300);

            try {
                return $this->fetchParachaDirectly();
            } catch (\Exception $e) {
                $this->logger->error('Erreur récupération Paracha: ' . $e->getMessage());

                return [
                    'name' => 'Bereshit',
                    'full_title' => 'Erreur: ' . $e->getMessage(),
                    'date' => date('r'),
                    'description' => 'Impossible de charger le calendrier.'
                ];
            }
        });
    }

    private function fetchParachaDirectly(): array
    {
        $content = null;

        try {
            $response = $this->httpClient->request('GET', self::HEBCAL_RSS_URL, [
                'timeout' => 5,
                'verify_peer' => false,
            ]);
            if ($response->getStatusCode() === 200) {
                $content = $response->getContent();
            }
        } catch (\Exception $e) {
            $this->logger->warning('HttpClient a échoué: ' . $e->getMessage());
        }

        if (!$content && ini_get('allow_url_fopen')) {
            $content = @file_get_contents(self::HEBCAL_RSS_URL);
        }

        if (!$content) {
            throw new \Exception("Toutes les méthodes de connexion ont échoué.");
        }

        $rss = @simplexml_load_string($content);
        if ($rss === false) {
            throw new \Exception("XML invalide ou vide.");
        }

        $rssItem = $rss->channel->item[0];
        $title = (string)$rssItem->title;

        $parts = explode('-', $title);
        $parachaName = trim($parts[0]);
        $parachaName = str_replace('Parachah ', '', $parachaName);

        return [
            'name' => $parachaName,
            'full_title' => $title,
            'date' => (string)$rssItem->pubDate,
            'description' => (string)$rssItem->description
        ];
    }

    /**
     * Convertit une date grégorienne en date hébraïque via l'API Hebcal.
     */
    public function getHebraicDate(\DateTimeInterface $date): array
    {
        // Cache for 24h since date doesn't change often
        return $this->cache->get('hebraic_date_' . $date->format('Y-m-d'), function (ItemInterface $item) use ($date) {
            $item->expiresAfter(3600 * 24);

            try {
                $response = $this->httpClient->request('GET', self::HEBCAL_CONVERTER_URL, [
                    'query' => [
                        'cfg' => 'json',
                        'gy' => $date->format('Y'),
                        'gm' => $date->format('m'),
                        'gd' => $date->format('d'),
                        'g2h' => 1
                    ],
                    'timeout' => 5,
                    'verify_peer' => false
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();

                    // Translate month manually or use Hebcal's transliteration if available
                    // Hebcal returns "Nisan", we want "Nissan" or French
                    $month = $data['hm'];
                    $day = $data['hd'];
                    $year = $data['hy'];
                    $hebrew = $data['hebrew'];

                    return [
                        'day' => $day,
                        'month' => $month,
                        'year' => $year,
                        'original_string' => $hebrew,
                        'full_string' => "$day $month $year"
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur conversion date Hebcal: ' . $e->getMessage());
            }

            // Fallback to PHP native if API fails
            if (function_exists('gregoriantojd')) {
                $jd = gregoriantojd((int)$date->format('m'), (int)$date->format('d'), (int)$date->format('Y'));
                $hebrewDate = jdtojewish($jd, true, CAL_JEWISH_ADD_GERESHAYIM);
                // Parse string like "2/11/5784" or similar depending on locale
                // This is rough, API is better
                return [
                    'day' => 1,
                    'month' => 'Nissan (Fallback)',
                    'year' => 5784,
                    'original_string' => $hebrewDate
                ];
            }

            return ['day' => 1, 'month' => 'Nissan', 'year' => 5784];
        });
    }

    public function isSpecialDate(\DateTimeInterface $date, string $specialDay, string $specialMonth): bool
    {
        // This logic needs to use the hebraic date, not gregorian
        $hebraic = $this->getHebraicDate($date);
        return $hebraic['day'] == $specialDay && $hebraic['month'] == $specialMonth;
    }

    public function checkAndAwardDailyBadges(): void
    {
        $today = new \DateTime();
        // Example: Check for 11 Nissan
        $hebraic = $this->getHebraicDate($today);

        if ($hebraic['day'] == 11 && $hebraic['month'] == 'Nisan') { // Hebcal uses 'Nisan'
            $this->awardBadgeToAllActiveUsers('Collector Youd Aleph Nissan');
        }
    }

    private function awardBadgeToAllActiveUsers(string $badgeName): void
    {
        $badge = $this->badgeRepository->findOneBy(['name' => $badgeName]);
        if (!$badge) return;
        $this->entityManager->flush();
    }
}
