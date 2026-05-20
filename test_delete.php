<?php
require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\PageSection;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Fetch IDs of all sections of page 2
$sectionIds = [3, 4, 5, 6, 7, 8];

echo "Found " . count($sectionIds) . " sections on page 2 to test.\n";

foreach ($sectionIds as $id) {
    $section = $em->getRepository(PageSection::class)->find($id);
    if (!$section) {
        echo "Section ID $id not found, skipping.\n";
        continue;
    }
    $title = ($section->getTitlePart1() ?? '') . " " . ($section->getTitlePart2() ?? '');
    echo "--------------------------------------------------\n";
    echo "Testing deletion of Section ID $id: \"$title\"\n";
    
    $em->getConnection()->beginTransaction();
    try {
        $em->remove($section);
        $em->flush();
        echo "--> SUCCESS: Deletion of Section ID $id was successful!\n";
        $em->getConnection()->rollBack();
    } catch (\Throwable $e) {
        echo "--> ERROR during deletion of Section ID $id:\n";
        echo get_class($e) . ": " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
        echo "Trace snippet:\n" . substr($e->getTraceAsString(), 0, 500) . "\n";
        try {
            $em->getConnection()->rollBack();
        } catch (\Throwable $rollbackEx) {
            echo "Could not rollback transaction: " . $rollbackEx->getMessage() . "\n";
        }
    }
    $em->clear(); // Clear identity map
}
