<?php
// src/IServ/Stsbl/FileDistributionBundle/Batch/PowerOnAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Security\Privilege;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;

class PowerOnAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    use Traits\NoopFormTrait;
    
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
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        return $bag;
    }
}
