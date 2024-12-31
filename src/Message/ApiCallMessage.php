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

namespace App\Message;

/**
 * [Description ApiCallMessage]
 *
 * On créé un message avec la date de début et de fin.
 *
 */
class ApiCallMessage
{
    private string $fromDate;
    private string $toDate;

    public function __construct(string $fromDate, string $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     * [Description for getFromDate]
     * Date de début
     * @return string
     *
     * Created at: 27/12/2024 08:37:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFromDate(): string
    {
        return $this->fromDate;
    }

    /**
     * [Description for getToDate]
     * Date de fin
     * @return string
     *
     * Created at: 27/12/2024 08:37:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getToDate(): string
    {
        return $this->toDate;
    }
}
