<?php
// src/FileDistributionBundle/Crud/Batch/UnlockAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\HostBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

class UnlockAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::LOCK;

    public function getName()
    {
        return 'unlock';
    }

    public function getLabel()
    {
        return _('Unlock');
    }

    public function getTooltip()
    {
        return _('Unlocks the selected computers.');
    }

    public function getListIcon()
    {
        return 'pro-unlock';
    }

    public function execute(ArrayCollection $entities)
    {
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            $messages[] = $this->createFlashMessage('success', __('%s unlocked successful.', (string)$entity->getName()));
        }
        
        $bag = $this->getFileDistributionManager()->unlock($entities);
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        return $bag;
    }
    
    /**
     * @param CrudInterface $object
     * @param UserInterface $user
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->getAuthorizationChecker()->isGranted(Privilege::LOCK);
    }
}
