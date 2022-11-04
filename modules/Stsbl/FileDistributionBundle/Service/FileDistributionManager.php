<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Exception\ShellExecException;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Service\HostManager;
use IServ\HostBundle\Service\HostStatus;

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
 * Handles File Distribution functions
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class FileDistributionManager
{
    // Commands
    public const FD_RPC = '/usr/lib/iserv/file_distribution_rpc';
    // Constants for file_distribution_rpc
    public const FD_ON = 'fdon';
    public const FD_OFF = 'fdoff';
    public const FD_SOUNDON = 'soundon';
    public const FD_SOUNDOFF = 'soundoff';
    // Constants for netrpc
    public const NETRPC_EXAM_ON = 'examon';
    public const NETRPC_EXAM_OFF = 'examoff';

    public function __construct(
        private readonly HostManager $hostManager,
        private readonly SecurityHandler $securityHandler,
        private readonly Shell $shell,
    ) {
    }

    /**
     * Execute file_distribution_rpc command.
     */
    private function fileDistributionRpc(string $cmd, array $args = [], ?string $arg = null, ?bool $isolation = null, ?string $folderAvailability = null): FlashMessageBag
    {
        $env = ['SESSPW' => $this->securityHandler->getSessionPassword()];

        if ($arg !== null) {
            $env['ARG'] = $arg;
        }

        if ($isolation !== null) {
            if ($isolation === true) {
                $env['FD_ISOLATION'] = 1;
            } elseif ($isolation === false) {
                $env['FD_ISOLATION'] = 0;
            } else {
                throw new \InvalidArgumentException('Isolation must be a boolean value!');
            }
        }

        if ($folderAvailability !== null) {
            $env['FD_FOLDER_AVAILABILITY'] = $folderAvailability;
        }

        return $this->shellMsgFilter(
            'sudo',
            array_merge([self::FD_RPC, $this->securityHandler->getUser()->getUsername(), $cmd], $args),
            null,
            $env
        );
    }

    /**
     * Enable file distribution on hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function enableFileDistribution(array|Host $hosts, string $title, bool $isolation, string $folderAvailability): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_ON, $this->hostManager->getIpsForHosts($hosts), $title, $isolation, $folderAvailability);
    }

    /**
     * Disable file distribution on hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function disableFileDistribution(array|Host $hosts): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_OFF, $this->hostManager->getIpsForHosts($hosts));
    }

    /**
     * Disable sound on hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function soundLock(array|Host $hosts): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_SOUNDOFF, $this->hostManager->getIpsForHosts($hosts));
    }

    /**
     * Enable sound on hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function soundUnlock(array|Host $hosts): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_SOUNDON, $this->hostManager->getIpsForHosts($hosts));
    }

    /**
     * Send message to hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function msg(array|Host $hosts, string $msg): FlashMessageBag
    {
        @trigger_error(
            'msg() is deprecated and will removed in future versions. Use sendMessage() from HostManager instead.',
            E_USER_DEPRECATED
        );

        return $this->hostManager->sendMessage($hosts, $msg);
    }

    /**
     * Enable exam mode on hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function examOn(array|Host $hosts, string $title): FlashMessageBag
    {
        return $this->hostManager->netrpc(self::NETRPC_EXAM_ON, $this->hostManager->getIpsForHosts($hosts), $title);
    }

    /**
     * Disable exam mode on hosts.
     *
     * @param Host|Host[] $hosts
     */
    public function examOff(array|Host $hosts): FlashMessageBag
    {
        return $this->hostManager->netrpc(self::NETRPC_EXAM_OFF, $this->hostManager->getIpsForHosts($hosts));
    }

    /**
     * Execute a command and return a FlashMessageBag with STDOUT lines as
     * warning messages and STDERR lines as error messages.
     * Similar to the original from HostManager, but filter out empty
     * stdout lines.
     */
    protected function shellMsgFilter(string $cmd, ?array $args = null, string $stdin = null, array $env = null): FlashMessageBag
    {
        try {
            $this->shell->exec($cmd, $args, $stdin, $env);
        } catch (ShellExecException $e) {
            throw new \RuntimeException('Could not execute command!', 0, $e);
        }

        $messages = new FlashMessageBag();
        foreach ($this->shell->getOutput() as $o) {
            if ('' !== $o) {
                $messages->addMessage('warning', $o);
            }
        }

        foreach ($this->shell->getError() as $e) {
            $messages->addMessage('error', $e);
        }

        return $messages;
    }

    /**
     * Delegate old calls to HostManager and throw deprecation notice
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->hostManager, $name) && is_callable([$this->hostManager, $name])) {
            @trigger_error(sprintf(
                'To call the method "%s" on "%s" and expect delegation to "%s" is deprecated and will be removed!',
                $name,
                get_class($this),
                get_class($this->hostManager)
            ), E_USER_DEPRECATED);
            return call_user_func_array([$this->hostManager, $name], $arguments);
        }

        throw new \LogicException(sprintf('Unknown method `%s`!', $name));
    }
}
