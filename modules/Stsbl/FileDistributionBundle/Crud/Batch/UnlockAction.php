<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

final class UnlockAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

    /**
     * {@inheritDoc}
     */
    protected $privileges = Privilege::LOCK;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'unlock';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Unlock');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Unlocks the selected computers.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'pro-unlock';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        $messages = [];

        foreach ($entities as $key => $entity) {
            $messages[] = $this->createFlashMessage('success', __('%s unlocked successful.', (string)$entity->getName()));
        }

        $bag = $this->crud->lockManager()->unlock($entities);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user): bool
    {
        return $this->crud->authorizationChecker()->isGranted(Privilege::LOCK);
    }
}
