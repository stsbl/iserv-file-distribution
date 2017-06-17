<?php
// src/Stsbl/FileDistributionBundle/Batch/RebootAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\HostBundle\Security\Privilege;

class RebootAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::BOOT;

    public function getName()
    {
        return 'reboot';
    }

    public function getLabel()
    {
        return _p('host', 'Reboot');
    }

    public function getTooltip()
    {
        return _('Reboots the selected computers.');
    }

    public function getListIcon()
    {
        return 'pro-restart';
    }

    public function getGroup()
    {
        return _('Start & Shutdown');
    }

    public function execute(ArrayCollection $entities)
    {
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            $messages[] = $this->createFlashMessage('success', __('Sent reboot command to %s.', (string)$entity->getName()));
        }
        
        $bag = $this->getFileDistributionManager()->reboot($entities);
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        return $bag;
    }
}
