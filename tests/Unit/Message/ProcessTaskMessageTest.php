<?php

namespace App\Tests\Message;

use App\Message\ProcessTaskMessage;
use PHPUnit\Framework\TestCase;

/**
 * [Description ProcessTaskMessageTest]
 */
class ProcessTaskMessageTest extends TestCase
{
    /**
     * Données de test pour la tâche
     */
    private array $validTask = [
        "organization" => "my-org-1",
        "id" => "BU_dO1vsORa8_beWCwsP",
        "type" => "REPORT",
        "componentId" => "AU-Tpxb--iU5OvuD2FLy",
        "componentKey" => "project_1",
        "componentName" => "Project One",
        "componentQualifier" => "TRK",
        "analysisId" => "AU-TpxcB-iU5Ovu12345",
        "status" => "SUCCESS",
        "submittedAt" => "2015-08-13T23:34:59+0200",
        "submitterLogin" => "john",
        "startedAt" => "2015-08-13T23:35:00+0200",
        "executedAt" => "2015-08-13T23:35:10+0200",
        "executionTimeMs" => 10000,
        "logs" => false,
        "hasErrorStacktrace" => false,
        "hasScannerContext" => true,
    ];

    /**
     * Teste la construction et la récupération d'une tâche valide
     */
    public function testConstructorAndGetTask()
    {
        $processTaskMessage = new ProcessTaskMessage($this->validTask);

        // Vérifie que la tâche est correctement définie
        $this->assertSame($this->validTask, $processTaskMessage->getTask());
    }

    /**
     * Teste le comportement avec une tâche vide
     */
    public function testEmptyTask()
    {
        $emptyTask = [];
        $processTaskMessage = new ProcessTaskMessage($emptyTask);

        // Vérifie que la tâche vide est correctement récupérée
        $this->assertSame($emptyTask, $processTaskMessage->getTask());
    }

    /**
     * Teste si toutes les clés nécessaires sont présentes dans une tâche valide
     */
    public function testValidTaskKeys()
    {
        $processTaskMessage = new ProcessTaskMessage($this->validTask);
        $task = $processTaskMessage->getTask();

        $expectedKeys = [
            "organization",
            "id",
            "type",
            "componentId",
            "componentKey",
            "componentName",
            "componentQualifier",
            "analysisId",
            "status",
            "submittedAt",
            "submitterLogin",
            "startedAt",
            "executedAt",
            "executionTimeMs",
            "logs",
            "hasErrorStacktrace",
            "hasScannerContext",
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $task, "Clé manquante : $key");
        }
    }

    /**
     * Teste les valeurs d'une tâche valide
     */
    public function testValidTaskValues()
    {
        $processTaskMessage = new ProcessTaskMessage($this->validTask);
        $task = $processTaskMessage->getTask();

        // Vérifie quelques valeurs spécifiques
        $this->assertEquals("my-org-1", $task["organization"]);
        $this->assertEquals("BU_dO1vsORa8_beWCwsP", $task["id"]);
        $this->assertEquals("REPORT", $task["type"]);
        $this->assertEquals(10000, $task["executionTimeMs"]);
        $this->assertFalse($task["logs"]);
        $this->assertTrue($task["hasScannerContext"]);
    }

    /**
     * Teste le comportement avec des données corrompues ou non valides
     */
    public function testInvalidTaskData()
    {
        $invalidTask = [
            "organization" => null,
            "executionTimeMs" => "not-a-number",
        ];

        $processTaskMessage = new ProcessTaskMessage($invalidTask);
        $task = $processTaskMessage->getTask();

        // Vérifie que les données corrompues sont correctement retournées
        $this->assertNull($task["organization"]);
        $this->assertEquals("not-a-number", $task["executionTimeMs"]);
    }
}
