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

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;


/**
 * [Description RegistrationController]
 */
class RegistrationController extends AbstractController
{
    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
        $this->params = $params;
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array
     *
     * Created at: 21/12/2024 21:03:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for register]
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $em
     *
     * @return Response
     *
     * Created at: 15/12/2022, 21:07:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, ValidatorInterface $validator): Response
    {
        /**
         * Si on est déjà connecté
         * On affiche la page /accueil, Si on la page /login
         */
        if (!is_Null($this->getUser())) {
            return $this->redirectToRoute('accueil');
        }

        /** On créé un objet utilisateur. */
        $utilisateur = new Utilisateur();
        /** On prépare le formulaire. */
        $form = $this->createForm(RegistrationFormType::class, $utilisateur);
        /** On récupère la requête. */
        $form->handleRequest($request);

        /** je récupère les données du HoneyPot  */
        $honeyPot = $form->get('email')->getData();
        if (!empty(trim($honeyPot))) {
            // Spam detected!
            $warning = sprintf('🐛 SPAM detected. honeypot content: %s IP: %s', $honeyPot, $request->getClientIp());
            $this->logger->warning($warning);
        } else {
            /** Le formulaire est valide */
            if ($form->isSubmitted() && $form->isValid()) {
                // Récupérer le mot de passe en clair
                $plainPassword = $form->get('plainPassword')->getData();
                $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
                /** J'enregistre l'url de l'image */
                $avatar = $form->get('avatar')->getData();
                // Si avatar est null ou vide, on applique la valeur par défaut
                if (!$avatar || empty($avatar)) {
                    $utilisateur->setAvatar('personne.png');
                } else {
                    $utilisateur->setAvatar($avatar);
                }
                /** J'enregistre le nom en majuscule */
                $utilisateur->setNom(strtoupper($form->get('nom')->getData()));
                /** J'enregistre le Prénom */
                $utilisateur->setPrenom(ucfirst($form->get('prenom')->getData()));
                /** J'enregistre en base de données */
                $courriel = $form->get('courriel')->getData();
                /** On canonise l'adresse. */
                $utilisateur->setCourriel(strtolower($courriel));
                /** On hash le mot de passe */
                $utilisateur->setPassword($userPasswordHasher->hashPassword($utilisateur, $plainPassword));
                /** On désactive l'utilisateur */
                $utilisateur->setActif(false);
                /** En enregistre la date de création */
                $utilisateur->setDateEnregistrement($date);

                // Valider l'entité
                $errors = $validator->validate($utilisateur);
                if (count($errors) > 0) {
                    // Si des erreurs sont trouvées, afficher ces erreurs
                    foreach ($errors as $error) {
                        $this->logger->error("[Inscription] ❌ une erreur dans le formulaire a été détectée.", ['erreur' => $error->getMessage()]);
                        $this->addFlash('notice', ['type' => 'alert', 'message' => $error->getMessage()] );
                    }

                    $render=static::genericRender();
                    $render['registrationForm'] = $form->createView();
                    return $this->render('auth/register.html.twig', $render);
                }

                /** On enregistre */
                $this->em->persist($utilisateur);
                $this->em->flush();

                /** On prepare un message flash */
                $message = "📌 Votre compte a été correctement créé.";
                $this->addFlash('notice', [
                    'type' => 'primary',
                    'titre' => "[Inscription] ",
                    'message' => $message
                ]);

                /** On préfère rediriger l'utilisateur sur la page de bienvenue */
                return $this->redirectToRoute('welcome', [
                    'nom' => $utilisateur->getNom(),
                    'prenom' => $utilisateur->getPrenom(),
                    'courriel' => $utilisateur->getCourriel()
                ]);
            }
        }

        $render=static::genericRender();
        $render['registrationForm'] = $form->createView();
        return $this->render('auth/register.html.twig', $render);
    }

    /**
     * [Description for welcome]
     *
     * @return render
     *
     * Created at: 15/12/2022, 21:07:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/welcome', name: 'welcome')]
    public function welcome(Request $request): Response
    {
        //$content = $request->getContent();
        //$data = $request->toArray();
        //dd($data);
        $render=static::genericRender();
        $render['nom'] = 'HADJADJ';
        $render['prenom'] = 'Laurent';
        $render['courriel'] = 'laurent.hadjadj@ma-petite-entreprise.fr';
        return $this->render('welcome/index.html.twig', $render);
    }

}
