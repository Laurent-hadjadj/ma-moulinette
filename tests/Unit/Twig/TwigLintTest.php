<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */
namespace App\Tests\Unit\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;
use Twig\Environment;
use Twig\Source;
use Twig\Error\SyntaxError;

class TwigLintTest extends KernelTestCase
{
    public function testAllTwigTemplatesAreValid(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        $templatesPath = self::$kernel->getProjectDir() . '/templates';

        $finder = new Finder();
        $finder->files()->in($templatesPath)->name('*.twig');

        foreach ($finder as $file) {
            $templateContent = $file->getContents();
            $source = new Source($templateContent, $file->getRelativePathname());

            try {
                $twig->parse($twig->tokenize($source));
                $this->assertTrue(true); // Le template est valide
            } catch (SyntaxError $e) {
                $this->fail("Syntax error in template '{$file->getRelativePathname()}': " . $e->getMessage());
            }
        }
    }
}
