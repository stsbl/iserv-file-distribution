<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Security\Privilege;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;

final class ShutdownCancelAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

    /**
     * {@inheritDoc}
     */
    protected $privileges = Privilege::BOOT;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'shutdown_cancel';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Cancel');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Cancels a sent shutdown/reboot/log off command on the selected computers.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'ban-circle';
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(): string
    {
        return _('Start & Shutdown');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        $messages = [];

        foreach ($entities as $key => $entity) {
            $messages[] = $this->createFlashMessage('success', __('Canceled schuelded actions for %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->cancelShutdown($entities);
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }
}
