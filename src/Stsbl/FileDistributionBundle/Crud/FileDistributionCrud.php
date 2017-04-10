<?php
// src/Stsbl/FileDistributionBundle/Crud/FileDistributionCrud.php
namespace Stsbl\FileDistributionBundle\Crud;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Entity\Specification\PropertyMatchSpecification;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Table\Filter;
use IServ\CrudBundle\Table\ListHandler;
use IServ\CrudBundle\Table\Specification\FilterSearch;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\HostBundle\Util\Config as HostConfig;
use IServ\HostBundle\Util\Network;
use IServ\HostBundle\Security\Privilege as HostPrivilege;
use Stsbl\FileDistributionBundle\Crud\Batch;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Stsbl\FileDistributionBundle\Service\Rpc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
 * FileDistribution Crud
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionCrud extends AbstractCrud
{
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * @var Session
     */
    private $session;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var Rpc
     */
    private $rpc;
    
    /* SETTERS */
    
    /**
     * Set shell
     * 
     * @param Shell $shell
     */
    public function setShell(Shell $shell)
    {
        $this->shell = $shell;
    }
    
    /**
     * Set entity manager
     * 
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Set security handler
     * 
     * @param SecurityHandler $securityHandler
     */
    public function setSecurityHandler(SecurityHandler $securityHandler)
    {
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * Set session
     * 
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }
    
    /**
     * Set config
     * 
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
    
    /**
     * Set request via stack
     * 
     * @param RequestStack $stack
     */
    public function setRequest(RequestStack $stack)
    {
        $this->request = $stack->getCurrentRequest();
    }
    
    /**
     * Set rpc
     * 
     * @param Rpc $rpc
     */
    public function setRpc(Rpc $rpc)
    {
        $this->rpc = $rpc;
    }
    
    /* GETTERS */
    
    /**
     * Get shell
     * 
     * @return Shell|null
     */
    public function getShell()
    {
        return $this->shell;
    }
    
    /**
     * Get entity manager
     * 
     * @return EntityManager|null
     */
    public function getEntityManager()
    {
        return $this->em;
    }
    
    /**
     * Get security handler
     * 
     * @return SecurityHandler|null
     */
    public function getSecurityHandler()
    {
        return $this->securityHandler;
    }
    
    /**
     * Get session
     * 
     * @return Session|null
     */
    public function getSession()
    {
        return $this->session;
    }
    
    /**
     * Get config
     * 
     * @return Config|config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Get request
     * 
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Get rpc
     * 
     * @return Rpc|null
     */
    public function getRpc()
    {
        return $this->rpc;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->title = _('File distribution');
        $this->itemTitle = _p('file-distribution', 'Device');
        $this->id = 'filedistribution';
        $this->routesNamePrefix = 'fd_';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution';
        $this->options['sort'] = 'room';
        $this->templates['crud_index'] = 'StsblFileDistributionBundle:Crud:file_distribution_index.html.twig';
        $this->templates['crud_batch_confirm'] = 'StsblFileDistributionBundle:Crud:file_distribution_batch_confirm.html.twig';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildRoutes() 
    {
        parent::buildRoutes();
        
        $this->routes[self::ACTION_INDEX]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:index';
        //$this->routes[self::ACTION_SHOW]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:show';
        $this->routes['batch_confirm']['_controller'] = 'StsblFileDistributionBundle:FileDistribution:confirmBatch';
        $this->routes['batch']['_controller'] = 'StsblFileDistributionBundle:FileDistribution:batch';
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToView(CrudInterface $object = null, UserInterface $user = null) 
    {
        // disable show action, it is useless here
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowedToAdd(UserInterface $user = null) 
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToEdit(CrudInterface $object = null, UserInterface $user = null)
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToDelete(CrudInterface $object = null, UserInterface $user = null) 
    {
        return $this->isAllowedToEdit($object, $user);
    }
    
    /** 
     * Checks if user is allowed to stop a file distribution
     * 
     * @param CrudInterface $object
     * @param UserInterface $user
     */
    public function isAllowedToStop(CrudInterface $object, UserInterface $user = null) 
    {
        $fileDistribution = $this->getFileDistributionForHost($object);
        
        if ($fileDistribution === null) {
            return false;
        }
        
        if ($this->getUser() !== $fileDistribution->getUser()) {
            return false;
        }
        
        return true;
    }
    
    /** 
     * Checks if user is allowed to enable a file distribution
     * 
     * @param CrudInterface $object
     * @param UserInterface $user
     */
    public function isAllowedToEnable(CrudInterface $object, UserInterface $user = null)
    {
        $fileDistribution = $this->getFileDistributionForHost($object);
        
        if($fileDistribution === null) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilterSpecification()
    {
        // Only show controllable hosts
        return new PropertyMatchSpecification('controllable', true);
    }
    
    /**
     * @param CrudInterface $object
     * @return \Stsbl\FileDistributionBundle\Entity\FileDistribution|null
     */
    public function getFileDistributionForHost(CrudInterface $object)
    {
        /* @var $object \Stsbl\FileDistributionBundle\Entity\Host */
        /* @var $er \Doctrine\ORM\EntityRepository */
        /* @var $fileDistribution \Stsbl\FileDistributionBundle\Entity\FileDistribution */
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');
        try {
            $fileDistribution = $er->findOneBy(['ip' => $object->getIp()]);
            
            return $fileDistribution;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Checks if current request comes from LAN
     * 
     * @return bool
     */
    public function isInLan()
    {
        return Network::ipInLan(null, $this->getConfig()->get('LAN'), $this->getRequest());
    }
    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper) 
    {
        $activeFileDistributions = false;
        $activeSoundLocks = false;
        
        $listMapper
            ->add('name', null, [
                'label' => _('Name'),
                'responsive' => 'all',
                'sortType' => 'natural',
                'template' => 'StsblFileDistributionBundle:List:field_name.html.twig',
            ])
            ->add('type', null, [
                'label' => _('Type'),
                'template' => 'IServHostBundle:Crud:list_field_type.html.twig', 
                'responsive' => 'desktop'
            ])
        ;
         
        // check for existing file distribution
        $fileDistributionRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');
        
        // only add columns if we have file distributions
        if (count($fileDistributionRepository->findAll()) > 0) {
            $activeFileDistributions = true;
            
            $listMapper
                ->add('fileDistribution', null, [
                    'label' => _('File distribution'),
                    'group' => true,
                    // sort in the following order: fileDistribution, name, room \o/
                    'sortOrder' => [3, 1],
                    'sortType' => 'natural',
                    'template' => 'StsblFileDistributionBundle:List:field_filedistribution.html.twig',
                ])
                ->add('fileDistributionIsolation', null, [
                    'label' => '', // no title in table
                    'template' => 'StsblFileDistributionBundle:List:field_isolation.html.twig',
            ]);
        }
        
        // check for existing sound locks
        $soundLockRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:SoundLock');
        
        // only add soundlock column if we have a sound lock
        if (count($soundLockRepository->findAll()) > 0) {
            $activeSoundLocks = true;
            
            $listMapper->add('soundLock', null, [
                'label' => '', // no title in table
                'template' => 'StsblFileDistributionBundle:List:field_soundlock.html.twig',
                'responsive' => 'min-tablet',
            ]);
        }
        
        // calculate sort order
        if ($activeFileDistributions && $activeSoundLocks) {
            // case: list with file distributions and sound locks
            $sortOrder = [6, 1];
        } else if ($activeFileDistributions || $activeSoundLocks) {
            if ($activeFileDistributions) {
                // case: only file distributions
                $sortOrder = [5, 1];
            } else {
                // only sound locks
                $sortOrder = [4, 1];
            }
        } else {
            // case: nothing of them
            $sortOrder = [3, 1];
        }
        $listMapper
            ->add('room', null, [
                'label' => _('Room'),
                'group' => true,
                // sort in the following order: room, name, fileDistribution \o/
                'sortOrder' => $sortOrder,
                'sortType' => 'natural',
            ])
        ;
        
        if ($this->isLockAvailable() && count($this->getObjectManager()->getRepository('StsblFileDistributionBundle:Lock')->findAll()) > 0) {
            $listMapper
                ->add('lock', null, [
                    'label' => '', // no title in table
                    'template' => 'StsblFileDistributionBundle:List:field_lock.html.twig',
                    'responsive' => 'min-tablet',
                    'sortType' => 'natural',
            ]);
        }
        
        $listMapper
            ->add('sambaUserDisplay', null, [
                'label' => _('User'),
                'template' => 'StsblFileDistributionBundle:List:field_sambauser.html.twig',
            ])
            ->add('nameForInternet', null, [
                'label' => _('Internet Access'),
                'template' => 'StsblFileDistributionBundle:List:field_internet.html.twig',
                'sortType' => 'natural',
            ]);
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadBatchActions()
    {
        parent::loadBatchActions();
        
        $enableAction = new Batch\EnableAction($this);
        $this->batchActions->add($enableAction);
        
        $stopAction = new Batch\StopAction($this);
        $this->batchActions->add($stopAction);

        $soundUnlockAction = new Batch\SoundUnlockAction($this);
        $this->batchActions->add($soundUnlockAction);
        
        $soundLockAction = new Batch\SoundLockAction($this);
        $this->batchActions->add($soundLockAction);
        
        return $this->batchActions;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getRoutePattern($action, $id, $entityBased = true) 
    {
        if ('index' === $action) {
            return sprintf('%s', $this->id);
        } else {
            return parent::getRoutePattern($action, $id, $entityBased);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthorized() 
    {
        return $this->isGranted(Privilege::USE_FD) && $this->isGranted(HostPrivilege::BOOT);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureListFilter(ListHandler $listHandler) 
    {
        $listHandler
            ->addListFilter((new Filter\ListFilterByFilter(_('Internet: yes'), array('internet' => true)))->setName('has-internet')->setGroup(_('Internet')))
            ->addListFilter((new Filter\ListFilterByFilter(_('Internet: no'), array('internet' => false)))->setName('has-no-internet')->setGroup(_('Internet')))
            ->addListFilter((new Filter\ListFilterByFilter(_('Internet: forbidden'), array('overrideRoute' => false)))->setName('internet-is-forbidden')->setGroup(_('Internet')))
            ->addListFilter((new Filter\ListFilterByFilter(_('Internet: granted'), array('overrideRoute' => true)))->setName('internet-is-granted')->setGroup(_('Internet')))
        ;
        
        if ($this->isExamModeAvailable()) {
                
            $examFilter = new Filter\ListExpressionFilter(_('Internet: exam mode'), 'EXISTS (SELECT e FROM StsblFileDistributionBundle:Exam e WHERE e.ip = parent.ip)');
            
            $examFilter
                ->setName('internet-exam')
                ->setGroup(_('Internet'))
            ;
            
            $listHandler->addListFilter($examFilter);
        }
        
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');
        
        $fileDistributionFilterHash = [];
        foreach ($er->findAll() as $f) {
            if (isset($fileDistributionFilterHash[$f->getPlainTitle()])) {
                // skip if we have already a filter for the distribution
                continue;
            }
            
            $fileDistributionFilterHash[$f->getPlainTitle()] = true;
            
            $fdFilter = new Filter\ListExpressionFilter(__('File distribution: %s', $f->getPlainTitle()), 'EXISTS (SELECT e FROM StsblFileDistributionBundle:FileDistribution e WHERE e.ip = parent.ip AND e.title = :title)');
            
            $fdFilter
                ->setParameters(['title' => $f->getPlainTitle()])
                ->setName(sprintf('file-distribution-%s', $f->getId()))
                ->setGroup(_('File distribution'))
            ;
            
            $listHandler->addListFilter($fdFilter);
        }
        
        $withoutFilter = new Filter\ListExpressionFilter(_('[Without file distribution]'), 'NOT EXISTS (SELECT e FROM StsblFileDistributionBundle:FileDistribution e WHERE e.ip = parent.ip)');
            
        $withoutFilter
            ->setName(sprintf('file-distribution-wihthout'))
            ->setGroup(_('File distribution'))
        ;
        
        $listHandler->addListFilter($withoutFilter);
        
        $listHandler
            ->addListFilter((new Filter\ListPropertyFilter(_('Room'), 'room', 'IServRoomBundle:Room', 'name', 'name'))->setName('room')->allowAnyAndNone()->setPickerOptions(array('data-live-search' => 'true')))
            ->addListFilter((new Filter\ListSearchFilter(_('Search'), [
                'name' => FilterSearch::TYPE_TEXT
            ])))
        ;
    }
    
    /**
     * Get host type by id
     *
     * @param string $type
     * @return HostType
     */
    public function getHostType($type)
    {
        return HostConfig::getHostType($type);
    }
    
    /**
     * Check if exam mode is installed on the IServ.
     * 
     * @todo Switch to hasBundle() or something similar, when exam mode is ported to IServ 3.
     * 
     * @return boolean
     */
    private function isExamModeAvailable()
    {
        return file_exists('/var/lib/dpkg/info/iserv-exam.list');
    }

    /**
     * Check if lock module is installed on the IServ.
     * 
     * @todo Switch to hasBundle() or something similar, when lock/host control is ported to IServ 3.
     * 
     * @return boolean
     */
    private function isLockAvailable()
    {
        return file_exists('/var/lib/dpkg/info/iserv-lock.list');
    }
    
    /**
     * Get current internet state (yes, now, allowed, forbidden) for Host by his name.
     * 
     * @param string $name
     * @return string
     */
    public function getInternetStateByName($name) {
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Host');
        /* @var $host \Stsbl\FileDistributionBundle\Entity\Host */
        $host = $er->find($name);
        
        if ($host === null) {
            return 'none';
        }
        
        $overrideRoute = $host->getOverrideRoute();
        $internet = $host->getInternet();
        
        if ($this->isExamModeAvailable()) {
            $examRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam');
            
            $examMode = $examRepository->find($host->getIp());
            
            if (!is_null($examMode)) {
                return 'exam';
            }
        }
        
        if ($overrideRoute === false) {
            return 'forbidden';
        } elseif ($overrideRoute === true) {
            return 'granted';
        } elseif ($internet === true) {
            return 'yes';
        } elseif ($internet === false) {
            return 'no';
        }
    }
    
    /**
     * Get current internet lock explaination for Host by his name.
     * 
     * @param string $name
     * @return array
     */
    public function getInternetExplainationByName($name) 
    {
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Host');
        /* @var $host \Stsbl\FileDistributionBundle\Entity\Host */
        $host = $er->find($name);
        
        if ($host === null) {
            return '';
        }
        
        $overrideRoute = $host->getOverrideRoute();
        $internet = $host->getInternet();
        
        if ($this->isExamModeAvailable()) {
            $examRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam');
            
            /* @var $examMode Stsbl\FileDistributionBundle\Entity\Exam */
            $examMode = $examRepository->find($host->getIp());
            
            if (!is_null($examMode)) {
                return [
                    'title' => $examMode->getTitle(),
                    'user' => $examMode->getUser(),
                    'until' => null
                ];
            }
        }
        
        if ($overrideRoute === false || $overrideRoute === true) {
            return [
                'title' => null,
                'user' => $this->getObjectManager()->getRepository('IServCoreBundle:User')->find($host->getOverrideBy()),
                'until' => $host->getOverrideUntil(),
            ];
        }
        
        return null;
    }
    
    /**
     * Get file distribution info by id
     * 
     * @param integer $id
     * @return \Stsbl\FileDistributionBundle\Entity\FileDistribution
     */
    public function getFileDistributionInfoById($id)
    {
        if (!is_null($id)) {
            return $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution')->find($id);
        } else {
            return null;
        }
    }
    
    /**
     * Get user who locked a computer.
     * 
     * @param string $name
     * @return \IServ\CoreBundle\Entity\User
     */
    public function getLockUser($ip)
    {  
        /* @var $lock \Stsbl\FileDistributionBundle\Entity\Lock */
        $lock = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Lock')->find($ip);
        
        if (is_null($lock)) {
            return false;
        }
        
        return $lock->getUser();
    }
    
    /**
     * Checks wether the user is coming from the host with given name or not.
     * 
     * @param string $name
     * @return boolean
     */
    public function isCurrentHost($name)
    {
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Host');
        /* @var $host \Stsbl\FileDistributionBundle\Entity\Host */
        $host = $er->find($name);
        
        if ($host === null) {
            return false;
        }
        
        return $host->getIp() === $this->getRequest()->getClientIp();
    }
}
