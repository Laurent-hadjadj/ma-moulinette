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
use Symfony\Component\HttpFoundation\Response;
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
        $this->params = $params;
    }

    /**
     * [Description for generateRapportPdf]
     * Génère un PDF pour un BatchExecution
     *
     * @param BatchExecution $batchExecution
     * @param mixed $document_type
     *
     * @return string
     *
     * Created at: 13/11/2025 21:05:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateRapportPdf(BatchExecution $batchExecution, $document_type): string
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
        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageWidth, $y, $document_type, $colorLine, $colorText, $font,$fontSize) {
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
}
