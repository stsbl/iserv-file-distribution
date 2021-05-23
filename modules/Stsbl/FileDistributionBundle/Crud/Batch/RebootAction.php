<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Security\Privilege;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;

final class RebootAction extends AbstractFileDistributionAction
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
        return 'reboot';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _p('host', 'Reboot');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Reboots the selected computers.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'pro-restart';
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
            $messages[] = $this->createFlashMessage('success', __('Sent reboot command to %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->reboot($entities);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }
}
