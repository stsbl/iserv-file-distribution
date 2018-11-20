<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/EnableAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Crud\Batch\AbstractHostAction;
use IServ\ComputerBundle\Crud\HostControlCrud;
use IServ\CrudBundle\Crud\Batch\FormExtendingBatchActionInterface;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessage;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use Stsbl\FileDistributionBundle\DependencyInjection\HostExtensionAwareInterface;
use Stsbl\FileDistributionBundle\DependencyInjection\HostExtensionAwareTrait;
use Stsbl\FileDistributionBundle\DependencyInjection\ManagerAwareInterface;
use Stsbl\FileDistributionBundle\DependencyInjection\ManagerAwareTrait;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * FileDistribution enable batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class EnableAction extends AbstractHostAction implements
    FormExtendingBatchActionInterface,
    GroupableBatchActionInterface,
    HostExtensionAwareInterface,
    ManagerAwareInterface
{
    use HostExtensionAwareTrait, ManagerAwareTrait;

    /**
     * @var HostControlCrud
     */
    protected $crud;

    /**
     * {@inheritdoc}
     */
    protected $privilege = Privilege::USE_FD;
    
    /**
     * @var string
     */
    private $title;
    
    /**
     * @var bool
     */
    private $isolation;
    
    /**
     * @var string
     */
    private $folderAvailability;
    
    /**
     * {@inheritdoc}
     */
    public function finalizeForm(FormInterface $form)
    {
        $form
            ->add('title', TextType::class, [
                'label' => _('File distribution title'),
                'constraints' => [
                    new NotBlank(['message' => _('Please enter a title for your file distribution.')])
                ],
                'attr' => [
                    'placeholder' => _('Title for this file distribution'),
                    'help_text' => _('The folder path where you will find the assignment folder and the returns will ' .
                        'be Files/File-Distribution/<Title>.'),
                    'required' => 'required'
                ]
            ]);
        
        $isolationAttr = [];
        if ($this->getHostExtension()->getConfig()->get('FileDistributionHostIsolationDefault')) {
            $isolationAttr['checked'] = 'checked';
        }
        $isolationAttr['help_text'] = _('Enable host isolation if you want to prevent that users can exchange files ' .
            'by sharing their accounts.');
        
        $form
            ->add('isolation', CheckboxType::class, [
                'label' => _('Host isolation'),
                'attr' => $isolationAttr,
            ])
            ->add('folder_availability', ChoiceType::class, [
                'label' => _('Availability of group folders and shares'),
                'choices' => [
                    _('Keep group folders and other shares available') => 'keep',
                    _('Allow only read access to group folders and other shares') => 'readonly',
                    _('Replace group folders and other shares with empty folders') => 'replace',
                ],
                'expanded' => true,
                'required' => true,
                'data' => $this->crud->getConfig()->get('FileDistributionFolderAvailabilityDefault'),
                'constraints' => [
                    new NotBlank(['message' => _('Please choose availability of group folders and shares.')]),
                ]
            ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function handleFormData(array $data)
    {
        $this->title = $data['title'];
        if (empty($data['isolation'])) {
            $this->isolation = false;
        } else {
            $this->isolation = (bool)$data['isolation'];
        }
        $this->folderAvailability = $data['folder_availability'];
        
        return $this->execute($data['multi']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute(ArrayCollection $entities)
    {
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $user = $this->crud->getUser();
        $messages = [];
        $error = false;
        
        if ($this->isolation === null) {
            throw new \InvalidArgumentException('Parameter isolation is not set!');
        }
        
        foreach ($entities as $key => $entity) {
            $skipOwnHost = false;
            
            if (empty($this->title)) {
                $messages[] = new FlashMessage('error', _('Title should not be empty!'));
                $error = true;
                break;
            }
            
            if ($entity->getIp() === $this->crud->getRequest()->getClientIp() && count($entities) > 1) {
                $messages[] = new FlashMessage('warning', _('Skipping own host!'));
                unset($entities[$key]);
                $skipOwnHost = true;
            }
            
            if (!$this->isAllowedToExecute($entity, $user)) {
                // remove unallowed hosts
                $messages[] = new FlashMessage(
                    'error',
                    __('You are not allowed to enable file distribution for %s.', $entity->getName())
                );
                unset($entities[$key]);
            } elseif (!$skipOwnHost) {
                $messages[] = new FlashMessage(
                    'success',
                    __('Enabled file distribution for %s.', $entity->getName())
                );
            }
        }
        
        // only execute rpc, if we have no errors and at least one entity
        if (!$error && count($entities) > 0) {
            $bag = $this
                ->getFileDistributionManager()
                ->enableFileDistribution($entities, $this->title, $this->isolation, $this->folderAvailability)
            ;
        } else {
            $bag = new FlashMessageBag();
        }
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        $this->getHostExtension()->getSession()->set('fd_title', $this->title);
        
        return $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'enable';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return _('Start');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip()
    {
        return _('Start a file distribution for the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon()
    {
        return 'pro-disk-open';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmClass()
    {
        return 'primary';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        return _('File distribution');
    }

    /**
     * @param CrudInterface $object
     * @param UserInterface $user
     * @return boolean
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user)
    {
        /** @var $object Host */
        return $this->getHostExtension()->getPrivilegeDetector()->isAllowedToEnable($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmationTemplate()/*: ?string*/
    {
        return 'StsblFileDistributionBundle:Crud:file_distribution_batch_confirm.html.twig';
    }
}
