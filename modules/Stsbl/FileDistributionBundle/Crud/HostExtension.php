<?php
declare(strict_types = 1);

namespace Stsbl\FileDistributionBundle\Crud;

use IServ\ComputerBundle\Crud\HostControlExtensionInterface;
use IServ\CoreBundle\Service\Config;
use IServ\HostBundle\Util\Network;
use IServ\HostExtensionBundle\Crud\AbstractHostExtension;
use Stsbl\FileDistributionBundle\Crud\Batch\EnableAction;
use Stsbl\FileDistributionBundle\Crud\Batch\SoundLockAction;
use Stsbl\FileDistributionBundle\Crud\Batch\SoundUnlockAction;
use Stsbl\FileDistributionBundle\Crud\Batch\StopAction;
use Stsbl\FileDistributionBundle\DependencyInjection\HostExtensionAwareInterface;
use Stsbl\FileDistributionBundle\DependencyInjection\ManagerAwareInterface;
use Stsbl\FileDistributionBundle\DependencyInjection\ManagerAwareTrait;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Stsbl\FileDistributionBundle\Security\PrivilegeDetector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/*
 * The MIT License
 *
 * Copyright 2019 Felix Jacobi.
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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class HostExtension extends AbstractHostExtension implements ManagerAwareInterface, HostControlExtensionInterface
{
    use ManagerAwareTrait;

    const NAME = 'file_distribution';

    /**
     * {@inheritdoc}
     */
    protected $privilege = Privilege::USE_FD;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PrivilegeDetector
     */
    private $privilegeDetector;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RequestStack
     */
    private $requestStack;


    public function __construct(
        Config $config,
        PrivilegeDetector $privilegeDetector,
        RequestStack $requestStack,
        SessionInterface $session
    ) {
        $this->config = $config;
        $this->privilegeDetector = $privilegeDetector;
        $this->session = $session;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function loadBatchActions()
    {
        $batchActions = [
            new EnableAction($this->crud, $this->getRequest()),
            new StopAction($this->crud),
            new SoundUnlockAction($this->crud),
            new SoundLockAction($this->crud),
        ];

        foreach ($batchActions as $batchAction) {
            if ($batchAction instanceof ManagerAwareInterface) {
                $batchAction->setFileDistributionManager($this->getFileDistributionManager());
            }

            if ($batchAction instanceof HostExtensionAwareInterface) {
                $batchAction->setHostExtension($this);
            }
        }

        if (null === $this->crud->getUser() || $this->crud->getAuthorizationChecker()->isGranted(Privilege::USE_FD)) {
            return $batchActions;
        }

        return [];
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getPrivilegeDetector(): PrivilegeDetector
    {
        return $this->privilegeDetector;
    }

    protected function getRequest(): ?Request
    {
        if (null === $this->request) {
            $this->request = $this->requestStack->getCurrentRequest();
        }

        return $this->request;
    }

    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * Checks if current request comes from LAN
     */
    public function isInLan(): bool
    {
        return Network::ipInLan(null, $this->getConfig()->get('LAN'), $this->getRequest());
    }
}
