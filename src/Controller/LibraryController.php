<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Service\GamificationManager;
use App\Service\HebraicCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class LibraryController extends AbstractController
{
    #[Route('/library', name: 'app_library')]
    public function index(DocumentRepository $documentRepository, HebraicCalendarService $calendarService): Response
    {
        // Structure of the Torah for progress calculation
        // Adjusted spellings to match Hebcal common output
        $torahBooks = [
            'Bereshit' => ['Bereshit', 'Noach', 'Lech-Lecha', 'Vayera', 'Chayei Sara', 'Toldot', 'Vayetzei', 'Vayishlach', 'Vayeshev', 'Miketz', 'Vayigash', 'Vayechi'],
            'Shemot' => ['Shemot', 'Vaera', 'Bo', 'Beshalach', 'Yitro', 'Mishpatim', 'Terumah', 'Tetzaveh', 'Ki Tisa', 'Vayakhel', 'Pekudei'],
            'Vayikra' => ['Vayikra', 'Tzav', 'Shemini', 'Tazria', 'Metzora', 'Acharei Mot', 'Kedoshim', 'Emor', 'Behar', 'Bechukotai'],
            'Bamidbar' => ['Bamidbar', 'Nasso', 'Beha\'alotcha', 'Sh\'lach', 'Korach', 'Chukat', 'Balak', 'Pinchas', 'Matot', 'Masei'],
            'Devarim' => ['Devarim', 'Vaetchanan', 'Eikev', 'Re\'eh', 'Shoftim', 'Ki Teitzei', 'Ki Tavo', 'Nitzavim', 'Vayelech', 'Ha\'Azinu', 'V\'Zot HaBerachah'],
        ];

        // Get current Paracha from Hebcal via Service
        $parachaInfo = $calendarService->getCurrentParacha();
        $currentParachaName = $parachaInfo['name']; // Ex: "Vaera"

        // Normalize for comparison (remove accents, lowercase, apostrophes, hyphens)
        $normalizedCurrent = $this->normalizeString($currentParachaName);

        // Determine current book and progress
        $currentBook = 'Bereshit'; // Default
        $completedParachiotCount = 0;
        $totalParachiotCount = 0;
        $foundCurrent = false;

        foreach ($torahBooks as $book => $parachiot) {
            $totalParachiotCount += count($parachiot);

            if ($foundCurrent) {
                continue;
            }

            // Check if current paracha is in this book
            $position = -1;
            foreach ($parachiot as $index => $p) {
                // Check for exact match or normalized match
                if ($this->normalizeString($p) === $normalizedCurrent || $p === $currentParachaName) {
                    $position = $index;
                    break;
                }
                // Handle special cases manually if normalization fails
                if ($normalizedCurrent === 'chemot' && $this->normalizeString($p) === 'shemot') {
                    $position = $index;
                    break;
                }
            }

            if ($position !== -1) {
                // Found it in this book!
                $currentBook = $book;
                $completedParachiotCount += $position; // Add previous parachiot in THIS book
                $foundCurrent = true;
            } else {
                // Not in this book, so this entire book is completed
                $completedParachiotCount += count($parachiot);
            }
        }

        // Fallback if not found
        if (!$foundCurrent) {
            $completedParachiotCount = 0;
            $currentBook = 'Bereshit';
        }

        // Calculate global progress
        $annualProgress = ($totalParachiotCount > 0) ? ($completedParachiotCount / $totalParachiotCount) * 100 : 0;

        // Use Local PDF File
        $filename = 'Géoula-Kids.pdf';
        $encodedFilename = rawurlencode($filename);
        $pdfPath = '/images/feuillet/' . $encodedFilename;

        $currentDocument = [
            'title' => 'Paracha ' . $currentParachaName,
            'pdfUrl' => $pdfPath,
            'downloadUrl' => $pdfPath,
            'pageCount' => 4
        ];

        // Prepare simple book counts for the template
        $torahBooksCounts = array_map('count', $torahBooks);

        return $this->render('library/index.html.twig', [
            'torahBooks' => $torahBooksCounts,
            'currentBook' => $currentBook,
            'annualProgress' => $annualProgress,
            'currentDocument' => $currentDocument,
            'currentParachaName' => $currentParachaName
        ]);
    }

    private function normalizeString(string $str): string
    {
        $str = strtolower($str);
        $str = str_replace(['é', 'è', 'ê', 'ë'], 'e', $str);
        $str = str_replace(['à', 'â'], 'a', $str);
        $str = str_replace(['ï', 'î'], 'i', $str);
        $str = str_replace(['ô'], 'o', $str);
        $str = str_replace(['ù', 'û'], 'u', $str);
        // Remove apostrophes, hyphens and non-alphanumeric characters
        $str = preg_replace('/[^a-z0-9]/', '', $str);
        return $str;
    }

    #[Route('/library/complete', name: 'app_library_complete')]
    public function complete(GamificationManager $gamificationManager, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $user->setTotalPoints($user->getTotalPoints() + 50);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', "Bravo ! Tu as terminé ta lecture et gagné 50 points ! 📖✨");

        return $this->redirectToRoute('app_library');
    }
}
