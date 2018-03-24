<?php
// src/Stsbl/FileDistributionBundle/Crud/FileDistributionCrud.php
namespace Stsbl\FileDistributionBundle\Crud;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Util\Format;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository;
use IServ\CrudBundle\Table\Filter;
use IServ\CrudBundle\Table\ListHandler;
use IServ\CrudBundle\Table\Specification\FilterSearch;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\HostBundle\Crud\ListFilterEventSubscriber;
use IServ\HostBundle\Model\HostType;
use IServ\HostBundle\Util\Config as HostConfig;
use IServ\HostBundle\Util\Network;
use IServ\HostBundle\Security\Privilege as HostPrivilege;
use Stsbl\FileDistributionBundle\Controller\FileDistributionController;
use Stsbl\FileDistributionBundle\Crud\Batch;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Entity\Host;
use Stsbl\FileDistributionBundle\Entity\Specification\FileDistributionSpecification;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Stsbl\FileDistributionBundle\Service\FileDistributionManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;

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
 * FileDistribution Crud
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionCrud extends AbstractCrud implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var EntityManager
     */
    private $em;
    
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
     * @var FileDistributionManager
     */
    private $manager;

    /**
     * @var \IServ\LockBundle\Service\LockManager
     */
    private $lockManager;

    /**
     * @var \IServ\ComputerBundle\Service\Internet
     */
    private $internet;
    
    /**
     * @var bool
     */
    private static $roomMode;
    
    /* GETTERS */
    
    /**
     * Get entity manager
     * 
     * @return EntityManager|null
     */
    public function getEntityManager()
    {
        if (null === $this->em) {
            /** @noinspection MissingService */
            $this->em = $this->container->get('doctrine.orm.entity_manager');
        }

        return $this->em;
    }
    
    /**
     * Get session
     * 
     * @return Session|null
     */
    public function getSession()
    {
        if (null === $this->session) {
            $this->session = $this->container->get('session');
        }

        return $this->session;
    }
    
    /**
     * Get config
     * 
     * @return Config|config
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = $this->container->get('iserv.config');
        }

        return $this->config;
    }
    
    /**
     * Get request
     * 
     * @return Request|null
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->request = $this->container->get('request_stack')->getCurrentRequest();
        }

        return $this->request;
    }
    
    /**
     * Get manager
     * 
     * @return FileDistributionManager|null
     */
    public function getFileDistributionManager()
    {
        if (null === $this->manager) {
            $this->manager = $this->container->get('stsbl.filedistribution.manager');
        }

        return $this->manager;
    }

    /**
     * Get lock manager
     *
     * @return \IServ\LockBundle\Service\LockManager|null
     */
    public function getLockManager()
    {
        if (null === $this->lockManager && $this->container->has('iserv.lock.manager')) {
            $this->lockManager = $this->container->get('iserv.lock.manager');
        }

        return $this->lockManager;
    }

    /**
     * Get the internet ;)
     *
     * @return \IServ\ComputerBundle\Service\Internet|null
     */
    public function getInternet()
    {
        if (null === $this->internet && $this->container->has('iserv.host.internet')) {
            $this->internet = $this->container->get('iserv.host.internet');
        }

        return $this->internet;
    }

    /**
     * Get container
     * 
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
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
        $this->options['json'] = true;
        $this->options['autoload'] = false;
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
     * @return bool
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
     * @return bool
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
     * Get current room filter mode
     * 
     * @return bool
     */
    public static function getRoomMode()
    {
        if (!is_bool(self::$roomMode)) {
            $content = file_get_contents(FileDistributionController::ROOM_CONFIG_FILE);
            self::$roomMode = json_decode($content, true)['invert'];
        }
        
        return self::$roomMode;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilterSpecification()
    {
        // Only show controllable hosts and hosts which are in available rooms
        return new FileDistributionSpecification($this->getRoomMode(), $this->getEntityManager());
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
        /** @noinspection PhpUndefinedMethodInspection */
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
        //if ($this->em->getRepository('StsblFileDistributionBundle:FileDistribution')->exists()) {
        //    $this->options['sort'] = 'fileDistribution';
        //} else {
        $this->options['sort'] = 'room';
        //}

        $activeFileDistributions = false;
        $activeSoundLocks = false;
        $activeExams = false;

        $listMapper
            ->add('name', null, [
                'label' => _('Name'),
                'responsive' => 'all',
                'sortType' => 'natural',
                'template' => 'IServHostBundle:Crud:list_field_name.html.twig',
            ])
            ->add('sambaUser', null, [
                'label' => _('User'),
                'template' => 'StsblFileDistributionBundle:List:field_sambauser.html.twig',
            ])
            ->add('type', null, [
                'label' => _('Type'),
                'template' => 'IServHostBundle:Crud:list_field_type.html.twig',
                'responsive' => 'desktop'
            ]);

        // check for existing file distribution
        /** @noinspection PhpUndefinedMethodInspection */
        $fileDistributionRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');

        // only add columns if we have file distributions
        if ($fileDistributionRepository->exists()) {
            $activeFileDistributions = true;

            $listMapper
                ->add('fileDistribution', null, [
                    'label' => _('File distribution'),
                    'group' => true,
                    // sort in the following order: fileDistribution, name, room \o/
                    'sortOrder' => [4, 1],
                    'sortType' => 'natural',
                    'template' => 'StsblFileDistributionBundle:List:field_filedistribution.html.twig',
                ]);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        // TODO Switch to ExamBundle
        if ($this->isExamModeAvailable() && $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam')->exists()) {
            $activeExams = true;

            if ($activeFileDistributions) {
                $sortOrder = [5, 1];
            } else {
                $sortOrder = [4, 1];
            }

            $listMapper
                ->add('exam', null, [
                    'label' => _('Exam'),
                    'group' => true,
                    // sort in the following order: exam, name, room \o/
                    'sortOrder' => $sortOrder,
                    'sortType' => 'natural',
                    'template' => 'StsblFileDistributionBundle:List:field_exam.html.twig'
                ]);
        }

        // check for existing sound locks
        /** @noinspection PhpUndefinedMethodInspection *
        /** @var \IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository $soundLockRepository */
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
        if ($activeFileDistributions && $activeExams && $activeSoundLocks) {
            $sortOrder = [7, 1];
        } else if (($activeFileDistributions || $activeExams) && $activeSoundLocks) {
            // case: list with (file distributions or exams) and sound locks
            $sortOrder = [6, 1];
        } else if ($activeExams && $activeFileDistributions) {
            // case: list with file distributions and exams
            $sortOrder = [6, 1];
        } else if ($activeFileDistributions || $activeSoundLocks || $activeExams) {
            // case: only exams or file distributions or sound locks
            $sortOrder = [5, 1];
        } else {
            // case: nothing of them
            $sortOrder = [4, 1];
        }

        $listMapper
            ->add('room', null, [
                'label' => _('Room'),
                'group' => true,
                // sort in the following order: room, name, fileDistribution \o/
                'sortOrder' => $sortOrder,
                'sortType' => 'natural',
            ])
            ->add('ipInternet', null, [
                'label' => _('Internet Access'),
                'template' => 'StsblFileDistributionBundle:List:field_internet.html.twig',
                'sortType' => 'natural',
            ]);
        ;

        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->isLockAvailable() && count($this->getObjectManager()->getRepository('IServLockBundle:Lock')->findAll()) > 0) {
            $listMapper
                ->add('locker', null, [
                    'label' => _('Locked by'),
                    'template' => 'StsblFileDistributionBundle:List:field_locker.html.twig'
                ]);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadBatchActions()
    {
        parent::loadBatchActions();
        
        /*
         * This section can also be called if we have no token set.
         * In this case the isGranted calls will not work and throw
         * an exception.
         */
        $hasToken = $this->getContainer()->get('security.token_storage')->getToken() !== null;
        
        // Lock
        if ((!$hasToken || $this->getAuthorizationChecker()->isGranted(HostPrivilege::LOCK)) && $this->isLockAvailable()) {
            $this->batchActions->add(new Batch\LockAction($this));
            $this->batchActions->add(new Batch\UnlockAction($this));
        }
        // Internet
        if ((!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::INET_ROOMS)) && $this->getConfig()->get('Activation')) {
            $this->batchActions->add(new Batch\GrantInternetAction($this));
            $this->batchActions->add(new Batch\DenyInternetAction($this));
            $this->batchActions->add(new Batch\ResetInternetAction($this));
        }
        // Communication
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(HostPrivilege::BOOT)) {
            $this->batchActions->add(new Batch\MessageAction($this));
        }
        // Sound
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(HostPrivilege::BOOT) && $this->getContainer()->get('security.authorization_checker')->isGranted(Privilege::USE_FD)) {
            $this->batchActions->add(new Batch\SoundUnlockAction($this));
            $this->batchActions->add(new Batch\SoundLockAction($this));
        }
        // Start & Shutdown
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(HostPrivilege::BOOT)) {
            $this->batchActions->add(new Batch\PowerOnAction($this));
            $this->batchActions->add(new Batch\LogOffAction($this));
            $this->batchActions->add(new Batch\RebootAction($this));
            $this->batchActions->add(new Batch\ShutdownAction($this));
            $this->batchActions->add(new Batch\ShutdownCancelAction($this));
        }
        // File Distribution
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(HostPrivilege::BOOT) && $this->getAuthorizationChecker()->isGranted(Privilege::USE_FD)) {
            $this->batchActions->add(new Batch\EnableAction($this));
            $this->batchActions->add(new Batch\StopAction($this));
        }
        
        // Exam Mode
        if ((!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::EXAM)) && $this->isExamModeAvailable()) {
            $this->batchActions->add(new Batch\ExamAction($this));
            $this->batchActions->add(new Batch\ExamOffAction($this));
        }
        
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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $yesExpr = $qb->expr()->eq('parent.internet', 'true');
        
        // add condition for NAC activation
        if ($this->isInternetAvailable()) {
            $qb
                ->select('n')
                ->from('StsblInternetBundle:Nac', 'n')
                ->where('n.ip = parent.ip')
            ;
            
            $yesExpr = $qb->expr()->orX($yesExpr, $qb->expr()->exists($qb));
        }
        
        $yesExpr = $qb->expr()->andX($yesExpr, $qb->expr()->isNull('parent.overrideRoute'));
        
        if ($this->isExamModeAvailable()) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('ex')
                ->from('StsblFileDistributionBundle:Exam', 'ex')
                ->where($qb->expr()->eq('ex.ip', 'parent.ip'))
            ;
            
            $yesExpr = $qb->expr()->andX($yesExpr, $qb->expr()->not($qb->expr()->exists($qb)));
        }
        
        $internetYesFilter = new Filter\ListExpressionFilter(_('Internet: yes'), $yesExpr);
        $internetYesFilter
                ->setName('has-internet')
                ->setGroup(_('Internet'));
            
        $listHandler->addListFilter($internetYesFilter);
        
        $noExpr = $qb->expr()->andX($qb->expr()->eq('parent.internet', 'false'), $qb->expr()->isNull('parent.overrideRoute'));
        
        if ($this->isExamModeAvailable()) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('ex2')
                ->from('StsblFileDistributionBundle:Exam', 'ex2')
                ->where($qb->expr()->eq('ex2.ip', 'parent.ip'))
            ;
            
            $noExpr = $qb->expr()->andX($noExpr, $qb->expr()->not($qb->expr()->exists($qb)));
        }
        
        $internetNoFilter = new Filter\ListExpressionFilter(_('Internet: no'), $noExpr);
        $internetNoFilter
                ->setName('has-no-internet')
                ->setGroup(_('Internet'));
            
        $listHandler->addListFilter($internetNoFilter);
        
        $grantedExpr = $qb->expr()->eq('parent.overrideRoute', 'true');

        if ($this->isExamModeAvailable()) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('ex3')
                ->from('StsblFileDistributionBundle:Exam', 'ex3')
                ->where($qb->expr()->eq('ex3.ip', 'parent.ip'))
            ;
            
            $grantedExpr = $qb->expr()->andX($grantedExpr, $qb->expr()->not($qb->expr()->exists($qb)));
        }
        
        $internetGrantedFilter = new Filter\ListExpressionFilter(_('Internet: granted'), $grantedExpr);
        $internetGrantedFilter
                ->setName('internet-is-granted')
                ->setGroup(_('Internet'));
            
        $listHandler->addListFilter($internetGrantedFilter);
        
        $deniedExpr = $qb->expr()->eq('parent.overrideRoute','false');

        if ($this->isExamModeAvailable()) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('ex4')
                ->from('StsblFileDistributionBundle:Exam', 'ex4')
                ->where($qb->expr()->eq('ex4.ip', 'parent.ip'))
            ;
            
            $deniedExpr = $qb->expr()->andX($deniedExpr, $qb->expr()->not($qb->expr()->exists($qb)));
        }
        
        $internetDeniedFilter = new Filter\ListExpressionFilter(_('Internet: forbidden'), $deniedExpr);
        $internetDeniedFilter
                ->setName('internet-is-forbidden')
                ->setGroup(_('Internet'));
            
        $listHandler->addListFilter($internetDeniedFilter);
        
        if ($this->isExamModeAvailable()) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('e')
                ->from('StsblFileDistributionBundle:Exam', 'e')
                ->where($qb->expr()->eq('e.ip', 'parent.ip'))
            ;
            
            $examFilter = new Filter\ListExpressionFilter(_('Internet: exam mode'), $qb->expr()->exists($qb));
            
            $examFilter
                ->setName('internet-exam')
                ->setGroup(_('Internet'))
            ;
            
            $listHandler->addListFilter($examFilter);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');
        
        $fileDistributionFilterHash = [];
        foreach ($er->findAll() as $f) {
            /** @var $f FileDistribution */
            if (isset($fileDistributionFilterHash[$f->getPlainTitle()])) {
                // skip if we have already a filter for the distribution
                continue;
            }
            
            $fileDistributionFilterHash[$f->getPlainTitle()] = true;
            
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('f2')
                ->from('StsblFileDistributionBundle:FileDistribution', 'f2')
                ->where($qb->expr()->eq('f2.ip', 'parent.ip'))
                ->andWhere($qb->expr()->eq('f2.title', ':title'))
            ;
            
            $fdFilter = new Filter\ListExpressionFilter(__('File distribution: %s', $f->getPlainTitle()), $qb->expr()->exists($qb));
            
            $fdFilter
                ->setParameters(['title' => $f->getPlainTitle()])
                ->setName(sprintf('file-distribution-%s', $f->getId()))
                ->setGroup(_('File distribution'))
            ;
            
            $listHandler->addListFilter($fdFilter);
        }
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('f3')
            ->from('StsblFileDistributionBundle:FileDistribution', 'f3')
            ->where($qb->expr()->eq('f3.ip', 'parent.ip'))
        ;
        
        $withFilter = new Filter\ListExpressionFilter(_('[With file distribution]'), $qb->expr()->exists($qb));    
        $withFilter
            ->setName(sprintf('file-distribution-with'))
            ->setGroup(_('File distribution'))
        ;
        $listHandler->addListFilter($withFilter);
        
        $withoutFilter = new Filter\ListExpressionFilter(_('[Without file distribution]'), $qb->expr()->not($qb->expr()->exists($qb)));   
        $withoutFilter
            ->setName(sprintf('file-distribution-without'))
            ->setGroup(_('File distribution'))
        ;
        $listHandler->addListFilter($withoutFilter);

        if ($this->isExamModeAvailable()) {
            $examRepository = $this->getEntityManager()->getRepository('StsblFileDistributionBundle:Exam');
            $examFilterHash = [];
            foreach ($examRepository->findAll() as $f) {
                if (isset($examFilterHash[$f->getTitle()])) {
                    // skip if we have already a filter for the exam
                    continue;
                }

                $examFilterHash[$f->getTitle()] = true;

                $qb = $this->getEntityManager()->createQueryBuilder();
                $qb
                    ->select('ex5')
                    ->from('StsblFileDistributionBundle:Exam', 'ex5')
                    ->where($qb->expr()->eq('ex5.ip', 'parent.ip'))
                    ->andWhere($qb->expr()->eq('ex5.title', ':title'))
                ;

                $fdFilter = new Filter\ListExpressionFilter(__('Exam: %s', $f->getTitle()), $qb->expr()->exists($qb));

                $fdFilter
                    ->setParameters(['title' => $f->getTitle()])
                    ->setName(sprintf('exam-%s', $f->getId()))
                    ->setGroup(_('Exam'))
                ;

                $listHandler->addListFilter($fdFilter);
            }

            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('ex6')
                ->from('StsblFileDistributionBundle:Exam', 'ex6')
                ->where($qb->expr()->eq('ex6.ip', 'parent.ip'))
            ;

            $withFilter = new Filter\ListExpressionFilter(_('[With exam]'), $qb->expr()->exists($qb));
            $withFilter
                ->setName(sprintf('exam-with'))
                ->setGroup(_('Exam'))
            ;
            $listHandler->addListFilter($withFilter);

            $withoutFilter = new Filter\ListExpressionFilter(_('[Without exam]'), $qb->expr()->not($qb->expr()->exists($qb)));
            $withoutFilter
                ->setName(sprintf('exam-without'))
                ->setGroup(_('Exam'))
            ;
            $listHandler->addListFilter($withoutFilter);
        }
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('fr')
            ->from('StsblFileDistributionBundle:FileDistributionRoom', 'fr')
            ->where($qb->expr()->eq('fr.room', 'filter.id'))
        ;
        $roomCondition = $qb->expr()->exists($qb);
        if ($this->getRoomMode() === true) {
            $roomCondition = $qb->expr()->not($roomCondition);
        }
        
        $qb2 = $this->getEntityManager()->createQueryBuilder();
        $qb2
            ->select('h')
            ->from('StsblFileDistributionBundle:Host', 'h')
            ->where('h.room = filter.id')
            ->andWhere('h.controllable = true')
        ;

        $hostCondition = $qb->expr()->exists($qb2);
        
        $listHandler
            ->addListFilter((new Filter\ListPropertyFilter(_('Room'), 'room', 'IServRoomBundle:Room', 'name', 'id'))->setName('room')
                    ->allowAnyAndNone()
                    ->setPickerOptions(array('data-live-search' => 'true'))
                    ->setWhereCondition($qb->expr()->andX($roomCondition, $hostCondition))
            )
            ->addListFilter((new Filter\ListSearchFilter(_('Search'), [
                'name' => FilterSearch::TYPE_TEXT
            ])))
        ;

        /* @var $om \IServ\CrudBundle\Doctrine\ORM\ORMObjectManager */
        $om = $this->getObjectManager();
        $listHandler->getFilterHandler()->addEventSubscriber(new ListFilterEventSubscriber($this->request, $om->getRepository('StsblFileDistributionBundle:Host')));
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
     * Checks if a bundle is installed
     *
     * @param string $name A bundle name like 'IServFooBundle'
     * @return bool
     */
    public function hasBundle($name)
    {
        return array_key_exists($name, $this->getContainer()->getParameter('kernel.bundles'));
    }
    
    /**
     * Check if exam mode is installed on the IServ.
     * 
     * @return boolean
     */
    public function isExamModeAvailable()
    {
        return $this->hasBundle('IServExamBundle');
    }

    /**
     * Check if lock module is installed on the IServ.
     * 
     * @return boolean
     */
    public function isLockAvailable()
    {
        return $this->hasBundle('IServLockBundle');
    }
    
    /**
     * Check if Internet GUI is installed on the IServ.
     * 
     * @return boolean
     */
    public function isInternetAvailable()
    {
        return $this->hasBundle('StsblInternetBundle');
    }
    
    /**
     * Checks if internet is granted to an ip via a NAC.
     * 
     * @param Host $host
     * @return boolean
     */
    private function isInternetGrantedViaNac(Host $host)
    {
        $er = $this->getEntityManager()->getRepository('StsblInternetBundle:Nac');
        $nac = $er->findOneBy(['ip' => $host->getIp()]);
        
        if ($nac === null) {
            return false;
        }
        
        return true;
    }

    /**
     * Get current internet state (yes, no, allowed, forbidden) for Host by his ip address.
     *
     * @param Host $host
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getInternetState(Host $host) {
        if ($host === null) {
            return 'none';
        }
        
        $overrideRoute = $host->getOverrideRoute();
        $internet = $host->getInternet();
        
        if ($this->isExamModeAvailable()) {
            /** @noinspection PhpUndefinedMethodInspection */
            /** @var EntitySpecificationRepository $examRepository */
            $examRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam');
            
            $examMode = $examRepository->find($host->getIp());
            
            if ($examMode !== null) {
                return 'exam';
            }
        }

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $qb = $this->getEntityManager()->createQueryBuilder($this->class);
        $subQb = clone $qb;
        $subSubQb = clone $qb;

        //$userCondition = '(SELECT MAX(s.act) FROM IServHostBundle:SambaUser s WHERE s.ip = :ip AND s.since=(SELECT MAX(v.since) FROM IServHostBundle:SambaUser v WHERE v.ip = :ip))';
        $subSubQb
            ->select($subSubQb->expr()->max('v.since'))
            ->from('IServHostBundle:SambaUser', 'v')
            ->where($qb->expr()->eq('v.ip', ':ip'))
        ;

        $subQb
            ->select($subQb->expr()->max('s.act'))
            ->from('IServHostBundle:SambaUser', 's')
            ->where($qb->expr()->eq('s.ip', ':ip'))
            ->andWhere($qb->expr()->in('s.since', $subSubQb->getDQL()))
        ;

        $qb
            ->select('u')
            ->from('IServCoreBundle:User', 'u')
            ->where($qb->expr()->in('u.username', $subQb->getDQL()))
            ->setParameter('ip', $host->getIp())
        ;
        
        /* @var $currentUser \IServ\CoreBundle\Entity\User */
        $currentUser = $qb->getQuery()->getOneOrNullResult();
        $internetAlwaysDenied = false;
        $internetAlwaysGranted = false;
        
        if (!is_null($currentUser)) {
            foreach ($currentUser->getPrivileges() as $p) {
                /* @var $p \IServ\CoreBundle\Entity\Privilege */
                if ($p->getId() === 'inet_access') {
                    $internetAlwaysGranted = true;
                }
                
                if ($p->getId() === 'inet_block') {
                    $internetAlwaysDenied = true;
                }
            }
        }
        
        if ($overrideRoute === false) {
            return 'forbidden';
        } else if ($internetAlwaysDenied === true) {
            return 'no_priv';
        } else if ($overrideRoute === true) {
            return 'granted';
        } else if ($this->isInternetAvailable() && $this->isInternetGrantedViaNac($host) === true) {
            return 'yes_nac';
        } else if ($internetAlwaysGranted === true) {
            return 'yes_priv';
        } else if ($internet === true) {
            return 'yes';
        } else if ($internet === false) {
            return 'no';
        }
    }
    
    /**
     * Get current internet lock explanation for Host by his ip address.
     * 
     * @param Host $host
     * @return array
     */
    public function getInternetExplanation(Host $host)
    {
        if ($host === null) {
            return null;
        }
        
        $overrideRoute = $host->getOverrideRoute();
        
        if ($this->isExamModeAvailable()) {
            /** @noinspection PhpUndefinedMethodInspection */
            /** @var EntitySpecificationRepository $examRepository */
            $examRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam');
            
            /* @var $examMode \Stsbl\FileDistributionBundle\Entity\Exam */
            $examMode = $examRepository->find($host->getIp());
            
            if ($examMode != null) {
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
                'user' => $this->getEntityManager()->getRepository('IServCoreBundle:User')->find($host->getOverrideBy()),
                'until' => $host->getOverrideUntil(),
            ];
        }
        
        if ($this->isInternetAvailable() && $this->isInternetGrantedViaNac($host)) {
            /* @var $nac \Stsbl\InternetBundle\Entity\Nac */
            /** @noinspection PhpUndefinedMethodInspection */
            $nac = $this->getEntityManager()->getRepository('StsblInternetBundle:Nac')->findOneByIp($host->getIp());
            $user = $nac->getUser();
            $until = $nac->getTimer();
            
            return [
                'title' => null,
                'user' => $user,
                // HACK use twig filter here
                'until' => Format::smartDate($until),
            ];
        }
        
        return null;
    }
    
    /**
     * Get file distribution
     * 
     * @param Host $host
     * @return \Stsbl\FileDistributionBundle\Entity\FileDistribution
     */
    public function getFileDistribution(Host $host)
    {
        if ($host != null) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution')->findOneByIp($host->getIp());
        } else {
            return null;
        }
    }

    /**
     * Get exam
     *
     * @param Host $host
     * @return \Stsbl\FileDistributionBundle\Entity\Exam
     */
    public function getExam(Host $host)
    {
        if ($host != null) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam')->findOneByIp($host->getIp());
        } else {
            return null;
        }
    }
    
    /**
     * Get user who locked the sound on a computer.
     * 
     * @param Host $host
     * @return \IServ\CoreBundle\Entity\User
     */
    public function getSoundLockUser(Host $host)
    {  
        /* @var $lock \Stsbl\FileDistributionBundle\Entity\SoundLock */
        /** @noinspection PhpUndefinedMethodInspection */
        $lock = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:SoundLock')->findOneByIp($host->getIp());
        
        if ($lock === null) {
            return null;
        }
        
        return $lock->getUser();
    }

    /**
     * Convert account to User entity
     *
     * @param string $act
     * @return \IServ\CoreBundle\Entity\User
     */
    public function accountToUser($act = null)
    {
        if ($act === null) {
            return null;
        }

        return $this->getEntityManager()->getRepository('IServCoreBundle:User')->find($act);
    }
}
