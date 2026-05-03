<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Dompdf\{Dompdf, Options};
use Twig\Environment;
use App\Entity\BatchExecution;

/**
 * [Description PdfExportService]
 */
class PdfExportService
{

    public function __construct(
        private Environment $twig,
        private ParameterBagInterface $params,
)
    {
    }

    /**
     * [Description for generateRapportPdf]
     * Génère un PDF pour un BatchExecution
     *
     * @param BatchExecution $batchExecution
     * @param string|null $document_type
     *
     * @return string
     *
     * Created at: 13/11/2025 21:05:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateRapportPdf(BatchExecution $batchExecution, ?string $document_type = null): string
    {
        // Préparer les données pour le template
        $collectes = $batchExecution->getCollectes()->map(function($journal) {
            return [
                'id' => $journal->getId(),
                'nomProjet' => $journal->getNomProjet(),
                'portefeuille' => $journal->getPortefeuille(),
                'code' => $journal->getCode(),
                'dateExecution' => $journal->getDateExecution()->format('d/m/Y H:i'),
            ];
        })->toArray();

        $batchData = [
            'id' => $batchExecution->getId(),
            'nomTraitement' => $batchExecution->getNomTraitement(),
            'executionId' => $batchExecution->getExecutionId()?->toRfc4122(),
            'traitementId' => $batchExecution->getTraitementId()?->toRfc4122(),
            'modeCollecte' => $batchExecution->getModeCollecte(),
            'utilisateurCollecte' => $batchExecution->getUtilisateurCollecte(),
            'dateEnregistrement' => $batchExecution->getDateEnregistrement()->format('d/m/Y H:i'),
            'collectes' => $collectes,
        ];

        // Configurer Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // Gère UTF-8
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        // --- Génération du HTML ---
        $html = $this->twig->render('/rapport/rapport-zurb-foundation.html.twig', [
            'batch' => $batchData,
            'logoBase64' => base64_encode(file_get_contents($this->params->get('kernel.project_dir').'/assets/images/marque-mm-400x128.png')),
            'document_type' => $document_type ?? 'Document confidentiel',
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        // --- Rendu principal ---
        $dompdf->render();

       // --- Après $dompdf->render() ---
        $canvas = $dompdf->getCanvas();
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $fontSize = 9;

        // Police
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');

        // Coordonnée Y du footer (depuis le haut)
        $y = $pageHeight - 30;

        // Couleur ligne et texte (RGB float 0-1)
        $colorLine = [0/255, 68/255, 91/255]; // #00445b
        $colorText = [0/255, 68/255, 91/255]; // #00445b

        // Ligne horizontale sur toutes les pages
        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageWidth, $y, $document_type, $colorLine, $colorText, $font, $fontSize) {
            // Ligne
            $canvas->line(40, $y - 5, $pageWidth - 40, $y - 5, $colorLine, 0.5);

            // Date à gauche
            $dateText = 'Généré le ' . (new \DateTimeImmutable())->format('d/m/Y');
            $canvas->text(40, $y, $dateText, $font, $fontSize, $colorText);

            // Mention au centre (variable $mention passée dans le template)
            $mention = $document_type ?? 'Document public';
            $textWidth = $fontMetrics->getTextWidth($mention, $font, $fontSize);
            $xCenter = ($pageWidth - $textWidth) / 2;
            $canvas->text($xCenter, $y, $mention, $font, $fontSize, $colorText);

            // Numéro de page à droite
            $pageText = sprintf('Page %d', $pageNumber);
            $textWidthRight = $fontMetrics->getTextWidth($pageText, $font, $fontSize);
            $xRight = $pageWidth - 40 - $textWidthRight;
            $canvas->text($xRight, $y, $pageText, $font, $fontSize, $colorText);
        });

        // --- Générer le PDF ---
        return $dompdf->output();
    }

    /**
     * [Description for generateOwaspPdf]
     * Génère un rapport PDF de la page OWASP (information projet + synthèse
     * vulnérabilités/hotspots + liste détaillée des menaces + graphique de
     * répartition par catégorie A1..A10). Réutilise le pattern dompdf +
     * page_script footer de generateRapportPdf.
     *
     * @param array<string, mixed> $data Données agrégées par le controller :
     *   - maven_key, project_name, version, date_version, referential_owasp
     *   - vulnerabilities : { total, blocker, critical, major, minor }
     *   - hotspots : { total, high, medium, low, reviewed, to_review, note }
     *   - repartition : { frontend, backend, autre, inconnu }
     *   - categories : [{ id, label, faille, hotspot }] pour A1..A10
     *   - menaces : [{ rule, severity, component, ligne, message, status, frontend, backend, autre }]
     * @param string|null $document_type Mention "Document confidentiel" en footer
     *
     * Created at: 2026-05-03 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateOwaspPdf(array $data, ?string $document_type = null): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render('/rapport/rapport-owasp.html.twig', [
            'rapport' => $data,
            'logoBase64' => base64_encode(file_get_contents($this->params->get('kernel.project_dir').'/assets/images/marque-mm-400x128.png')),
            'document_type' => $document_type ?? 'Document confidentiel',
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // --- Footer commun (date + mention + n° page) ---
        $canvas = $dompdf->getCanvas();
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $fontSize = 9;
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $y = $pageHeight - 30;
        $colorLine = [0/255, 68/255, 91/255];
        $colorText = [0/255, 68/255, 91/255];

        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageWidth, $y, $document_type, $colorLine, $colorText, $font, $fontSize) {
            $canvas->line(40, $y - 5, $pageWidth - 40, $y - 5, $colorLine, 0.5);

            $dateText = 'Généré le ' . (new \DateTimeImmutable())->format('d/m/Y');
            $canvas->text(40, $y, $dateText, $font, $fontSize, $colorText);

            $mention = $document_type ?? 'Document public';
            $textWidth = $fontMetrics->getTextWidth($mention, $font, $fontSize);
            $xCenter = ($pageWidth - $textWidth) / 2;
            $canvas->text($xCenter, $y, $mention, $font, $fontSize, $colorText);

            $pageText = sprintf('Page %d / %d', $pageNumber, $pageCount);
            $textWidthRight = $fontMetrics->getTextWidth($pageText, $font, $fontSize);
            $xRight = $pageWidth - 40 - $textWidthRight;
            $canvas->text($xRight, $y, $pageText, $font, $fontSize, $colorText);
        });

        return $dompdf->output();
    }
}
