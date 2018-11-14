<?php
// src/IServ/Stsbl/FileDistributionBundle/Crud/Batch/LockAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

class ExamOffAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::EXAM;
    
    public function getName()
    {
        return 'exam_off';
    }

    public function getLabel()
    {
        return _('Disable');
    }

    public function getTooltip()
    {
        return _('Disable exam mode on the selected computers.');
    }

    public function getListIcon()
    {
        return 'pro-disk-save';
    }
    
    public function getGroup() 
    {
        return _('Exam Mode');
    }

    public function execute(ArrayCollection $entities)
    {
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            $messages[] = $this->createFlashMessage('success', __('Disabled exam mode on %s.', (string)$entity->getName()));
        }
            
        $bag = $this->getFileDistributionManager()->examOff($entities);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        return $bag;
    }
    
    /**
     * @param CrudInterface $object
     * @param UserInterface $user
     * @return boolean
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->getAuthorizationChecker()->isGranted(Privilege::EXAM);
    }
}
