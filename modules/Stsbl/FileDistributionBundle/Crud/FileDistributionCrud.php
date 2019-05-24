<?php
// src/Stsbl/FileDistributionBundle/Crud/FileDistributionCrud.php
namespace Stsbl\FileDistributionBundle\Crud;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use IServ\ComputerBundle\Crud\ListFilterEventSubscriber;
use IServ\ComputerBundle\Service\Internet;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\BundleDetector;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Util\Format;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Table\Filter;
use IServ\CrudBundle\Table\ListHandler;
use IServ\CrudBundle\Table\Specification\FilterSearch;
use IServ\HostBundle\Model\HostType;
use IServ\HostBundle\Util\Config as HostConfig;
use IServ\HostBundle\Util\Network;
use IServ\LockBundle\Service\LockManager;
use Psr\Container\ContainerInterface;
use Stsbl\FileDistributionBundle\Controller\FileDistributionController;
use Stsbl\FileDistributionBundle\Crud\Batch;
use Stsbl\FileDistributionBundle\DependencyInjection\HostExtensionAwareInterface;
use Stsbl\FileDistributionBundle\DependencyInjection\ManagerAwareInterface;
use Stsbl\FileDistributionBundle\Entity\Exam;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Repository\FileDistributionRepository;
use Stsbl\FileDistributionBundle\Entity\Host;
use Stsbl\FileDistributionBundle\Entity\Specification\FileDistributionSpecification;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Stsbl\FileDistributionBundle\Service\FileDistributionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

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
class FileDistributionCrud extends AbstractCrud implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

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

    public function __construct()
    {
        parent::__construct(Host::class);
    }

    /* GETTERS */

    public function getEntityManager(): EntityManagerInterface
    {
        if (null === $this->em) {
            /** @noinspection MissingService */
            $this->em = $this->container->get(EntityManagerInterface::class);
        }

        return $this->em;
    }

    public function getSession(): SessionInterface
    {
        if (null === $this->session) {
            $this->session = $this->container->get(SessionInterface::class);
        }

        return $this->session;
    }

    public function getConfig(): Config
    {
        if (null === $this->config) {
            $this->config = $this->container->get(Config::class);
        }

        return $this->config;
    }

    public function getRequest(): ?Request
    {
        if (null === $this->request) {
            $this->request = $this->container->get(RequestStack::class)->getCurrentRequest();
        }

        return $this->request;
    }

    public function getFileDistributionManager(): FileDistributionManager
    {
        if (null === $this->manager) {
            $this->manager = $this->container->get(FileDistributionManager::class);
        }

        return $this->manager;
    }

    public function getLockManager(): ?LockManager
    {
        if (null === $this->lockManager && $this->container->has(LockManager::class)) {
            $this->lockManager = $this->container->get(LockManager::class);
        }

        return $this->lockManager;
    }

    public function getInternet(): ?Internet
    {
        if (null === $this->internet && $this->container->has(Internet::class)) {
            $this->internet = $this->container->get(Internet::class);
        }

        return $this->internet;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @required
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
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
        
        if ($user !== $fileDistribution->getUser()) {
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
        
        if ($fileDistribution === null) {
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
<<<<<<< HEAD
     * Checks if current request comes from LAN
     *
     * @return bool
=======
     * Checks if current request comes from LAN.
>>>>>>> master
     */
    public function isInLan(): bool
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
        } elseif (($activeFileDistributions || $activeExams) && $activeSoundLocks) {
            // case: list with (file distributions or exams) and sound locks
            $sortOrder = [6, 1];
        } elseif ($activeExams && $activeFileDistributions) {
            // case: list with file distributions and exams
            $sortOrder = [6, 1];
        } elseif ($activeFileDistributions || $activeSoundLocks || $activeExams) {
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
        $hasToken = $this->getContainer()->get(TokenStorageInterface::class)->getToken() !== null;
        
        // Lock
        if ((!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::LOCK)) && $this->isLockAvailable()) {
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
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::BOOT)) {
            $this->batchActions->add(new Batch\MessageAction($this));
        }
        // Sound
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::BOOT) &&
            $this->getAuthorizationChecker()->isGranted(Privilege::USE_FD)) {
            $this->batchActions->add(new Batch\SoundUnlockAction($this));
            $this->batchActions->add(new Batch\SoundLockAction($this));
        }
        // Start & Shutdown
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::BOOT)) {
            $this->batchActions->add(new Batch\PowerOnAction($this));
            $this->batchActions->add(new Batch\LogOffAction($this));
            $this->batchActions->add(new Batch\RebootAction($this));
            $this->batchActions->add(new Batch\ShutdownAction($this));
            $this->batchActions->add(new Batch\ShutdownCancelAction($this));
        }
        // File Distribution
        if (!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::BOOT) && $this->getAuthorizationChecker()->isGranted(Privilege::USE_FD)) {
            $this->batchActions->add(new Batch\EnableAction($this, $this->getRequest()));
            $this->batchActions->add(new Batch\StopAction($this));
        }
        
        // Exam Mode
        if ((!$hasToken || $this->getAuthorizationChecker()->isGranted(Privilege::EXAM)) && $this->isExamModeAvailable()) {
            $this->batchActions->add(new Batch\ExamAction($this, $this->getRequest()));
            $this->batchActions->add(new Batch\ExamOffAction($this));
        }

        foreach ($this->batchActions as $batchAction) {
            if ($batchAction instanceof ManagerAwareInterface) {
                $batchAction->setFileDistributionManager($this->getFileDistributionManager());
            }

            if ($batchAction instanceof HostExtensionAwareInterface) {
                $batchAction->setHostExtension($this->container->get(HostExtension::class));
            }
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
        return $this->isGranted(Privilege::USE_FD) && $this->isGranted(Privilege::BOOT);
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
        
        $deniedExpr = $qb->expr()->eq('parent.overrideRoute', 'false');

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
                ->select('e3')
                ->from('StsblFileDistributionBundle:Exam', 'e3')
                ->where($qb->expr()->eq('e3.ip', 'parent.ip'))
            ;
            
            $examFilter = new Filter\ListExpressionFilter(_('Internet: exam mode'), $qb->expr()->exists($qb));
            
            $examFilter
                ->setName('internet-exam')
                ->setGroup(_('Internet'))
            ;
            
            $listHandler->addListFilter($examFilter);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var \Stsbl\FileDistributionBundle\Repository\FileDistributionRepository $er */
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
        $listHandler->getFilterHandler()->addEventSubscriber(
            new ListFilterEventSubscriber($this->getRequest(), $om->getRepository(\IServ\HostBundle\Entity\Host::class))
        );
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
     */
    public function hasBundle(string $name): bool
    {
        return $this->container->get(BundleDetector::class)->isLoaded($name);
    }
    
    /**
     * Check if exam mode is installed on the IServ.
<<<<<<< HEAD
     *
     * @return boolean
=======
>>>>>>> master
     */
    public function isExamModeAvailable(): bool
    {
        return $this->hasBundle('IServExamBundle');
    }

    /**
     * Check if lock module is installed on the IServ.
<<<<<<< HEAD
     *
     * @return boolean
=======
>>>>>>> master
     */
    public function isLockAvailable(): bool
    {
        return $this->hasBundle('IServLockBundle');
    }
    
    /**
     * Check if Internet GUI is installed on the IServ.
<<<<<<< HEAD
     *
     * @return boolean
=======
>>>>>>> master
     */
    public function isInternetAvailable(): bool
    {
        return $this->hasBundle('StsblInternetBundle');
    }
    
    /**
     * Checks if internet is granted to an ip via a NAC.
<<<<<<< HEAD
     *
     * @param Host $host
     * @return boolean
=======
>>>>>>> master
     */
    private function isInternetGrantedViaNac(Host $host): bool
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
     */
    public function getInternetState(Host $host): ?string
    {
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

        $qb = $this->getEntityManager()->createQueryBuilder();
        $subQb = clone $qb;
        $subSubQb = clone $qb;

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
        try {
            $currentUser = $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new \LogicException('Cannot happen!', 0, $e);
        }

        $internetAlwaysDenied = false;
        $internetAlwaysGranted = false;
        
        if (null !== $currentUser) {
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
        } elseif ($internetAlwaysDenied === true) {
            return 'no_priv';
        } elseif ($overrideRoute === true) {
            return 'granted';
        } elseif ($this->isInternetAvailable() && $this->isInternetGrantedViaNac($host) === true) {
            return 'yes_nac';
        } elseif ($internetAlwaysGranted === true) {
            return 'yes_priv';
        } elseif ($internet === true) {
            return 'yes';
        } elseif ($internet === false) {
            return 'no';
        }

        return 'yes';

    }
    
    /**
     * Get current internet lock explanation for Host by his ip address.
     *
     * @return mixed[]|null
     */
    public function getInternetExplanation(Host $host): ?array
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
                'until' => Format::smartDate($until),
            ];
        }
        
        return null;
    }

    public function getFileDistribution(Host $host): ?FileDistribution
    {
        if ($host != null) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution')->findOneByIp($host->getIp());
        }

        return null;
    }

    public function getExam(Host $host): ?Exam
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
     */
    public function getSoundLockUser(Host $host): ?User
    {
        /* @var $lock \Stsbl\FileDistributionBundle\Entity\SoundLock */
        /** @noinspection PhpUndefinedMethodInspection */
        $lock = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:SoundLock')->findOneByIp($host->getIp());
        
        if ($lock === null) {
            return null;
        }
        
        return $lock->getUser();
    }

    public function accountToUser(?string $act = null): ?User
    {
        if ($act === null) {
            return null;
        }

        return $this->getEntityManager()->getRepository('IServCoreBundle:User')->find($act);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            BundleDetector::class,
            Config::class,
            EntityManagerInterface::class,
            FileDistributionManager::class,
            HostExtension::class => HostExtension::class,
            '?' . Internet::class,
            '?' . LockManager::class,
            RequestStack::class,
            SessionInterface::class,
            TokenStorageInterface::class,
        ];
    }
}
