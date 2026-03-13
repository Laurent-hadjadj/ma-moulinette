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

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Ldap\Ldap;


/**
 * [Description LdapTestCommand]
 */
#[AsCommand(
    name: 'app:ldap:test',
    description: 'Test de connexion LDAP',
)]
class LdapTestCommand extends Command
{
    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 11/02/2026 15:33:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $ldap = Ldap::create('ext_ldap', [
            'host' => $_ENV['LDAP_HOST'],
            'port' => (int) $_ENV['LDAP_PORT'],
            'encryption' => $_ENV['LDAP_ENCRYPTION'],
            'options' => [
                'protocol_version' => 3,
                'referrals' => false,
                'network_timeout' => 3,
            ],
        ]);

        try {
            $ldap->bind(
                $_ENV['LDAP_BIND_DN'],
                $_ENV['LDAP_BIND_PASSWORD']
            );

            $output->writeln('<info>✅ Connexion LDAP OK</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>❌ Erreur LDAP: '.$e->getMessage().'</error>');
        }

        return Command::SUCCESS;
    }
}
