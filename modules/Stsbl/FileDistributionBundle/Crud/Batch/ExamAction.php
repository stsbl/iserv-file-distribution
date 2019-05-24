<?php
// src/IServ/Stsbl/FileDistributionBundle/Crud/Batch/LockAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExamAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    protected $privileges = Privilege::EXAM;
    
    /**
     * @var string
     */
    private $title;

    /**
     * @var Request|null
     */
    private $request;

    public function __construct(AbstractCrud $crud, ?Request $request, bool $enabled = true)
    {
        parent::__construct($crud, $enabled);

        $this->request = $request;
    }

    /**
     * Allows the batch action to manipulate the form.
     *
     * This is called at the end of `prepareBatchActions`.
     *
     * @param FormInterface $form
     */
    public function finalizeForm(FormInterface $form)
    {
        $form
            ->add('exam_title', TextType::class, [
                'label' => _('Exam title'),
                'constraints' => [
                    new NotBlank(['message' => _('Please enter a title for your exam.')])
                ],
                'attr' => [
                    'placeholder' => _('Title for this exam'),
                    'required' => 'required'
                ]
            ])
        ;
    }
    
    /**
     * Gets called with the full form data instead of `execute`.
     *
     * @param array $data
     * @return FlashMessageBag
     */
    public function handleFormData(array $data)
    {
        $this->title = $data['exam_title'];
        return $this->execute($data['multi']);
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
            
            if ($this->request && $entity->getIp() === $this->request->getClientIp() && $entities->count() > 1) {
                $messages[] = $this->createFlashMessage('warning', _('Skipping own host!'));
                unset($entities[$key]);
                $skipOwnHost = true;
            }
            
            if (!$skipOwnHost) {
                $messages[] = $this->createFlashMessage('success', __('Switched %s to exam mode.', $entity->getName()));
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
     * @return boolean
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user)
    {
        return $this->crud->getAuthorizationChecker()->isGranted(Privilege::EXAM);
    }
}
