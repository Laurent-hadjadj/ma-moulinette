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

declare(strict_types=1);

namespace App\Listener;

use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Equipe;
use App\Entity\Portefeuille;
use App\Entity\Utilisateur;

final class EasyAdminListener implements EventSubscriberInterface
{
    /**
     * [Description for $name]
     * name='%name'
     *
     * @var string
     */
    public static $name = '%name%';

    /**
     * [Description for __construct]
     * RequestStack, EntityManagerInterface
     *
     * @param  private
     *
     * Created at: 12/02/2023, 13:55:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $em
    ) {
        $this->rs = $requestStack;
        $this->em = $em;
    }

    /**
     * [Description for getSubscribedEvents]
     * Création des listener pour les services CRUD
     *
     * @return array
     *
     * Created at: 12/02/2023, 13:56:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getSubscribedEvents(): array
    {
        return [
          AfterEntityPersistedEvent::class => ['flashMessageAfterPersist'],
          AfterEntityUpdatedEvent::class => ['flashMessageAfterUpdate'],
          AfterEntityDeletedEvent::class => ['flashMessageAfterDelete'],
          AfterCrudActionEvent::class => ['ignoreFollowingFlashMsgIfXhrUpdate']
        ];
    }

    /**
     * [Description for ignoreFollowingFlashMsgIfXhrUpdate]
     *  Eviter d'afficher plusieurs fois le même message lors d'appel Ajax.
     *
     * @param mixed $e
     *
     * @return [type]
     *
     * Created at: 13/02/2023, 08:50:52 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function ignoreFollowingFlashMsgIfXhrUpdate($e)
    {
        if (!is_null($e->getAdminContext()->getRequest()->query->get('fieldName'))) {
            $e->getAdminContext()
              ->getRequest()
              ->getSession()->set('ignoreFollowingFlashMsg', true);
        }

        /**
         * Utilisation pour afficher le message ?
         * `if ($this->rs->getSession()->get('ignoreFollowingFlashMsg')) {
         *   $this->rs->getSession()->set('ignoreFollowingFlashMsg', false);
         *   }`
         */
    }

    /**
     * [Description for flashMessageAfterPersist]
     * Affiche un message à l'utilsateur lorsque l'enregistrement a été effectué.
     *
     * @param AfterEntityPersistedEvent $event
     *
     * @return void
     *
     * Created at: 13/02/2023, 08:51:37 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function flashMessageAfterPersist(AfterEntityPersistedEvent $event): void
    {
        /** On exclu  la class Utilisateur */
        $entity = $event->getEntityInstance();
        if ($entity instanceof Utilisateur) {
            return;
        }

        /** On affiche un message à l'utilisateur */
        if (is_null($entity->getId())) {
            $this->rs->getSession()->getFlashBag()
              ->add(
                  'warning',
                  new TranslatableMessage(
                      'content_admin.flash_message.unique',
                      [static::$name => $entity->getTitre()],
                      'admin'
                  )
              );
        } else {
            $this->rs->getSession()->getFlashBag()
            ->add(
                'success',
                new TranslatableMessage(
                    'content_admin.flash_message.create',
                    [static::$name => $entity->getTitre()],
                    'admin'
                )
            );
        }
    }

    /**
     * [Description for flashMessageAfterUpdate]
      * Affiche un message à l'utilsateur lorsque l'enregistrement a été mis à jour.   *
     * @param AfterEntityUpdatedEvent $event
     *
     * @return void
     *
     * Created at: 13/02/2023, 09:01:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function flashMessageAfterUpdate(AfterEntityUpdatedEvent $event): void
    {
        /** On exclu  la class Utilisateur */
        $entity = $event->getEntityInstance();
        if ($entity instanceof Utilisateur) {
            return;
        }

        /** On affiche un message à l'utilisateur */
        if ($entity->getId() === 0) {
            $this->rs->getSession()->getFlashBag()
              ->add(
                  'warning',
                  new TranslatableMessage(
                      'content_admin.flash_message.unique',
                      [static::$name => $entity->getTitre()],
                      'admin'
                  )
              );
        } else {
            $this->rs->getSession()->getFlashBag()
                ->add(
                    'success',
                    new TranslatableMessage(
                        'content_admin.flash_message.update',
                        [static::$name => $entity->getTitre()],
                        'admin'
                    )
                );
        }
    }

    /**
     * [Description for flashMessageAfterDelete]
     * Affiche un message à l'utilsateur lorsque l'enregistrement a été supprimé.
     * @param AfterEntityDeletedEvent $event
     *
     * @return void
     *
     * Created at: 13/02/2023, 09:03:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function flashMessageAfterDelete(AfterEntityDeletedEvent $event): void
    {
        /** On exclu  la class Utilisateur */
        $entity = $event->getEntityInstance();
        if ($entity instanceof Utilisateur) {
            return;
        }

        $this->rs->getSession()->getFlashBag()
            ->add('success', new TranslatableMessage(
                'content_admin.flash_message.delete',
                [static::$name => $entity->getTitre()],
                'admin'
            ));
    }
}
