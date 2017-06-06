<?php
// src/IServ/Stsbl/FileDistributionBundle/Crud/Batch/LockAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\HostBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

class LockAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::LOCK;

    public function getName()
    {
        return 'lock';
    }

    public function getLabel()
    {
        return _('Lock');
    }

    public function getTooltip()
    {
        return _('Locks the selected computers. The logged-in users cannot use the computers while they are locked.');
    }

    public function getListIcon()
    {
        return 'pro-lock';
    }

    public function execute(ArrayCollection $entities)
    {
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            $skipOwnHost = false;
            
            if ($entity->getIp() === $this->crud->getRequest()->getClientIp() && count($entities) > 1) {
                $messages[] = $this->createFlashMessage('warning', _('Skipping own host!'));
                unset($entities[$key]);
                $skipOwnHost = true;
            }
            
            if (!$skipOwnHost) {
                $messages[] = $this->createFlashMessage('success', __('Switched %s into exam mode.', (string)$entity->getName()));
            }
        }
        
        $bag = $this->getFileDistributionManager()->lock($entities);
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
