<?php
// src/IServ/Stsbl/FileDistributionBundle/Crud/Batch/LockAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

class ExamAction extends AbstractFileDistributionAction
{
    protected $privileges = Privilege::EXAM;

    /**
     * @var string
     */
    private $title;
    
    /**
     * Set exam title
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    public function getName()
    {
        return 'exam';
    }

    public function getLabel()
    {
        return _('Enable');
    }

    public function getTooltip()
    {
        return _('Switch the selected computers into exam mode.');
    }

    public function getListIcon()
    {
        return 'pro-disk-open';
    }
    
    public function getGroup() 
    {
        return _('Exam Mode');
    }

    public function execute(ArrayCollection $entities)
    {
        $messages = [];
        $error = false;
        
        foreach ($entities as $key => $entity) {
            $skipOwnHost = false;
            
            if (empty($this->title)) {
               $messages[] = $this->createFlashMessage('error', _('Title should not be empty!'));
               $error = true;
               break;
            } 
            
            if ($entity->getIp() === $this->crud->getRequest()->getClientIp() && count($entities) > 1) {
                $messages[] = $this->createFlashMessage('warning', _('Skipping own host!'));
                unset($entities[$key]);
                $skipOwnHost = true;
            }
            
            if (!$skipOwnHost) {
                $messages[] = $this->createFlashMessage('success', __('Switched %s to exam mode.', (string)$entity->getName()));
            }
        }
        
        if (!$error) {
            $bag = $this->getFileDistributionManager()->examOn($entities, $this->title);
        } else {
            $bag = new FlashMessageBag();
        }
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
        return $this->crud->getAuthorizationChecker()->isGranted(Privilege::EXAM);
    }
}
