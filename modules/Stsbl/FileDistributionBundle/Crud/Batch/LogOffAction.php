<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Security\Privilege;

final class LogOffAction extends AbstractFileDistributionAction
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
        return 'log_off';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Log Off');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Forces users on the selected computers to log out.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'log-out';
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
            $messages[] = $this->createFlashMessage('success', __('Sent log off command to %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->logoff($entities);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }
}
