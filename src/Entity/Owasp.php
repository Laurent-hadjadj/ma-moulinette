<?php
/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

namespace App\Entity;

use App\Repository\OwaspRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OwaspRepository::class)]
class Owasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $maven_key;

    #[ORM\Column(type: 'integer')]
    private $effort_total;

    #[ORM\Column(type: 'integer')]
    private $a1;

    #[ORM\Column(type: 'integer')]
    private $a2;

    #[ORM\Column(type: 'integer')]
    private $a3;

    #[ORM\Column(type: 'integer')]
    private $a4;

    #[ORM\Column(type: 'integer')]
    private $a5;

    #[ORM\Column(type: 'integer')]
    private $a6;

    #[ORM\Column(type: 'integer')]
    private $a7;

    #[ORM\Column(type: 'integer')]
    private $a8;

    #[ORM\Column(type: 'integer')]
    private $a9;

    #[ORM\Column(type: 'integer')]
    private $a10;

    #[ORM\Column(type: 'integer')]
    private $a1_blocker;

    #[ORM\Column(type: 'integer')]
    private $a1_critical;

    #[ORM\Column(type: 'integer')]
    private $a1_major;

    #[ORM\Column(type: 'integer')]
    private $a1_info;

    #[ORM\Column(type: 'integer')]
    private $a1_minor;

    #[ORM\Column(type: 'integer')]
    private $a2_blocker;

    #[ORM\Column(type: 'integer')]
    private $a2_critical;

    #[ORM\Column(type: 'integer')]
    private $a2_major;

    #[ORM\Column(type: 'integer')]
    private $a2_info;

    #[ORM\Column(type: 'integer')]
    private $a2_minor;

    #[ORM\Column(type: 'integer')]
    private $a3_blocker;

    #[ORM\Column(type: 'integer')]
    private $a3_critical;

    #[ORM\Column(type: 'integer')]
    private $a3_major;

    #[ORM\Column(type: 'integer')]
    private $a3_info;

    #[ORM\Column(type: 'integer')]
    private $a3_minor;

    #[ORM\Column(type: 'integer')]
    private $a4_blocker;

    #[ORM\Column(type: 'integer')]
    private $a4_critical;

    #[ORM\Column(type: 'integer')]
    private $a4_major;

    #[ORM\Column(type: 'integer')]
    private $a4_info;

    #[ORM\Column(type: 'integer')]
    private $a4_minor;

    #[ORM\Column(type: 'integer')]
    private $a5_blocker;

    #[ORM\Column(type: 'integer')]
    private $a5_critical;

    #[ORM\Column(type: 'integer')]
    private $a5_major;

    #[ORM\Column(type: 'integer')]
    private $a5_info;

    #[ORM\Column(type: 'integer')]
    private $a5_minor;

    #[ORM\Column(type: 'integer')]
    private $a6_blocker;

    #[ORM\Column(type: 'integer')]
    private $a6_critical;

    #[ORM\Column(type: 'integer')]
    private $a6_major;

    #[ORM\Column(type: 'integer')]
    private $a6_info;

    #[ORM\Column(type: 'integer')]
    private $a6_minor;

    #[ORM\Column(type: 'integer')]
    private $a7_blocker;

    #[ORM\Column(type: 'integer')]
    private $a7_critical;

    #[ORM\Column(type: 'integer')]
    private $a7_major;

    #[ORM\Column(type: 'integer')]
    private $a7_info;

    #[ORM\Column(type: 'integer')]
    private $a7_minor;

    #[ORM\Column(type: 'integer')]
    private $a8_blocker;

    #[ORM\Column(type: 'integer')]
    private $a8_critical;

    #[ORM\Column(type: 'integer')]
    private $a8_major;

    #[ORM\Column(type: 'integer')]
    private $a8_info;

    #[ORM\Column(type: 'integer')]
    private $a8_minor;

    #[ORM\Column(type: 'integer')]
    private $a9_blocker;

    #[ORM\Column(type: 'integer')]
    private $a9_critical;

    #[ORM\Column(type: 'integer')]
    private $a9_major;

    #[ORM\Column(type: 'integer')]
    private $a9_info;

    #[ORM\Column(type: 'integer')]
    private $a9_minor;

    #[ORM\Column(type: 'integer')]
    private $a10_blocker;

    #[ORM\Column(type: 'integer')]
    private $a10_critical;

    #[ORM\Column(type: 'integer')]
    private $a10_major;

    #[ORM\Column(type: 'integer')]
    private $a10_info;

    #[ORM\Column(type: 'integer')]
    private $a10_minor;

    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->maven_key;
    }

    public function setMavenKey(string $maven_key): self
    {
        $this->maven_key = $maven_key;

        return $this;
    }

    public function getEffortTotal(): ?int
    {
        return $this->effort_total;
    }

    public function setEffortTotal(int $effort_total): self
    {
        $this->effort_total = $effort_total;

        return $this;
    }

    public function getA1(): ?int
    {
        return $this->a1;
    }

    public function setA1(int $a1): self
    {
        $this->a1 = $a1;

        return $this;
    }

    public function getA2(): ?int
    {
        return $this->a2;
    }

    public function setA2(int $a2): self
    {
        $this->a2 = $a2;

        return $this;
    }

    public function getA3(): ?int
    {
        return $this->a3;
    }

    public function setA3(int $a3): self
    {
        $this->a3 = $a3;

        return $this;
    }

    public function getA4(): ?int
    {
        return $this->a4;
    }

    public function setA4(int $a4): self
    {
        $this->a4 = $a4;

        return $this;
    }

    public function getA5(): ?int
    {
        return $this->a5;
    }

    public function setA5(int $a5): self
    {
        $this->a5 = $a5;

        return $this;
    }

    public function getA6(): ?int
    {
        return $this->a6;
    }

    public function setA6(int $a6): self
    {
        $this->a6 = $a6;

        return $this;
    }

    public function getA7(): ?int
    {
        return $this->a7;
    }

    public function setA7(int $a7): self
    {
        $this->a7 = $a7;

        return $this;
    }

    public function getA8(): ?int
    {
        return $this->a8;
    }

    public function setA8(int $a8): self
    {
        $this->a8 = $a8;

        return $this;
    }

    public function getA9(): ?int
    {
        return $this->a9;
    }

    public function setA9(int $a9): self
    {
        $this->a9 = $a9;

        return $this;
    }

    public function getA10(): ?int
    {
        return $this->a10;
    }

    public function setA10(int $a10): self
    {
        $this->a10 = $a10;

        return $this;
    }

    public function getA1Blocker(): ?int
    {
        return $this->a1_blocker;
    }

    public function setA1Blocker(int $a1_blocker): self
    {
        $this->a1_blocker = $a1_blocker;

        return $this;
    }

    public function getA1Critical(): ?int
    {
        return $this->a1_critical;
    }

    public function setA1Critical(int $a1_critical): self
    {
        $this->a1_critical = $a1_critical;

        return $this;
    }

    public function getA1Major(): ?int
    {
        return $this->a1_major;
    }

    public function setA1Major(int $a1_major): self
    {
        $this->a1_major = $a1_major;

        return $this;
    }

    public function getA1Info(): ?int
    {
        return $this->a1_info;
    }

    public function setA1Info(int $a1_info): self
    {
        $this->a1_info = $a1_info;

        return $this;
    }

    public function getA1Minor(): ?int
    {
        return $this->a1_minor;
    }

    public function setA1Minor(int $a1_minor): self
    {
        $this->a1_minor = $a1_minor;

        return $this;
    }

    public function getA2Blocker(): ?int
    {
        return $this->a2_blocker;
    }

    public function setA2Blocker(int $a2_blocker): self
    {
        $this->a2_blocker = $a2_blocker;

        return $this;
    }

    public function getA2Critical(): ?int
    {
        return $this->a2_critical;
    }

    public function setA2Critical(int $a2_critical): self
    {
        $this->a2_critical = $a2_critical;

        return $this;
    }

    public function getA2Major(): ?int
    {
        return $this->a2_major;
    }

    public function setA2Major(int $a2_major): self
    {
        $this->a2_major = $a2_major;

        return $this;
    }

    public function getA2Info(): ?int
    {
        return $this->a2_info;
    }

    public function setA2Info(int $a2_info): self
    {
        $this->a2_info = $a2_info;

        return $this;
    }

    public function getA2Minor(): ?int
    {
        return $this->a2_minor;
    }

    public function setA2Minor(int $a2_minor): self
    {
        $this->a2_minor = $a2_minor;

        return $this;
    }

    public function getA3Blocker(): ?int
    {
        return $this->a3_blocker;
    }

    public function setA3Blocker(int $a3_blocker): self
    {
        $this->a3_blocker = $a3_blocker;

        return $this;
    }

    public function getA3Critical(): ?int
    {
        return $this->a3_critical;
    }

    public function setA3Critical(int $a3_critical): self
    {
        $this->a3_critical = $a3_critical;

        return $this;
    }

    public function getA3Major(): ?int
    {
        return $this->a3_major;
    }

    public function setA3Major(int $a3_major): self
    {
        $this->a3_major = $a3_major;

        return $this;
    }

    public function getA3Info(): ?int
    {
        return $this->a3_info;
    }

    public function setA3Info(int $a3_info): self
    {
        $this->a3_info = $a3_info;

        return $this;
    }

    public function getA3Minor(): ?int
    {
        return $this->a3_minor;
    }

    public function setA3Minor(int $a3_minor): self
    {
        $this->a3_minor = $a3_minor;

        return $this;
    }

    public function getA4Blocker(): ?int
    {
        return $this->a4_blocker;
    }

    public function setA4Blocker(int $a4_blocker): self
    {
        $this->a4_blocker = $a4_blocker;

        return $this;
    }

    public function getA4Critical(): ?int
    {
        return $this->a4_critical;
    }

    public function setA4Critical(int $a4_critical): self
    {
        $this->a4_critical = $a4_critical;

        return $this;
    }

    public function getA4Major(): ?int
    {
        return $this->a4_major;
    }

    public function setA4Major(int $a4_major): self
    {
        $this->a4_major = $a4_major;

        return $this;
    }

    public function getA4Info(): ?int
    {
        return $this->a4_info;
    }

    public function setA4Info(int $a4_info): self
    {
        $this->a4_info = $a4_info;

        return $this;
    }

    public function getA4Minor(): ?int
    {
        return $this->a4_minor;
    }

    public function setA4Minor(int $a4_minor): self
    {
        $this->a4_minor = $a4_minor;

        return $this;
    }

    public function getA5Blocker(): ?int
    {
        return $this->a5_blocker;
    }

    public function setA5Blocker(int $a5_blocker): self
    {
        $this->a5_blocker = $a5_blocker;

        return $this;
    }

    public function getA5Critical(): ?int
    {
        return $this->a5_critical;
    }

    public function setA5Critical(int $a5_critical): self
    {
        $this->a5_critical = $a5_critical;

        return $this;
    }

    public function getA5Major(): ?int
    {
        return $this->a5_major;
    }

    public function setA5Major(int $a5_major): self
    {
        $this->a5_major = $a5_major;

        return $this;
    }

    public function getA5Info(): ?int
    {
        return $this->a5_info;
    }

    public function setA5Info(int $a5_info): self
    {
        $this->a5_info = $a5_info;

        return $this;
    }

    public function getA5Minor(): ?int
    {
        return $this->a5_minor;
    }

    public function setA5Minor(int $a5_minor): self
    {
        $this->a5_minor = $a5_minor;

        return $this;
    }

    public function getA6Blocker(): ?int
    {
        return $this->a6_blocker;
    }

    public function setA6Blocker(int $a6_blocker): self
    {
        $this->a6_blocker = $a6_blocker;

        return $this;
    }

    public function getA6Critical(): ?int
    {
        return $this->a6_critical;
    }

    public function setA6Critical(int $a6_critical): self
    {
        $this->a6_critical = $a6_critical;

        return $this;
    }

    public function getA6Major(): ?int
    {
        return $this->a6_major;
    }

    public function setA6Major(int $a6_major): self
    {
        $this->a6_major = $a6_major;

        return $this;
    }

    public function getA6Info(): ?int
    {
        return $this->a6_info;
    }

    public function setA6Info(int $a6_info): self
    {
        $this->a6_info = $a6_info;

        return $this;
    }

    public function getA6Minor(): ?int
    {
        return $this->a6_minor;
    }

    public function setA6Minor(int $a6_minor): self
    {
        $this->a6_minor = $a6_minor;

        return $this;
    }

    public function getA7Blocker(): ?int
    {
        return $this->a7_blocker;
    }

    public function setA7Blocker(int $a7_blocker): self
    {
        $this->a7_blocker = $a7_blocker;

        return $this;
    }

    public function getA7Critical(): ?int
    {
        return $this->a7_critical;
    }

    public function setA7Critical(int $a7_critical): self
    {
        $this->a7_critical = $a7_critical;

        return $this;
    }

    public function getA7Major(): ?int
    {
        return $this->a7_major;
    }

    public function setA7Major(int $a7_major): self
    {
        $this->a7_major = $a7_major;

        return $this;
    }

    public function getA7Info(): ?int
    {
        return $this->a7_info;
    }

    public function setA7Info(int $a7_info): self
    {
        $this->a7_info = $a7_info;

        return $this;
    }

    public function getA7Minor(): ?int
    {
        return $this->a7_minor;
    }

    public function setA7Minor(int $a7_minor): self
    {
        $this->a7_minor = $a7_minor;

        return $this;
    }

    public function getA8Blocker(): ?int
    {
        return $this->a8_blocker;
    }

    public function setA8Blocker(int $a8_blocker): self
    {
        $this->a8_blocker = $a8_blocker;

        return $this;
    }

    public function getA8Critical(): ?int
    {
        return $this->a8_critical;
    }

    public function setA8Critical(int $a8_critical): self
    {
        $this->a8_critical = $a8_critical;

        return $this;
    }

    public function getA8Major(): ?int
    {
        return $this->a8_major;
    }

    public function setA8Major(int $a8_major): self
    {
        $this->a8_major = $a8_major;

        return $this;
    }

    public function getA8Info(): ?int
    {
        return $this->a8_info;
    }

    public function setA8Info(int $a8_info): self
    {
        $this->a8_info = $a8_info;

        return $this;
    }

    public function getA8Minor(): ?int
    {
        return $this->a8_minor;
    }

    public function setA8Minor(int $a8_minor): self
    {
        $this->a8_minor = $a8_minor;

        return $this;
    }

    public function getA9Blocker(): ?int
    {
        return $this->a9_blocker;
    }

    public function setA9Blocker(int $a9_blocker): self
    {
        $this->a9_blocker = $a9_blocker;

        return $this;
    }

    public function getA9Critical(): ?int
    {
        return $this->a9_critical;
    }

    public function setA9Critical(int $a9_critical): self
    {
        $this->a9_critical = $a9_critical;

        return $this;
    }

    public function getA9Major(): ?int
    {
        return $this->a9_major;
    }

    public function setA9Major(int $a9_major): self
    {
        $this->a9_major = $a9_major;

        return $this;
    }

    public function getA9Info(): ?int
    {
        return $this->a9_info;
    }

    public function setA9Info(int $a9_info): self
    {
        $this->a9_info = $a9_info;

        return $this;
    }

    public function getA9Minor(): ?int
    {
        return $this->a9_minor;
    }

    public function setA9Minor(int $a9_minor): self
    {
        $this->a9_minor = $a9_minor;

        return $this;
    }

    public function getA10Blocker(): ?int
    {
        return $this->a10_blocker;
    }

    public function setA10Blocker(int $a10_blocker): self
    {
        $this->a10_blocker = $a10_blocker;

        return $this;
    }

    public function getA10Critical(): ?int
    {
        return $this->a10_critical;
    }

    public function setA10Critical(int $a10_critical): self
    {
        $this->a10_critical = $a10_critical;

        return $this;
    }

    public function getA10Major(): ?int
    {
        return $this->a10_major;
    }

    public function setA10Major(int $a10_major): self
    {
        $this->a10_major = $a10_major;

        return $this;
    }

    public function getA10Info(): ?int
    {
        return $this->a10_info;
    }

    public function setA10Info(int $a10_info): self
    {
        $this->a10_info = $a10_info;

        return $this;
    }

    public function getA10Minor(): ?int
    {
        return $this->a10_minor;
    }

    public function setA10Minor(int $a10_minor): self
    {
        $this->a10_minor = $a10_minor;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->date_enregistrement;
    }

    public function setDateEnregistrement(\DateTimeInterface $date_enregistrement): self
    {
        $this->date_enregistrement = $date_enregistrement;

        return $this;
    }

}
