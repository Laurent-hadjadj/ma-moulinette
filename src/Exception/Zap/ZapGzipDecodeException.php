<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Exception\Zap;

/**
 * MODIF 2026-10-13 : la décompression gzip du payload a échoué à
 * l'ingestion (format corrompu).
 */
class ZapGzipDecodeException extends ZapPayloadException
{
}
