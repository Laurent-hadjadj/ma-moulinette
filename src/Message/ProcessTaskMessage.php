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
 * [Description ProcessTaskMessage]
 */
class ProcessTaskMessage
{
    private array $task;

    public function __construct(array $task)
    {
        $this->task = $task;
    }

    /**
     * [Description for getTask]
     *
     * @return array
     *
     * Created at: 31/12/2024 16:32:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTask(): array
    {
        return $this->task;
    }
}
