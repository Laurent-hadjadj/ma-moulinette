<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Service\NpmAudit;

/**
 * Toute défaillance liée au payload d'un job d'ingestion npm audit (worker
 * `app:npm-audit:process`) : payload vide, gzip corrompu, JSON invalide,
 * structure inattendue (bloc "full" absent). Contrairement à DC
 * (4 sous-classes dédiées, jamais vraiment discriminées dans le catch du
 * worker), une seule classe suffit ici — le message distingue déjà la
 * cause, et aucun appelant n'a besoin de brancher sur le type concret.
 */
class NpmAuditIngestException extends \RuntimeException
{
}
