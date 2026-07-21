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

/* MODIF 2026-05-21 : déplacé dans App\Command\Dev. */
namespace App\Command\Dev;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\LdapException;

/**
 * [Description LdapTestCommand]
 */
#[AsCommand(
    name: 'app:ldap:test',
    description: 'Test de connexion LDAP',
)]
class LdapTestCommand extends Command
{
    // MODIF 2026-05-26 : extraction des string literals dupliqués (S1192).
    private const TAG_ERROR_CLOSE = '</error>';

    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

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
        /* MODIF 2026-07-21 : garde-fou d'environnement ajouté, cohérent avec
         * les 4 autres commandes de App\Command\Dev — cette commande ne fait
         * que tester une connexion LDAP mais n'a aucune raison de tourner
         * en prod (identifiants de bind affichés en erreur en cas d'échec). */
        if ($this->appEnv !== 'dev') {
            $output->writeln(sprintf('<error>❌ Cette commande est réservée à env=dev (env courant : %s).</error>', $this->appEnv));
            return Command::FAILURE;
        }

        $ldapHost = $_ENV['LDAP_HOST'];
        $ldapPort = (int) $_ENV['LDAP_PORT'];
        $ldapEncryption = $_ENV['LDAP_ENCRYPTION'];

        $ldap = Ldap::create('ext_ldap', [
                'host' => $ldapHost,
                'port' => $ldapPort,
                'encryption' => $ldapEncryption,
                'options' => [
                        'protocol_version' => 3,
                        'referrals' => false,
                        'network_timeout' => 3,
                        'x_tls_require_cert' => 0
                ],
        ]);

        try {
            $ldap->bind(
                $_ENV['LDAP_BIND_DN'],
                $_ENV['LDAP_BIND_PASSWORD']
            );

            $output->writeln('<info>✅ Connexion LDAP OK</info>');
        } catch (LdapException $e) {
                        $output->writeln('<error>❌ Erreur LDAP: ' . $e->getMessage() . self::TAG_ERROR_CLOSE);
                        $output->writeln('<error>❌ Code d\'erreur LDAP: ' . $e->getCode() . self::TAG_ERROR_CLOSE);
                        $output->writeln('<error>❌ Trace de l\'erreur: ' . $e->getTraceAsString() . self::TAG_ERROR_CLOSE);
                        // MODIF 2026-07-21 : le code retournait toujours Command::SUCCESS,
                        // y compris quand le bind LDAP échouait.
                        return Command::FAILURE;
                } catch (\Exception $e) {
                        $output->writeln('<error>❌ Erreur générale: ' . $e->getMessage() . self::TAG_ERROR_CLOSE);
                        $output->writeln('<error>❌ Code d\'erreur: ' . $e->getCode() . self::TAG_ERROR_CLOSE);
                        $output->writeln('<error>❌ Trace de l\'erreur: ' . $e->getTraceAsString() . self::TAG_ERROR_CLOSE);
                        return Command::FAILURE;
                }

        return Command::SUCCESS;
    }
}
