<?php
// src/Stsbl/FileDistributionBundle/ShutdownCancelAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Security\Privilege;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;

class ShutdownCancelAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::BOOT;

    public function getName()
    {
        return 'shutdown_cancel';
    }

    public function getLabel()
    {
        return _('Cancel');
    }

    public function getTooltip()
    {
        return _('Cancels a sent shutdown/reboot/log off command on the selected computers.');
    }

    public function getListIcon()
    {
        return 'ban-circle';
    }

    public function getGroup()
    {
        return _('Start & Shutdown');
    }

    public function execute(ArrayCollection $entities)
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
