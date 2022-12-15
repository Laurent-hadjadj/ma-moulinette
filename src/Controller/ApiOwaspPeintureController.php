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

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;

class ApiOwaspPeintureController extends AbstractController
{
	public function __construct(private EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	/**
	 * [Description for peintureOwaspListe]
	 * On récupère les résultats Owasp
	 *
	 * @param Request $request
	 *
	 * @return response
	 *
	 * Created at: 15/12/2022, 21:19:10 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	#[Route('/api/peinture/owasp/liste', name: 'peinture_owasp_liste', methods: ['GET'])]
	public function peintureOwaspListe(Request $request): response
	{
		$mavenKey = $request->get('mavenKey');
		$response = new JsonResponse();

		/** On récupère les failles owasp */
		$sql = "SELECT * FROM owasp WHERE maven_key='${mavenKey}'
						ORDER BY date_enregistrement DESC LIMIT 1";

		$list = $this->em->getConnection()->prepare($sql)->executeQuery();
		$owasp = $list->fetchAllAssociative();

		/** si on ne trouve pas la liste */
		if (empty($owasp)) {
			return $response->setData(["code" => 406]);
		}

		/** Informations */
		$total = $owasp[0]["a1"] + $owasp[0]["a2"] + $owasp[0]["a3"] + $owasp[0]["a4"]
						+ $owasp[0]["a5"] + $owasp[0]["a6"] + $owasp[0]["a7"] + $owasp[0]["a8"]
						+ $owasp[0]["a9"] + $owasp[0]["a10"];

		$bloquant = $owasp[0]["a1_blocker"] + $owasp[0]["a2_blocker"] + $owasp[0]["a3_blocker"]
							+ $owasp[0]["a4_blocker"] + $owasp[0]["a5_blocker"] + $owasp[0]["a6_blocker"]
							+ $owasp[0]["a7_blocker"] + $owasp[0]["a8_blocker"] + $owasp[0]["a9_blocker"]
							+ $owasp[0]["a10_blocker"];

		$critique = $owasp[0]["a1_critical"] + $owasp[0]["a2_critical"]
							+ $owasp[0]["a3_critical"] + $owasp[0]["a4_critical"]
							+ $owasp[0]["a5_critical"] + $owasp[0]["a6_critical"]
							+ $owasp[0]["a7_critical"] + $owasp[0]["a8_critical"]
							+ $owasp[0]["a9_critical"] + $owasp[0]["a10_critical"];

		$majeur = $owasp[0]["a1_major"] + $owasp[0]["a2_major"] + $owasp[0]["a3_major"]
						+ $owasp[0]["a4_major"] + $owasp[0]["a5_major"] + $owasp[0]["a6_major"]
						+ $owasp[0]["a7_major"] + $owasp[0]["a8_major"] + $owasp[0]["a9_major"]
						+ $owasp[0]["a10_major"];

		$mineur = $owasp[0]["a1_minor"] + $owasp[0]["a2_minor"] + $owasp[0]["a3_minor"]
						+ $owasp[0]["a4_minor"] + $owasp[0]["a5_minor"] + $owasp[0]["a6_minor"]
						+ $owasp[0]["a7_minor"] + $owasp[0]["a8_minor"] + $owasp[0]["a9_minor"]
						+ $owasp[0]["a10_minor"];

		return $response->setData(
			[
				"code" => 200, "total" => $total,
				"bloquant" => $bloquant,
				"critique" => $critique,
				"majeur" => $majeur,
				"mineur" => $mineur,
				"a1" => $owasp[0]["a1"], "a2" => $owasp[0]["a2"],
				"a3" => $owasp[0]["a3"], "a4" => $owasp[0]["a4"],
				"a5" => $owasp[0]["a5"], "a6" => $owasp[0]["a6"],
				"a7" => $owasp[0]["a7"], "a8" => $owasp[0]["a8"],
				"a9" => $owasp[0]["a9"], "a10" => $owasp[0]["a10"],
				"a1Blocker" => $owasp[0]["a1_blocker"],
				"a2Blocker" => $owasp[0]["a2_blocker"],
				"a3Blocker" => $owasp[0]["a3_blocker"],
				"a4Blocker" => $owasp[0]["a4_blocker"],
				"a5Blocker" => $owasp[0]["a5_blocker"],
				"a6Blocker" => $owasp[0]["a6_blocker"],
				"a7Blocker" => $owasp[0]["a7_blocker"],
				"a8Blocker" => $owasp[0]["a8_blocker"],
				"a9Blocker" => $owasp[0]["a9_blocker"],
				"a10Blocker" => $owasp[0]["a10_blocker"],
				"a1Critical" => $owasp[0]["a1_critical"],
				"a2Critical" => $owasp[0]["a2_critical"],
				"a3Critical" => $owasp[0]["a3_critical"],
				"a4Critical" => $owasp[0]["a4_critical"],
				"a5Critical" => $owasp[0]["a5_critical"],
				"a6Critical" => $owasp[0]["a6_critical"],
				"a7Critical" => $owasp[0]["a7_critical"],
				"a8Critical" => $owasp[0]["a8_critical"],
				"a9Critical" => $owasp[0]["a9_critical"],
				"a10Critical" => $owasp[0]["a10_critical"],
				"a1Major" => $owasp[0]["a1_major"],
				"a2Major" => $owasp[0]["a2_major"],
				"a3Major" => $owasp[0]["a3_major"],
				"a4Major" => $owasp[0]["a4_major"],
				"a5Major" => $owasp[0]["a5_major"],
				"a6Major" => $owasp[0]["a6_major"],
				"a7Major" => $owasp[0]["a7_major"],
				"a8Major" => $owasp[0]["a8_major"],
				"a9Major" => $owasp[0]["a9_major"],
				"a10Major" => $owasp[0]["a10_major"],
				"a1Minor" => $owasp[0]["a1_minor"],
				"a2Minor" => $owasp[0]["a2_minor"],
				"a3Minor" => $owasp[0]["a3_minor"],
				"a4Minor" => $owasp[0]["a4_minor"],
				"a5Minor" => $owasp[0]["a5_minor"],
				"a6Minor" => $owasp[0]["a6_minor"],
				"a7Minor" => $owasp[0]["a7_minor"],
				"a8Minor" => $owasp[0]["a8_minor"],
				"a9Minor" => $owasp[0]["a9_minor"],
				"a10Minor" => $owasp[0]["a10_minor"],
				Response::HTTP_OK
			]
		);
	}

	/**
	 * [Description for peintureOwaspHotspotInfo]
   * On récupère les résultats des hotspots
		 *
	 * @param Request $request
	 * 
	 * @return response
	 * 
	 * Created at: 15/12/2022, 22:11:35 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com> 
	 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
	 */
	#[Route('/api/peinture/owasp/hotspot/info', name: 'peinture_owasp_hotspot_info', methods: ['GET'])]
 public function peintureOwaspHotspotInfo(Request $request): response
	{
		$mavenKey = $request->get('mavenKey');
		$response = new JsonResponse();

		/** On compte le nombre de hotspot REVIEWED */
		$sql = "SELECT count(*) as reviewed FROM hotspot_owasp
						WHERE maven_key='${mavenKey}' AND status='REVIEWED'";

		$list = $this->em->getConnection()->prepare($sql)->executeQuery();
		$reviewed = $list->fetchAllAssociative();

		/** On compte le nombre de hotspot TO_REVIEW */
		$sql = "SELECT count(*) as to_review FROM hotspot_owasp
						WHERE maven_key='${mavenKey}' AND status='TO_REVIEW'";

		$list = $this->em->getConnection()->prepare($sql)->executeQuery();
		$toReview = $list->fetchAllAssociative();

		/** On récupère le nombre de hotspot owasp par niveau de sévérité potentiel. */
		$sql = "SELECT probability, count(*) as total
						FROM hotspot_owasp WHERE maven_key='${mavenKey}'
						AND status='TO_REVIEW' GROUP BY probability";

		$count = $this->em->getConnection()->prepare($sql)->executeQuery();
		$probability = $count->fetchAllAssociative();
		$high = 0;
		$medium = 0;
		$low = 0;
		foreach ($probability as $elt) {
			if ($elt["probability"] == "HIGH") {
				$high = $elt["total"];
			}
			if ($elt["probability"] == "MEDIUM") {
				$medium = $elt["total"];
			}
			if ($elt["probability"] == "LOW") {
				$low = $elt["total"];
			}
		}

		return $response->setData(
			[
				"reviewed" => $reviewed[0]["reviewed"],
				"toReview" => $toReview[0]["to_review"],
				"total" => $reviewed[0]["reviewed"] + $toReview[0]["to_review"],
				"high" => $high,
				"medium" => $medium,
				"low" => $low,
				Response::HTTP_OK
			]
		);
	}

	/**
	 * [Description for peintureOwaspHotspotListe]
	 * On récupère les résultats de la table hotpsot_owasp
	 *
	 * @param Request $request
	 *
	 * @return response
	 *
	 * Created at: 15/12/2022, 21:20:20 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	#[Route('/api/peinture/owasp/hotspot/liste', name: 'peinture_owasp_hotspot_liste', methods: ['GET'])]
	public function peintureOwaspHotspotListe(Request $request): response
	{
		$mavenKey = $request->get('mavenKey');
		$response = new JsonResponse();

		/** On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED */
		$sql = "SELECT menace, count(*) as total FROM hotspot_owasp
						WHERE maven_key='${mavenKey}'
						AND status='TO_REVIEW' GROUP BY menace";

		$list = $this->em->getConnection()->prepare($sql)->executeQuery();
		$menaces = $list->fetchAllAssociative();

		$menaceA1=$menaceA2=$menaceA3=$menaceA4=$menaceA5=$menaceA6=$menaceA7=$menaceA8=$menaceA9=$menaceA10=0;

		foreach ($menaces as $elt) {
			if ($elt["menace"] === "a1") {
				$menaceA1 = $elt["total"];
			}
			if ($elt["menace"] === "a2") {
				$menaceA2 = $elt["total"];
			}
			if ($elt["menace"] === "a3") {
				$menaceA3 = $elt["total"];
			}
			if ($elt["menace"] === "a4") {
				$menaceA4 = $elt["total"];
			}
			if ($elt["menace"] === "a5") {
				$menaceA5 = $elt["total"];
			}
			if ($elt["menace"] === "a6") {
				$menaceA6 = $elt["total"];
			}
			if ($elt["menace"] === "a7") {
				$menaceA7 = $elt["total"];
			}
			if ($elt["menace"] === "a8") {
				$menaceA8 = $elt["total"];
			}
			if ($elt["menace"] === "a9") {
				$menaceA9 = $elt["total"];
			}
			if ($elt["menace"] === "a10") {
				$menaceA10 = $elt["total"];
			}
		}

		return $response->setData(
			[
				"menaceA1" => $menaceA1, "menaceA2" => $menaceA2,
				"menaceA3" => $menaceA3, "menaceA4" => $menaceA4,
				"menaceA5" => $menaceA5, "menaceA6" => $menaceA6,
				"menaceA7" => $menaceA7, "menaceA8" => $menaceA8,
				"menaceA9" => $menaceA9, "menaceA10" => $menaceA10,
				Response::HTTP_OK
			]
		);
	}

	/**
	 * [Description for peintureOwaspHotspotDetails]
	 * On récupère le détails des failles de type hotpsot
	 *
	 * @param Request $request
	 *
	 * @return response
	 *
	 * Created at: 15/12/2022, 21:20:54 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	#[Route('/api/peinture/owasp/hotspot/details', name: 'peinture_owasp_hotspot_details', methods: ['GET'])]
	public function peintureOwaspHotspotDetails(Request $request): response
	{
		$mavenKey = $request->get('mavenKey');
		$response = new JsonResponse();

		/** On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED */
		$sql = "SELECT * FROM hotspot_details
						WHERE maven_key='${mavenKey}'
						ORDER BY niveau ASC";

		$list = $this->em->getConnection()->prepare($sql)->executeQuery();
		$details = $list->fetchAllAssociative();

		if (empty($details)) {
			$details = "vide";
		}

		return $response->setData(["details" => $details, Response::HTTP_OK]);
	}

	/**
	 * [Description for peintureOwaspSeverity]
	 * On récupère le détails des failles de type hotpsot
	 *
	 * @param Request $request
	 *
	 * @return response
	 *
	 * Created at: 15/12/2022, 21:21:20 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	#[Route('/api/peinture/owasp/hotspot/severity', name: 'peinture_owasp_hotspot_severity', methods: ['GET'])]
	public function peintureOwaspSeverity(Request $request): response
	{
		$mavenKey = $request->get('mavenKey');
		$menace = $request->get('menace');
		$response = new JsonResponse();

		$strSelect = "SELECT count(*) as total FROM hotspot_owasp WHERE maven_key='";
		$strMenace = "' AND menace='";

		/** On compte le nombre de faille OWASP u statut HIGH */
		$sql = $strSelect . $mavenKey . $strMenace . $menace . "' AND status='TO_REVIEW' AND probability='HIGH'";
		$count = $this->em->getConnection()->prepare($sql)->executeQuery();
		$hhigh = $count->fetchAllAssociative();

		/** On compte le nombre de faille OWASP au statut MEDIUM */
		$sql = $strSelect . $mavenKey . $strMenace . $menace . "' AND status='TO_REVIEW' AND probability='MEDIUM'";
		$count = $this->em->getConnection()->prepare($sql)->executeQuery();
		$mmedium = $count->fetchAllAssociative();

		/**  n compte le nombre de faille OWASP au statut LOW */
		$sql = $strSelect . $mavenKey . $strMenace . $menace . "' AND status='TO_REVIEW' AND probability='LOW'";
		$count = $this->em->getConnection()->prepare($sql)->executeQuery();
		$llow = $count->fetchAllAssociative();

		/**
		 * On vérifie la valeur des vulnerabilité de type HIGH, MEDIUMet LOW
		 * Si la valeur est null alors on initialise à 0
		 */
		if (empty($hhigh)) {
			$high = 0;
		} else {
			$high = $hhigh[0];
		}

		if (empty($mmedium)) {
			$medium = 0;
		} else {
			$medium = $mmedium[0];
		}

		if (empty($llow)) {
			$low = 0;
		} else {
			$low = $llow[0];
		}

		return $response->setData(
			["high" => $high, "medium" => $medium, "low" => $low, Response::HTTP_OK]);
	}
}
