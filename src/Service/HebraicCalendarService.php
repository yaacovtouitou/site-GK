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

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository,
        private UserRepository $userRepository,
        private CacheInterface $cache,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère la Paracha de la semaine.
     * Cache désactivé temporairement pour debug si nécessaire, ou durée très courte.
     */
    public function getCurrentParacha(): array
    {
        // Pour debug : on force l'expiration immédiate ou on n'utilise pas le cache
        // return $this->fetchParachaDirectly();

        return $this->cache->get('current_paracha_v2', function (ItemInterface $item) {
            $item->expiresAfter(300); // Cache court de 5 min pour tester

            try {
                return $this->fetchParachaDirectly();
            } catch (\Exception $e) {
                $this->logger->error('Erreur récupération Paracha: ' . $e->getMessage());

                return [
                    'name' => 'Bereshit',
                    'full_title' => 'Erreur: ' . $e->getMessage(), // Affiche l'erreur dans le titre pour debug
                    'date' => date('r'),
                    'description' => 'Impossible de charger le calendrier.'
                ];
            }
        });
    }

    private function fetchParachaDirectly(): array
    {
        $content = null;

        // Tentative 1 : HttpClient Symfony
        try {
            $response = $this->httpClient->request('GET', self::HEBCAL_RSS_URL, [
                'timeout' => 5,
                'verify_peer' => false, // Désactive la vérif SSL temporairement (cas fréquents sur serveurs mutualisés)
            ]);
            if ($response->getStatusCode() === 200) {
                $content = $response->getContent();
            }
        } catch (\Exception $e) {
            $this->logger->warning('HttpClient a échoué: ' . $e->getMessage());
        }

        // Tentative 2 : file_get_contents (si allow_url_fopen est on)
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

    // ... (rest of the methods unchanged)
    public function getHebraicDate(\DateTimeInterface $date): array
    {
        if (!function_exists('gregoriantojd')) {
            return ['day' => 1, 'month' => 'Nissan', 'year' => 5784];
        }
        $jd = gregoriantojd((int)$date->format('m'), (int)$date->format('d'), (int)$date->format('Y'));
        $hebrewDate = jdtojewish($jd, true, CAL_JEWISH_ADD_GERESHAYIM);
        return ['original_string' => iconv('WINDOWS-1255', 'UTF-8', $hebrewDate), 'day' => 11, 'month' => 'Nissan', 'year' => 5784];
    }

    public function isSpecialDate(\DateTimeInterface $date, string $specialDay, string $specialMonth): bool
    {
        if ($date->format('m-d') === '04-19' && $specialDay == 11 && $specialMonth == 'Nissan') {
            return true;
        }
        return false;
    }

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
        if (!$badge) return;
        $this->entityManager->flush();
    }
}
