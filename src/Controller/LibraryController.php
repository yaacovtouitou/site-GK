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
        $torahBooks = [
            'Bereshit' => ['Bereshit', 'Noach', 'Lech Lecha', 'Vayera', 'Chayei Sarah', 'Toldot', 'Vayetze', 'Vayishlach', 'Vayeshev', 'Miketz', 'Vayigash', 'Vayechi'],
            'Shemot' => ['Shemot', 'Va\'eira', 'Bo', 'Beshalach', 'Yitro', 'Mishpatim', 'Terumah', 'Tetzaveh', 'Ki Tisa', 'Vayakhel', 'Pekudei'],
            'Vayikra' => ['Vayikra', 'Tzav', 'Shemini', 'Tazria', 'Metzora', 'Acharei Mot', 'Kedoshim', 'Emor', 'Behar', 'Bechukotai'],
            'Bamidbar' => ['Bamidbar', 'Naso', 'Behaalotecha', 'Shlach', 'Korach', 'Chukat', 'Balak', 'Pinchas', 'Matot', 'Masei'],
            'Devarim' => ['Devarim', 'Vaetchanan', 'Eikev', 'Re\'eh', 'Shoftim', 'Ki Teitzei', 'Ki Tavo', 'Nitzavim', 'Vayelech', 'Haazinu', 'V\'Zot HaBerachah'],
        ];

        // Get current Paracha from Hebcal via Service
        $parachaInfo = $calendarService->getCurrentParacha();
        $currentParachaName = $parachaInfo['name']; // Ex: "Chemot" from RSS

        // Normalize for comparison (remove accents, lowercase)
        $normalizedCurrent = $this->normalizeString($currentParachaName);

        // Determine current book and progress
        $currentBook = 'Bereshit'; // Default
        $completedParachiotCount = 0;
        $totalParachiotCount = 0;
        $foundCurrent = false;

        foreach ($torahBooks as $book => $parachiot) {
            $totalParachiotCount += count($parachiot);

            if ($foundCurrent) {
                // If we already found the current paracha in a previous book,
                // subsequent books contribute 0 to completed count.
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
                // Handle special cases like "Chemot" vs "Shemot"
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

        // Fallback if not found (e.g. spelling mismatch), default to beginning
        if (!$foundCurrent) {
            // Reset to 0 or handle error. Let's assume Bereshit 1 if not found.
            $completedParachiotCount = 0;
            $currentBook = 'Bereshit';
        }

        // Calculate global progress
        $annualProgress = ($totalParachiotCount > 0) ? ($completedParachiotCount / $totalParachiotCount) * 100 : 0;

        // Use Local PDF File
        $currentDocument = [
            'title' => 'Paracha ' . $currentParachaName,
            'pdfUrl' => '/images/feuillet/Géoula Kids.pdf', // Local path
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
        $str = str_replace(['é', 'è', 'ê'], 'e', $str);
        $str = str_replace(['à', 'â'], 'a', $str);
        // Add more replacements if needed
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
