<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

final class LockAction extends AbstractFileDistributionAction
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
        return 'lock';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Lock');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Locks the selected computers. The logged-in users cannot use the computers while they are locked.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'pro-lock';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        /** @var FileDistribution[] $entities */
        $hosts = [];

        foreach ($entities as $entity) {
            $hosts[] = $entity->getHost();
        }
        $messages = [];

        foreach ($hosts as $key => $entity) {
            $skipOwnHost = false;

            $request = $this->crud->request();
            $clientIp = $request ? $request->getClientIp() : null;

            if (null === $clientIp || $entity->getIp() === $clientIp) {
                $messages[] = $this->createFlashMessage('warning', _('Skipping own host!'));
                unset($hosts[$key]);
                $skipOwnHost = true;
            }

            if (!$skipOwnHost) {
                $messages[] = $this->createFlashMessage('success', __('Locked %s.', (string)$entity->getName()));
            }
        }

        if (count($hosts) > 0) {
            $lockManager = $this->crud->lockManager();

            if (null === $lockManager) {
                $bag = new FlashMessageBag();
                $bag->addError(_('Lock manager not available.'));

                return $bag;
            }

            $bag = $lockManager->lock($hosts);
        } else {
            $bag = new FlashMessageBag();
        }

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
