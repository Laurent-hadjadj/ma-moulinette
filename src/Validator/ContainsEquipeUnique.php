<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ContainsEquipeUnique extends Constraint
{
    public string $message = '[Equipe] La valeur "{{ string }}" existe déjà.';
    public string $mode = 'strict'; //mode='loose'
}
