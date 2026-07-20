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
use setasign\Fpdi\Fpdi;
use Twig\Environment;
use App\Entity\BatchExecution;

/**
 * [Description PdfExportService]
 */
class PdfExportService
{

    private static string $police = 'DejaVu Sans';
    private static string $confidentiel = 'Document confidentiel';
    private static string $public = 'Document public';
    private static string $logo = '/assets/images/marque-mm-400x128.png';
    // MODIF 2026-05-26 : extraction des string literals dupliqués (S1192).
    private static string $projectDir     = 'kernel.project_dir';
    private static string $dateFormat     = 'd/m/Y';
    private static string $footerDateText = 'Généré le ';
    private static string $footerPageFmt  = 'Page %d / %d';

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
            /* MODIF 2026-05-20 : Ulid non-nullable, ?-> → ->. */
            'executionId' => $batchExecution->getExecutionId()->toRfc4122(),
            'traitementId' => $batchExecution->getTraitementId()->toRfc4122(),
            'modeCollecte' => $batchExecution->getModeCollecte(),
            'utilisateurCollecte' => $batchExecution->getUtilisateurCollecte(),
            'dateEnregistrement' => $batchExecution->getDateEnregistrement()->format('d/m/Y H:i'),
            'collectes' => $collectes,
        ];

        // Configurer Dompdf
        $options = new Options();
        $options->set('defaultFont', self::$police); // Gère UTF-8
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        // --- Génération du HTML ---
        $html = $this->twig->render('/rapport/rapport-zurb-foundation.html.twig', [
            'batch' => $batchData,
            'logoBase64' => base64_encode(file_get_contents($this->params->get(self::$projectDir).self::$logo)),
            'document_type' => $document_type ?? self::$confidentiel,
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
        $font = $dompdf->getFontMetrics()->getFont(self::$police);

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
            $dateText = self::$footerDateText . (new \DateTimeImmutable())->format(self::$dateFormat);
            $canvas->text(40, $y, $dateText, $font, $fontSize, $colorText);

            // Mention au centre (variable $mention passée dans le template)
            $mention = $document_type ?? self::$public;
            $textWidth = $fontMetrics->getTextWidth($mention, $font, $fontSize);
            $xCenter = ($pageWidth - $textWidth) / 2;
            $canvas->text($xCenter, $y, $mention, $font, $fontSize, $colorText);

            // Numéro de page à droite
            // MODIF 2026-05-11 : ajout du total
            // ($pageCount était reçu mais ignoré). Aligne sur generateSuiviPdf
            // et generateOwaspPdf qui affichent "Page X / Y".
            $pageText = sprintf(self::$footerPageFmt, $pageNumber, $pageCount);
            $textWidthRight = $fontMetrics->getTextWidth($pageText, $font, $fontSize);
            $xRight = $pageWidth - 40 - $textWidthRight;
            $canvas->text($xRight, $y, $pageText, $font, $fontSize, $colorText);
        });

        // --- Générer le PDF ---
        return $dompdf->output();
    }

    /**
     * Genere le rapport PDF de la page Suivi.
     *
     * MODIF 2026-05-11 : refonte sur le modèle
     * generateDcPdf. DomPDF ne supporte pas le changement d'orientation
     * in-document : on génère 4 sous-PDFs Dompdf qu'on fusionne via FPDI
     * pour preserver l'orientation native de chaque section.
     *
     *   - sous-PDF 1 : portrait  (page de garde)
     *   - sous-PDF 2 : portrait  (info projet + synthèse executive + alertes)
     *   - sous-PDF 3 : paysage   (tous les tableaux : notes A-E, anomalies,
     *                             tests, complexités, distribution langage)
     *   - sous-PDF 4 : portrait  (glossaire)
     *
     * Footer + filigrane sont dessines au merge FPDI (page_script ne
     * survit pas a la fusion FPDI). Filigrane en RGB(244,244,244) pour
     * matcher l’opacité 0.08 du rendu Dompdf legacy.
     *
     * MODIF 2026-05-11 : suppression
     * du paramètre $orientation (orphelin depuis la refonte FPDI ci-dessus :
     * chaque sous-section impose son orientation propre via les partials).
     *
     * @param array<string, mixed> $data
     * @param string|null $document_type
     * @param bool|null $watermarkOverride
     */
    public function generateSuiviPdf(array $data, ?string $document_type = null, ?bool $watermarkOverride = null): string
    {
        $documentType = $document_type ?? self::$confidentiel;

        $mode = strtolower((string) $this->params->get('rapport.pdf.watermark.mode'));
        if (!in_array($mode, ['always', 'confidential', 'never'], true)) {
            $mode = 'confidential';
        }
        $isConfidential = stripos((string) $documentType, 'confidentiel') !== false;
        $watermarkEnabled = $watermarkOverride ?? match ($mode) {
            'always' => true,
            'never' => false,
            default => $isConfidential,
        };
        $watermarkText = strtoupper($documentType);

        $pdfPart1 = $this->renderSuiviSubPdf('rapport/suivi/_part1-portrait.html.twig', $data, $documentType, 'portrait');
        $pdfPart2 = $this->renderSuiviSubPdf('rapport/suivi/_part2-portrait.html.twig', $data, $documentType, 'portrait');
        $pdfPart3 = $this->renderSuiviSubPdf('rapport/suivi/_part3-landscape.html.twig', $data, $documentType, 'landscape');
        $pdfPart4 = $this->renderSuiviSubPdf('rapport/suivi/_part4-portrait.html.twig', $data, $documentType, 'portrait');

        return $this->mergeSuiviSubPdfs(
            [$pdfPart1, $pdfPart2, $pdfPart3, $pdfPart4],
            $data,
            $documentType,
            $watermarkEnabled,
            $watermarkText
        );
    }

    /**
     * [Description for renderSuiviSubPdf]
     * MODIF 2026-05-11 : génère un sous-PDF Dompdf pour
     * le rapport Suivi a partir d'un partial Twig + une orientation.
     * Pas de footer ni filigrane (dessines apres fusion FPDI pour avoir une
     * numérotation continue).
     *
     * @param array<string, mixed> $rapport
     *
     * @return string
     *
     * Created at: 11/05/2026 18:40:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function renderSuiviSubPdf(string $template, array $rapport, string $documentType, string $orientation): string
    {
        $options = new Options();
        $options->set('defaultFont', self::$police);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render($template, [
            'rapport'       => $rapport,
            'logoBase64'    => base64_encode(file_get_contents($this->params->get(self::$projectDir) . self::$logo)),
            'document_type' => $documentType,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * [Description for mergeSuiviSubPdfs]
     * MODIF 2026-05-11 : fusionne les 4 sous-PDFs Suivi
     * via FPDI en préservant l'orientation de chaque page. Footer + filigrane
     * dessines au niveau du merger FPDF.
     *
     * Filigrane en RGB(244,244,244) (au lieu de 220 pour le rapport DC) :
     * preserve le rendu de l'ancien generateSuiviPdf qui utilisait
     * canvas->set_opacity(0.08) sur RGB(120,120,120) -> projeté sur blanc
     * cela donne ~244. Plus clair = plus facile a lire par-dessus.
     *
     * @param string[] $pdfBinaries
     * @param array<string, mixed> $data
     *
     * @return string
     *
     * Created at: 11/05/2026 18:41:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function mergeSuiviSubPdfs(
        array $pdfBinaries,
        array $data,
        string $documentType,
        bool $watermarkEnabled = false,
        string $watermarkText = '',
    ): string {
        $merger = new class extends Fpdi {
            public string $suiviDocumentType = '';
            public string $suiviDateGenerated = '';
            private float $angle = 0;

            public function Footer(): void
            {
                $this->SetY(-15);
                $this->SetDrawColor(0, 68, 91);
                $this->Line(15, $this->GetY(), $this->GetPageWidth() - 15, $this->GetY());
                $this->SetY(-12);
                $this->SetFont('Helvetica', '', 9);
                $this->SetTextColor(0, 68, 91);
                $this->SetX(15);
                $this->Cell(60, 6, $this->suiviDateGenerated, 0, 0, 'L');
                $this->SetX(0);
                $this->Cell($this->GetPageWidth(), 6, $this->suiviDocumentType, 0, 0, 'C');
                $this->SetX($this->GetPageWidth() - 75);
                $this->Cell(60, 6, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');
            }

            public function Rotate(float $angle, ?float $x = null, ?float $y = null): void
            {
                if ($x === null) { $x = $this->x; }
                if ($y === null) { $y = $this->y; }
                if ($this->angle != 0) { $this->_out('Q'); }
                $this->angle = $angle;
                if ($angle != 0) {
                    $angle *= M_PI / 180;
                    $c  = cos($angle);
                    $s  = sin($angle);
                    $cx = $x * $this->k;
                    $cy = ($this->h - $y) * $this->k;
                    $this->_out(sprintf(
                        'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                        $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
                    ));
                }
            }

            protected function _endpage(): void
            {
                if ($this->angle != 0) {
                    $this->angle = 0;
                    $this->_out('Q');
                }
                parent::_endpage();
            }

            /* MODIF 2026-05-11 : couleur RGB(244,244,244)
             * au lieu de (220,220,220) du DC pour conserver le rendu clair de
             * l'ancien filigrane Dompdf (opacity 0.08 sur gris). */
            public function DrawSuiviWatermark(string $text): void
            {
                if ($text === '') { return; }
                $this->SetFont('Helvetica', 'B', 50);
                $this->SetTextColor(244, 244, 244);
                $w = $this->GetPageWidth();
                $h = $this->GetPageHeight();
                $cx = $w / 2;
                $cy = $h / 2;
                $textWidth = $this->GetStringWidth($text);
                $this->Rotate(30, $cx, $cy);
                $this->Text($cx - $textWidth / 2, $cy + 8, $text);
                $this->Rotate(0);
                $this->SetTextColor(0, 68, 91);
            }
        };
        $merger->suiviDocumentType  = $this->toLatin1($documentType);
        $merger->suiviDateGenerated = $this->toLatin1('Genere le ' . date(self::$dateFormat));
        $merger->AliasNbPages();
        $merger->setAutoPageBreak(false);

        $projectLabel = $data['project_name'] ?? ($data['maven_key'] ?? 'projet');
        $merger->SetTitle($this->toLatin1('Rapport de suivi qualite - ' . $projectLabel));
        $merger->SetAuthor('Ma-Moulinette');
        $merger->SetSubject($this->toLatin1('Suivi qualite SonarQube - ' . ($data['maven_key'] ?? '')));
        $merger->SetKeywords('SonarQube, qualite, suivi, rapport, ' . ($data['maven_key'] ?? ''));
        $merger->SetCreator('Ma-Moulinette');

        $tmpFiles = [];
        try {
            foreach ($pdfBinaries as $bin) {
                $tmp = tempnam(sys_get_temp_dir(), 'suivi-fpdi-') . '.pdf';
                file_put_contents($tmp, $bin);
                $tmpFiles[] = $tmp;
            }

            $latinWatermark = $watermarkEnabled ? $this->toLatin1($watermarkText) : '';
            foreach ($tmpFiles as $tmp) {
                $pageCount = $merger->setSourceFile($tmp);
                for ($p = 1; $p <= $pageCount; $p++) {
                    $tplId = $merger->importPage($p);
                    $size  = $merger->getTemplateSize($tplId);
                    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    if ($watermarkEnabled && $merger->PageNo() > 1) {
                        $merger->DrawSuiviWatermark($latinWatermark);
                    }
                    $merger->useTemplate($tplId);
                }
            }

            return $merger->Output('S');
        } finally {
            foreach ($tmpFiles as $tmp) { @unlink($tmp); }
        }
    }

    /**
     * [Description for generateOwaspPdf]
     *
     * @param array $data
     * @param string|null $document_type
     *
     * @return string
     *
     * Created at: 11/05/2026 00:17:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateOwaspPdf(array $data, ?string $document_type = null): string
    {
        $options = new Options();
        $options->set('defaultFont', self::$police);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render('/rapport/rapport-owasp.html.twig', [
            'rapport' => $data,
            'logoBase64' => base64_encode(file_get_contents($this->params->get(self::$projectDir).self::$logo)),
            'document_type' => $document_type ?? self::$confidentiel,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // --- Footer commun (date + mention + n° page) ---
        $canvas = $dompdf->getCanvas();
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $fontSize = 9;
        $font = $dompdf->getFontMetrics()->getFont(self::$logo);
        $y = $pageHeight - 30;
        $colorLine = [0/255, 68/255, 91/255];
        $colorText = [0/255, 68/255, 91/255];

        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageWidth, $y, $document_type, $colorLine, $colorText, $font, $fontSize) {
            $canvas->line(40, $y - 5, $pageWidth - 40, $y - 5, $colorLine, 0.5);

            $dateText = self::$footerDateText . (new \DateTimeImmutable())->format(self::$dateFormat);
            $canvas->text(40, $y, $dateText, $font, $fontSize, $colorText);

            $mention = $document_type ?? self::$public;
            $textWidth = $fontMetrics->getTextWidth($mention, $font, $fontSize);
            $xCenter = ($pageWidth - $textWidth) / 2;
            $canvas->text($xCenter, $y, $mention, $font, $fontSize, $colorText);

            $pageText = sprintf(self::$footerPageFmt, $pageNumber, $pageCount);
            $textWidthRight = $fontMetrics->getTextWidth($pageText, $font, $fontSize);
            $xRight = $pageWidth - 40 - $textWidthRight;
            $canvas->text($xRight, $y, $pageText, $font, $fontSize, $colorText);
        });

        return $dompdf->output();
    }

    /**
     * [Description for generateDcPdf]
     * MODIF 2026-05-08 : generation PDF d'un rapport
     * OWASP DependencyCheck pour un projet/version donne.
     *
     * MODIF 2026-05-08 : architecture en 3 sous-PDFs fusionnes
     * via FPDI pour permettre une orientation mixte (portrait/paysage/portrait).
     * DomPDF ne supportant pas le changement d'orientation in-document, on génère :
     *   - sous-PDF 1 : portrait (page de garde + executive summary)
     *   - sous-PDF 2 : paysage  (detail des findings par sévérité)
     *   - sous-PDF 3 : portrait (glossaire)
     * puis FPDI les fusionne en préservant l'orientation native de chaque page.
     *
     * MODIF 2026-05-08 : ajout filigrane DC, pilote par
     * RAPPORT_PDF_WATERMARK_MODE (.env) avec override `?watermark=on|off` cote
     * Controller. Aligne avec generateSuiviPdf.
     *
     * @param array<string, mixed> $data {
     *   project_group, project_artifact, project_version, scan, findings,
     *   top_deps, top_cwes
     * }
     *
     * @return string
     *
     * Created at: 11/05/2026 00:17:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateDcPdf(array $data, ?string $document_type = null, ?bool $watermarkOverride = null): string
    {
        $documentType = $document_type ?? self::$confidentiel;

        $mode = strtolower((string) $this->params->get('rapport.pdf.watermark.mode'));
        if (!in_array($mode, ['always', 'confidential', 'never'], true)) {
            $mode = 'confidential';
        }
        $isConfidential = stripos((string) $documentType, 'confidentiel') !== false;
        $watermarkEnabled = $watermarkOverride ?? match ($mode) {
            'always' => true,
            'never' => false,
            default => $isConfidential,
        };
        $watermarkText = strtoupper($documentType);

        $pdfPart1 = $this->renderDcSubPdf(
            'rapport/dc/_part1-portrait.html.twig', $data, 'portrait'
        );
        $pdfPart3 = $this->renderDcSubPdf(
            'rapport/dc/_part3-portrait.html.twig', $data, 'portrait'
        );
        // Sous-PDF 2 (paysage) génère uniquement si findings non vide
        $pdfPart2 = !empty($data['findings'])
            ? $this->renderDcSubPdf(
                'rapport/dc/_part2-landscape.html.twig', $data, 'landscape'
            )
            : null;

        $parts = [$pdfPart1];
        if ($pdfPart2 !== null) { $parts[] = $pdfPart2; }
        $parts[] = $pdfPart3;

        return $this->mergeDcSubPdfs($parts, $data, $documentType, $watermarkEnabled, $watermarkText);
    }

    /**
     * [Description for renderDcSubPdf]
     * Génère un sous-PDF DomPDF a partir d'un template Twig + une orientation.
     * Pas de footer (sera ajoute après la fusion FPDI pour avoir une numérotation
     * de pages continue).
     *
     * @param array<string, mixed> $data
     *
     * @return string
     *
     * Created at: 11/05/2026 19:05:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function renderDcSubPdf(string $template, array $data, string $orientation): string
    {
        $options = new Options();
        $options->set('defaultFont', self::$police);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render($template, [
            'data'       => $data,
            'logoBase64' => base64_encode(file_get_contents($this->params->get(self::$projectDir) . self::$logo)),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * [Description for mergeDcSubPdfs]
     * Fusionne plusieurs PDFs binaires via FPDI en préservant l'orientation et
     * la taille de chaque page source. Footer commun dessine via le hook
     * `Footer()` de FPDF (sous-classe anonyme) + `AliasNbPages()` qui remplace
     * automatiquement `{nb}` par le total a la generation finale.
     *
     * @param string[] $pdfBinaries
     * @param array<string, mixed> $data
     *
     * @return string
     *
     * Created at: 11/05/2026 19:07:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function mergeDcSubPdfs(
        array $pdfBinaries,
        array $data,
        string $documentType,
        bool $watermarkEnabled = false,
        string $watermarkText = '',
    ): string {
        $merger = new class extends Fpdi {
            public string $dcDocumentType = '';
            public string $dcDateGenerated = '';
            /* MODIF 2026-05-08 [dc-ingest] : état de rotation FPDF (script Rotate
             * canonique - FPDF n'a pas de transformation native). */
            private float $angle = 0;

            public function Footer(): void
            {
                $this->SetY(-15);
                $this->SetDrawColor(0, 68, 91);
                $this->Line(15, $this->GetY(), $this->GetPageWidth() - 15, $this->GetY());
                $this->SetY(-12);
                $this->SetFont('Helvetica', '', 9);
                $this->SetTextColor(0, 68, 91);
                // Date a gauche
                $this->SetX(15);
                $this->Cell(60, 6, $this->dcDateGenerated, 0, 0, 'L');
                // Mention au centre
                $this->SetX(0);
                $this->Cell($this->GetPageWidth(), 6, $this->dcDocumentType, 0, 0, 'C');
                // Numero page a droite : {nb} remplace automatiquement par AliasNbPages()
                $this->SetX($this->GetPageWidth() - 75);
                $this->Cell(60, 6, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');
            }

            /* MODIF 2026-05-08 : Rotate canonique FPDF
             * (https://www.fpdf.org/en/script/script2.php). Permet de pivoter le
             * texte autour d'un point pour dessiner un filigrane oblique. */
            public function Rotate(float $angle, ?float $x = null, ?float $y = null): void
            {
                if ($x === null) { $x = $this->x; }
                if ($y === null) { $y = $this->y; }
                if ($this->angle != 0) { $this->_out('Q'); }
                $this->angle = $angle;
                if ($angle != 0) {
                    $angle *= M_PI / 180;
                    $c  = cos($angle);
                    $s  = sin($angle);
                    $cx = $x * $this->k;
                    $cy = ($this->h - $y) * $this->k;
                    $this->_out(sprintf(
                        'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                        $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
                    ));
                }
            }

            /* MODIF 2026-05-08 : annulation de la rotation a la fermeture
             * de page (sinon l’état graphique fuit sur les pages suivantes). */
            protected function _endpage(): void
            {
                if ($this->angle != 0) {
                    $this->angle = 0;
                    $this->_out('Q');
                }
                parent::_endpage();
            }

            /* MODIF 2026-05-08 : dessine le filigrane oblique centre
             * sur la page courante. Couleur gris tres clair pour simuler la
             * transparence (FPDF n'a pas d'opacity natif).
             *
             * MODIF 2026-05-10 : remplacement de
             * Cell()+SetXY par Text() (pattern officiel FPDF watermark, cf.
             * https://www.fpdf.org/en/script/script19.php). La Cell tournée
             * sur les pages portrait clippait le texte apres ~3 caractères
             * ("DOC") parce que la cellHeight=16mm était inférieure a la
             * hauteur réelle de la font 50pt (~17.6mm), avec un comportement
             * different selon l'orientation page. Text() dessine directement
             * a une position absolue, sans wrapping ni clipping de cell. */
            public function DrawDcWatermark(string $text): void
            {
                if ($text === '') { return; }
                $this->SetFont('Helvetica', 'B', 50);
                // MODIF 2026-05-11 : RGB(220) -> RGB(244)
                // pour aligner sur le filigrane du rapport Suivi (preserve la
                // lisibilité du texte par-dessus, RGB(244) ~ opacity 0.08 sur
                // RGB(120) projeté sur blanc).
                $this->SetTextColor(244, 244, 244);
                $w = $this->GetPageWidth();
                $h = $this->GetPageHeight();
                $cx = $w / 2;
                $cy = $h / 2;
                $textWidth = $this->GetStringWidth($text);
                // Rotation autour du centre de la page, puis Text() centre
                // sur ce meme point. Le +8 sur Y compense la baseline (font
                // 50pt ~17.6mm, baseline ~8mm sous le centre vertical du glyph).
                $this->Rotate(30, $cx, $cy);
                $this->Text($cx - $textWidth / 2, $cy + 8, $text);
                $this->Rotate(0);
                // Reset couleur pour le footer
                $this->SetTextColor(0, 68, 91);
            }
        };
        $merger->dcDocumentType  = $this->toLatin1($documentType);
        $merger->dcDateGenerated = $this->toLatin1('Génère le ' . date(self::$dateFormat));
        $merger->AliasNbPages();
        $merger->setAutoPageBreak(false);

        // Metadata
        $projectLabel = sprintf('%s:%s:%s',
            $data['project_group'] ?? '?',
            $data['project_artifact'] ?? '?',
            $data['project_version'] ?? '?'
        );
        $merger->SetTitle($this->toLatin1('Rapport DependencyCheck - ' . $projectLabel));
        $merger->SetAuthor('Ma-Moulinette');
        $merger->SetSubject($this->toLatin1('Rapport vulnerabilites OWASP DependencyCheck'));
        $merger->SetKeywords('OWASP, DependencyCheck, CVE, CWE');
        $merger->SetCreator('Ma-Moulinette');

        // Écrit chaque sous-PDF dans un fichier temporaire (FPDI requiert un fichier)
        $tmpFiles = [];
        try {
            foreach ($pdfBinaries as $bin) {
                $tmp = tempnam(sys_get_temp_dir(), 'dc-fpdi-') . '.pdf';
                file_put_contents($tmp, $bin);
                $tmpFiles[] = $tmp;
            }

            // Importer toutes les pages : Footer() est appelé automatiquement
            // a chaque AddPage / fermeture de page.
            /* MODIF 2026-05-10 : filigrane dessine
             * AVANT useTemplate() pour rester en arrière-plan (était apres,
             * il masquait le texte du document). Les sous-PDFs dompdf ont un
             * <body> transparent par défaut donc le filigrane reste visible
             * a travers les zones non-texte. Saut sur la page 1 (garde, qui
             * porte deja la classification). */
            $latinWatermark = $watermarkEnabled ? $this->toLatin1($watermarkText) : '';
            foreach ($tmpFiles as $tmp) {
                $pageCount = $merger->setSourceFile($tmp);
                for ($p = 1; $p <= $pageCount; $p++) {
                    $tplId = $merger->importPage($p);
                    $size  = $merger->getTemplateSize($tplId);
                    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    if ($watermarkEnabled && $merger->PageNo() > 1) {
                        $merger->DrawDcWatermark($latinWatermark);
                    }
                    $merger->useTemplate($tplId);
                }
            }

            return $merger->Output('S');
        } finally {
            foreach ($tmpFiles as $tmp) { @unlink($tmp); }
        }
    }

    /**
     * [Description for generateCleanCodeSynthesePdf]
     * Génère le PDF synthèse clean code
     * (paysage A4, 9 colonnes : projet, MNT/FIA/SEC/HSP, score risque, bugs,
     * vulnérabilités, couverture). Un seul sous-PDF Dompdf, pas de fusion FPDI.
     *
     * @param array<string, mixed> $data  Clés : projets (array), scope_label (string)
     * @param string|null $document_type
     *
     * @return string contenu binaire du PDF
     *
     * Created at: 19/05/2026 09:32:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateCleanCodeSynthesePdf(array $data, ?string $document_type = null): string
    {
        $documentType = $document_type ?? self::$public;

        $html = $this->twig->render('rapport/clean-code/_synthese-landscape.html.twig', [
            'projets'          => $data['projets'] ?? [],
            'scope_label'      => $data['scope_label'] ?? '',
            'date_generation'  => (new \DateTimeImmutable())->format(self::$dateFormat),
            'logoBase64'       => base64_encode(
                file_get_contents($this->params->get(self::$projectDir) . self::$logo)
            ),
        ]);

        $options = new Options();
        $options->set('defaultFont', self::$police);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $canvas    = $dompdf->getCanvas();
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $fontSize  = 9;
        $font      = $dompdf->getFontMetrics()->getFont(self::$police);
        $colorLine = [0/255, 68/255, 91/255];
        $colorText = [0/255, 68/255, 91/255];
        $y = $pageHeight - 30;

        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics)
            use ($pageWidth, $y, $documentType, $colorLine, $colorText, $font, $fontSize) {
            $canvas->line(30, $y - 5, $pageWidth - 30, $y - 5, $colorLine, 0.5);

            $dateText = self::$footerDateText . (new \DateTimeImmutable())->format(self::$dateFormat);
            $canvas->text(30, $y, $dateText, $font, $fontSize, $colorText);

            $textWidth = $fontMetrics->getTextWidth($documentType, $font, $fontSize);
            $canvas->text(($pageWidth - $textWidth) / 2, $y, $documentType, $font, $fontSize, $colorText);

            $pageText = sprintf(self::$footerPageFmt, $pageNumber, $pageCount);
            $textWidthRight = $fontMetrics->getTextWidth($pageText, $font, $fontSize);
            $canvas->text($pageWidth - 30 - $textWidthRight, $y, $pageText, $font, $fontSize, $colorText);
        });

        return $dompdf->output();
    }

    /**
     * [Description for generateUserRoleLogPdf]
     * Génère le rapport PDF du journal des changements de rôle
     * (paysage A4, un seul sous-PDF Dompdf, pas de fusion FPDI).
     *
     * @param array<int, array<string, mixed>> $lignes lignes déjà formatées
     *   (date, userEmail, editorEmail, oldRoles, newRoles, oldActive, newActive, alerts)
     * @param string|null $filtreLabel description du filtre appliqué, affichée en sous-titre
     * @param string|null $document_type
     *
     * @return string contenu binaire du PDF
     *
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateUserRoleLogPdf(array $lignes, ?string $filtreLabel = null, ?string $document_type = null): string
    {
        $documentType = $document_type ?? self::$confidentiel;

        $html = $this->twig->render('rapport/journal-roles/_rapport.html.twig', [
            'lignes'          => $lignes,
            'filtre_label'    => $filtreLabel,
            'date_generation' => (new \DateTimeImmutable())->format('d/m/Y H:i'),
            'logoBase64'      => base64_encode(
                file_get_contents($this->params->get(self::$projectDir) . self::$logo)
            ),
        ]);

        $options = new Options();
        $options->set('defaultFont', self::$police);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $canvas     = $dompdf->getCanvas();
        $pageWidth  = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $fontSize   = 9;
        $font       = $dompdf->getFontMetrics()->getFont(self::$police);
        $colorLine  = [0/255, 68/255, 91/255];
        $colorText  = [0/255, 68/255, 91/255];
        $y = $pageHeight - 30;

        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics)
            use ($pageWidth, $y, $documentType, $colorLine, $colorText, $font, $fontSize) {
            $canvas->line(30, $y - 5, $pageWidth - 30, $y - 5, $colorLine, 0.5);

            $dateText = self::$footerDateText . (new \DateTimeImmutable())->format(self::$dateFormat);
            $canvas->text(30, $y, $dateText, $font, $fontSize, $colorText);

            $textWidth = $fontMetrics->getTextWidth($documentType, $font, $fontSize);
            $canvas->text(($pageWidth - $textWidth) / 2, $y, $documentType, $font, $fontSize, $colorText);

            $pageText = sprintf(self::$footerPageFmt, $pageNumber, $pageCount);
            $textWidthRight = $fontMetrics->getTextWidth($pageText, $font, $fontSize);
            $canvas->text($pageWidth - 30 - $textWidthRight, $y, $pageText, $font, $fontSize, $colorText);
        });

        return $dompdf->output();
    }

    /**
     * [Description for toLatin1]
     * FPDF n'accepte que latin1 nativement. Conversion safe avec fallback.
     *
     * @param string $utf8
     *
     * @return string
     *
     * Created at: 11/05/2026 19:10:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function toLatin1(string $utf8): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $utf8);
        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '?', $utf8);
    }
}
