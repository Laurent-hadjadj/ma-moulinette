<?php

namespace App\Tests\Unit\Service;

use App\Service\ExtractName;
use PHPUnit\Framework\TestCase;

/**
 * [Description ExtractNameTest]
 */
class ExtractNameTest extends TestCase
{
    private ExtractName $extractName;

    protected function setUp(): void
    {
        // Initialisation de l'objet ExtractName
        $this->extractName = new ExtractName();
    }

    public function testExtractNameFromValidMavenKey(): void
    {
        $mavenKey = "fr.ma-petite-entreprise:ma-moulinette";
        $result = $this->extractName->extractNameFromMavenKey($mavenKey);

        // Vérification que le nom extrait est "ma-moulinette"
        $this->assertEquals("ma-moulinette", $result);
    }

    public function testExtractNameFromMavenKeyWithoutColon(): void
    {
        $mavenKey = "ma-petite-entreprise";
        $result = $this->extractName->extractNameFromMavenKey($mavenKey);

        // Vérification que la clé Maven sans colon reste inchangée
        $this->assertEquals("ma-petite-entreprise", $result);
    }

    public function testExtractNameFromEmptyMavenKey(): void
    {
        $mavenKey = "";
        $result = $this->extractName->extractNameFromMavenKey($mavenKey);

        // Vérification que la chaîne vide est retournée
        $this->assertEquals("", $result);
    }

    public function testExtractNameFromNullMavenKey(): void
    {
        $mavenKey = null;  // On passe null pour tester ce cas
        $result = $this->extractName->extractNameFromMavenKey($mavenKey);

        // Vérification que la méthode retourne une chaîne vide
        $this->assertEquals('', $result);
    }

    public function testExtractNameFromMavenKeyWithMultipleColons(): void
    {
        $mavenKey = "org.apache:project:name";
        $result = $this->extractName->extractNameFromMavenKey($mavenKey);

        // Vérification que le nom extrait est le deuxième élément après le ":"
        $this->assertEquals("project", $result);
    }

    public function testExtractNameFromSingleElementMavenKey(): void
    {
        $mavenKey = "my-single-key";
        $result = $this->extractName->extractNameFromMavenKey($mavenKey);

        // Vérification que la clé est retournée telle quelle
        $this->assertEquals("my-single-key", $result);
    }
}
