<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use IServ\ComputerBundle\Crud\ListFilterEventSubscriber;
use IServ\ComputerBundle\Service\Internet;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\BundleDetector;
use IServ\CoreBundle\Util\Format;
use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository;
use IServ\CrudBundle\Doctrine\Specification\SpecificationInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Routing\RoutingDefinition;
use IServ\CrudBundle\Table\Filter;
use IServ\CrudBundle\Table\ListHandler;
use IServ\CrudBundle\Table\Specification\FilterSearch;
use IServ\HostBundle\Model\HostType;
use IServ\HostBundle\Util\Config as HostConfig;
use IServ\HostBundle\Util\Network;
use IServ\Library\Config\Config;
use IServ\LockBundle\Service\LockManager;
use Stsbl\FileDistributionBundle\Controller\FileDistributionController;
use Stsbl\FileDistributionBundle\Crud\Batch;
use Stsbl\FileDistributionBundle\Entity\Exam;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Entity\Host;
use Stsbl\FileDistributionBundle\Entity\Specification\FileDistributionSpecification;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Stsbl\FileDistributionBundle\Service\FileDistributionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
final class FileDistributionCrud extends ServiceCrud
{
    /**
     * {@inheritDoc}
     */
    protected static $entityClass = Host::class;

    /**
     * @var bool|null
     */
    private static $roomMode;

    /* GETTERS */

    public function entityManager(): EntityManagerInterface
    {
        return $this->locator->get(EntityManagerInterface::class);
    }

    public function session(): SessionInterface
    {
        return $this->locator->get(SessionInterface::class);
    }

    public function config(): Config
    {
        return $this->locator->get(Config::class);
    }

    public function request(): ?Request
    {
        return $this->locator->get(RequestStack::class)->getCurrentRequest();
    }

    public function fileDistributionManager(): FileDistributionManager
    {
        return $this->locator->get(FileDistributionManager::class);
    }

    public function lockManager(): ?LockManager
    {
        if ($this->locator->has(LockManager::class)) {
            return $this->locator->get(LockManager::class);
        }

        return null;
    }

    public function internet(): ?Internet
    {
        if ($this->locator->has(Internet::class)) {
            return $this->locator->get(Internet::class);
        }

        return null;
    }

    /**
     * Exposes parent method as public.
     *
     * {@inheritDoc}
     */
    public function authorizationChecker(): AuthorizationCheckerInterface
    {
        return parent::authorizationChecker();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->title = _('File distribution');
        $this->itemTitle = _p('file-distribution', 'Device');
        $this->id = 'filedistribution';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution';
        $this->options['json'] = true;
        $this->options['autoload'] = false;
        $this->templates['crud_index'] = 'StsblFileDistributionBundle:Crud:file_distribution_index.html.twig';
        $this->templates['crud_batch_confirm'] = 'StsblFileDistributionBundle:Crud:file_distribution_batch_confirm.html.twig';
    }

    /**
     * {@inheritDoc}
     */
    public static function defineRoutes(): RoutingDefinition
    {
        $definition = parent::defineRoutes()
            ->useControllerForAction(self::ACTION_INDEX, FileDistributionController::class . '::indexAction')
            ->useControllerForAction('batch_confirm', FileDistributionController::class . '::confirmBatchAction')
            ->setNamePrefix('fd_')
        ;

        // FIXME: Remove, after CRUD allows proper access!
        try {
            $reflectionProperty = new \ReflectionProperty($definition, 'baseName');
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Could not reflect!', 0, $e);
        }

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($definition, 'filedistribution');

        // FIXME: Remove, after CRUD allows proper access!
        try {
            $reflectionProperty = new \ReflectionProperty($definition, 'basePath');
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Could not reflect!', 0, $e);
        }

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($definition, 'filedistribution');

        return $definition;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedTo(string $action, UserInterface $user, CrudInterface $object = null): bool
    {
        return false;
    }

    public function isAllowedToStop(CrudInterface $object, UserInterface $user = null): bool
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

    public function isAllowedToEnable(CrudInterface $object, UserInterface $user = null): bool
    {
        $fileDistribution = $this->getFileDistributionForHost($object);

        return$fileDistribution === null;
    }

    /**
     * Get current room filter mode
     */
    public static function getRoomMode(): bool
    {
        if (!is_bool(self::$roomMode)) {
            $content = file_get_contents(FileDistributionController::ROOM_CONFIG_FILE);
            self::$roomMode = json_decode($content, true)['invert'] ?? true;
        }

        return self::$roomMode;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterSpecification(): ?SpecificationInterface
    {
        // Only show controllable hosts and hosts which are in available rooms
        return new FileDistributionSpecification(self::getRoomMode(), $this->entityManager());
    }

    public function getFileDistributionForHost(CrudInterface $object): ?FileDistribution
    {
        /* @var $object Host */
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');
        try {
            return $er->findOneBy(['ip' => $object->getIp()]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if current request comes from LAN.
     */
    public function isInLan(): bool
    {
        return Network::ipInLan(null, $this->config()->get('LAN'), $this->request());
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper): void
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
    protected function loadBatchActions(): void
    {
        parent::loadBatchActions();

        /*
         * This section can also be called if we have no token set.
         * In this case the isGranted calls will not work and throw
         * an exception.
         */
        $hasToken = $this->tokenStorage()->getToken() !== null;

        // Lock
        if ((!$hasToken || $this->authorizationChecker()->isGranted(Privilege::LOCK)) && $this->isLockAvailable()) {
            $this->batchActions->add(new Batch\LockAction($this));
            $this->batchActions->add(new Batch\UnlockAction($this));
        }
        // Internet
        if ((!$hasToken || $this->authorizationChecker()->isGranted(Privilege::INET_ROOMS)) && $this->config()->get('Activation')) {
            $this->batchActions->add(new Batch\GrantInternetAction($this));
            $this->batchActions->add(new Batch\DenyInternetAction($this));
            $this->batchActions->add(new Batch\ResetInternetAction($this));
        }
        // Communication
        if (!$hasToken || $this->authorizationChecker()->isGranted(Privilege::BOOT)) {
            $this->batchActions->add(new Batch\MessageAction($this));
        }
        // Sound
        if (!$hasToken || ($this->authorizationChecker()->isGranted(Privilege::BOOT) &&
                $this->authorizationChecker()->isGranted(Privilege::USE_FD))) {
            $this->batchActions->add(new Batch\SoundUnlockAction($this));
            $this->batchActions->add(new Batch\SoundLockAction($this));
        }
        // Start & Shutdown
        if (!$hasToken || $this->authorizationChecker()->isGranted(Privilege::BOOT)) {
            $this->batchActions->add(new Batch\PowerOnAction($this));
            $this->batchActions->add(new Batch\LogOffAction($this));
            $this->batchActions->add(new Batch\RebootAction($this));
            $this->batchActions->add(new Batch\ShutdownAction($this));
            $this->batchActions->add(new Batch\ShutdownCancelAction($this));
        }
        // File Distribution
        if (!$hasToken || ($this->authorizationChecker()->isGranted(Privilege::BOOT) && $this->authorizationChecker()->isGranted(Privilege::USE_FD))) {
            $this->batchActions->add(new Batch\EnableAction($this));
            $this->batchActions->add(new Batch\StopAction($this));
        }

        // Exam Mode
        if ((!$hasToken || $this->authorizationChecker()->isGranted(Privilege::EXAM)) && $this->isExamModeAvailable()) {
            $this->batchActions->add(new Batch\ExamAction($this));
            $this->batchActions->add(new Batch\ExamOffAction($this));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized(): bool
    {
        return $this->isGranted(Privilege::USE_FD) && $this->isGranted(Privilege::BOOT);
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFilter(ListHandler $listHandler): void
    {
        $qb = $this->entityManager()->createQueryBuilder();
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
            $qb = $this->entityManager()->createQueryBuilder();
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
            $qb = $this->entityManager()->createQueryBuilder();
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
            $qb = $this->entityManager()->createQueryBuilder();
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
            $qb = $this->entityManager()->createQueryBuilder();
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
            $qb = $this->entityManager()->createQueryBuilder();
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

        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');

        $fileDistributionFilterHash = [];
        foreach ($er->findAll() as $f) {
            /** @var $f FileDistribution */
            if (isset($fileDistributionFilterHash[$f->getPlainTitle()])) {
                // skip if we have already a filter for the distribution
                continue;
            }

            $fileDistributionFilterHash[$f->getPlainTitle()] = true;

            $qb = $this->entityManager()->createQueryBuilder();
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

        $qb = $this->entityManager()->createQueryBuilder();
        $qb
            ->select('f3')
            ->from('StsblFileDistributionBundle:FileDistribution', 'f3')
            ->where($qb->expr()->eq('f3.ip', 'parent.ip'))
        ;

        $withFilter = new Filter\ListExpressionFilter(_('[With file distribution]'), $qb->expr()->exists($qb));
        $withFilter
            ->setName('file-distribution-with')
            ->setGroup(_('File distribution'))
        ;
        $listHandler->addListFilter($withFilter);

        $withoutFilter = new Filter\ListExpressionFilter(_('[Without file distribution]'), $qb->expr()->not($qb->expr()->exists($qb)));
        $withoutFilter
            ->setName('file-distribution-without')
            ->setGroup(_('File distribution'))
        ;
        $listHandler->addListFilter($withoutFilter);

        if ($this->isExamModeAvailable()) {
            $examRepository = $this->entityManager()->getRepository('StsblFileDistributionBundle:Exam');
            $examFilterHash = [];
            foreach ($examRepository->findAll() as $f) {
                if (isset($examFilterHash[$f->getTitle()])) {
                    // skip if we have already a filter for the exam
                    continue;
                }

                $examFilterHash[$f->getTitle()] = true;

                $qb = $this->entityManager()->createQueryBuilder();
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

            $qb = $this->entityManager()->createQueryBuilder();
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

        $qb = $this->entityManager()->createQueryBuilder();
        $qb
            ->select('fr')
            ->from('StsblFileDistributionBundle:FileDistributionRoom', 'fr')
            ->where($qb->expr()->eq('fr.room', 'filter.id'))
        ;
        $roomCondition = $qb->expr()->exists($qb);
        if (self::getRoomMode() === true) {
            $roomCondition = $qb->expr()->not($roomCondition);
        }

        $qb2 = $this->entityManager()->createQueryBuilder();
        $qb2
            ->select('h')
            ->from('StsblFileDistributionBundle:Host', 'h')
            ->where('h.room = filter.id')
            ->andWhere('h.controllable = true')
        ;

        $hostCondition = $qb->expr()->exists($qb2);

        $listHandler
            ->addListFilter(
                (new Filter\ListPropertyFilter(_('Room'), 'room', 'IServRoomBundle:Room', 'name', 'id'))->setName('room')
                    ->allowAnyAndNone()
                    ->setPickerOptions(['data-live-search' => 'true'])
                    ->setWhereCondition($qb->expr()->andX($roomCondition, $hostCondition))
            )
            ->addListFilter((new Filter\ListSearchFilter(_('Search'), [
                'name' => FilterSearch::TYPE_TEXT
            ])))
        ;

        /* @var $om \IServ\CrudBundle\Doctrine\ORM\ORMObjectManager */
        $om = $this->getObjectManager();
        $listHandler->getFilterHandler()->addEventSubscriber(
            new ListFilterEventSubscriber($this->request(), $om->getRepository(\IServ\HostBundle\Entity\Host::class))
        );
    }

    /**
     * Get host type by id
     */
    public function getHostType(string $type): HostType
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
        return $this->bundleDetector()->isLoaded($name);
    }

    /**
     * Check if exam mode is installed on the IServ.
     */
    public function isExamModeAvailable(): bool
    {
        return $this->hasBundle('IServExamBundle');
    }

    /**
     * Check if lock module is installed on the IServ.
     */
    public function isLockAvailable(): bool
    {
        return $this->hasBundle('IServLockBundle');
    }

    /**
     * Check if Internet GUI is installed on the IServ.
     */
    public function isInternetAvailable(): bool
    {
        return $this->hasBundle('StsblInternetBundle');
    }

    /**
     * Checks if internet is granted to an ip via a NAC.
     */
    private function isInternetGrantedViaNac(Host $host): bool
    {
        $er = $this->entityManager()->getRepository('StsblInternetBundle:Nac');
        $nac = $er->findOneBy(['ip' => $host->getIp()]);

        return $nac !== null;
    }

    /**
     * Get current internet state (yes, no, allowed, forbidden) for Host by his ip address.
     */
    public function getInternetState(Host $host): ?string
    {
        $overrideRoute = $host->getOverrideRoute();
        $internet = $host->getInternet();

        if ($this->isExamModeAvailable()) {
            /** @var EntitySpecificationRepository $examRepository */
            $examRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam');

            $examMode = $examRepository->find($host->getIp());

            if ($examMode !== null) {
                return 'exam';
            }
        }

        $qb = $this->entityManager()->createQueryBuilder();
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

        /* @var $currentUser User */
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
        }

        if ($internetAlwaysDenied === true) {
            return 'no_priv';
        }

        if ($overrideRoute === true) {
            return 'granted';
        }

        if ($this->isInternetAvailable() && $this->isInternetGrantedViaNac($host) === true) {
            return 'yes_nac';
        }

        if ($internetAlwaysGranted === true) {
            return 'yes_priv';
        }

        if ($internet === true) {
            return 'yes';
        }

        if ($internet === false) {
            return 'no';
        }

        return null;
    }

    /**
     * Get current internet lock explanation for Host by his ip address.
     */
    public function getInternetExplanation(Host $host): ?array
    {
        if ($host === null) {
            return null;
        }

        $overrideRoute = $host->getOverrideRoute();

        if ($this->isExamModeAvailable()) {
            /** @var EntitySpecificationRepository $examRepository */
            $examRepository = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam');

            /* @var $examMode Exam */
            $examMode = $examRepository->find($host->getIp());

            if ($examMode !== null) {
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
                'user' => $this->entityManager()->getRepository('IServCoreBundle:User')->find($host->getOverrideBy()),
                'until' => $host->getOverrideUntil(),
            ];
        }

        if ($this->isInternetAvailable() && $this->isInternetGrantedViaNac($host)) {
            /* @var $nac \Stsbl\InternetBundle\Entity\Nac */
            /** @noinspection PhpUndefinedMethodInspection */
            $nac = $this->entityManager()->getRepository('StsblInternetBundle:Nac')->findOneByIp($host->getIp());
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
        if ($host !== null) {
            return $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution')->findOneByIp($host->getIp());
        }

        return null;
    }

    public function getExam(Host $host): ?Exam
    {
        if ($host !== null) {
            return $this->getObjectManager()->getRepository('StsblFileDistributionBundle:Exam')->findOneByIp($host->getIp());
        }

        return null;
    }

    /**
     * Get user who locked the sound on a computer.
     */
    public function getSoundLockUser(Host $host): ?User
    {
        /* @var $lock \Stsbl\FileDistributionBundle\Entity\SoundLock */
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

        return $this->entityManager()->getRepository('IServCoreBundle:User')->find($act);
    }

    private function bundleDetector(): BundleDetector
    {
        return $this->locator->get(BundleDetector::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return \array_merge(parent::getSubscribedServices(), [
            BundleDetector::class,
            Config::class,
            EntityManagerInterface::class,
            FileDistributionManager::class,
            '?' . Internet::class,
            '?' . LockManager::class,
            RequestStack::class,
            SessionInterface::class,
        ]);
    }
}
