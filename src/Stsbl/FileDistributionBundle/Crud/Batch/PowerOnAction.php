<?php
// src/IServ/Stsbl/FileDistributionBundle/Batch/PowerOnAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\HostBundle\Security\Privilege;

class PowerOnAction extends AbstractFileDistributionAction
{
    protected $privileges = Privilege::BOOT;

    public function getName()
    {
        return 'power_on';
    }

    public function getLabel()
    {
        return _('Power On');
    }

    public function getTooltip()
    {
        return _('Sends Wake-on-LAN packets to the selected computers to wake them.');
    }

    public function getListIcon()
    {
        return 'pro-remote-control';
    }

    public function getGroup()
    {
        return _('Start & Shutdown');
    }

    public function execute(ArrayCollection $entities)
    {
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            if ($entity->getMac() != null) {
                $messages[] = $this->createFlashMessage('success', __('Sent power on command to %s.', (string)$entity->getName()));
            }
        }
        
        $bag = $this->getFileDistributionManager()->wol($entities);
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        return $bag;
    }
}
