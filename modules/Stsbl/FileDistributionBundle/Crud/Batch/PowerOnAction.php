<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Security\Privilege;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;

final class PowerOnAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

    protected $privileges = Privilege::BOOT;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'power_on';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Power On');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Sends Wake-on-LAN packets to the selected computers to wake them.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'pro-remote-control';
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
        /** @var FileDistribution[] $entities */
        $hosts = [];

        foreach ($entities as $entity) {
            $hosts[] = $entity->getHost();
        }
        $messages = [];

        foreach ($hosts as $entity) {
            if ($entity->getMac() !== null) {
                $messages[] = $this->createFlashMessage('success', __('Sent power on command to %s.', (string)$entity->getName()));
            }
        }

        $bag = $this->getFileDistributionManager()->wol($hosts);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }
}
