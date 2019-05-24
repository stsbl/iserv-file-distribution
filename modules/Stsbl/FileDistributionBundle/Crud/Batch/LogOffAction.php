<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/LogOffAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use Stsbl\FileDistributionBundle\Security\Privilege;

class LogOffAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::BOOT;

    public function getName()
    {
        return 'log_off';
    }

    public function getLabel()
    {
        return _('Log Off');
    }

    public function getTooltip()
    {
        return _('Forces users on the selected computers to log out.');
    }

    public function getListIcon()
    {
        return 'log-out';
    }

    public function getGroup()
    {
        return _('Start & Shutdown');
    }

    public function execute(ArrayCollection $entities)
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
