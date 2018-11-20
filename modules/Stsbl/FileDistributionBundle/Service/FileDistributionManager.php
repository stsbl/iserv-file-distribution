<?php declare(strict_types = 1);
// src/Stsbl/FileDistributionBundle/Service/FileDistributionManager.php
namespace Stsbl\FileDistributionBundle\Service;

use IServ\CoreBundle\Exception\ShellExecException;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Service\HostManager;

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
 * Handles File Distribution functions
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionManager
{
    // Commands
    const FD_RPC = '/usr/lib/iserv/file_distribution_rpc';
    // Constants for file_distribution_rpc
    const FD_ON = 'fdon';
    const FD_OFF = 'fdoff';
    const FD_SOUNDON = 'soundon';
    const FD_SOUNDOFF = 'soundoff';
    // Constants for netrpc
    const NETRPC_EXAM_ON = 'examon';
    const NETRPC_EXAM_OFF = 'examoff';

    /**
     * @var HostManager
     */
    private $hostManager;

    /**
     * @var SecurityHandler
     */
    private $securityHandler;

    /**
     * @var Shell
     */
    private $shell;

    public function __construct(HostManager $hostManager, SecurityHandler $securityHandler, Shell $shell)
    {
        $this->hostManager = $hostManager;
        $this->securityHandler = $securityHandler;
        $this->shell = $shell;
    }

    /**
     * Execute file_distribution_rpc command.
     *
     * @param string $cmd
     * @param array $args
     * @param string $arg
     * @param bool|null $isolation
     * @param string|null $folderAvailability
     * @return FlashMessageBag
     */
    public function fileDistributionRpc(
        string $cmd,
        array $args = [],
        string $arg = null,
        bool $isolation = null,
        string $folderAvailability = null
    ): FlashMessageBag {
        $env = ['SESSPW' => $this->securityHandler->getSessionPassword()];
        
        if ($arg != null) {
            $env['ARG'] = $arg;
        }
        
        if ($isolation !== null) {
            if (true === $isolation) {
                $env['FD_ISOLATION'] = 1;
            } elseif (false === $isolation) {
                $env['FD_ISOLATION'] = 0;
            } else {
                throw new \InvalidArgumentException('Isolation must be a boolean value!');
            }
        }
        
        if ($folderAvailability !== null) {
            $env['FD_FOLDER_AVAILABILITY'] = $folderAvailability;
        }
        
        return $this->shellMsg(
            'sudo',
            array_merge([self::FD_RPC, $this->securityHandler->getUser()->getUsername(), $cmd], $args),
            null,
            $env
        );
    }
    
    /**
     * Enable file distribution on hosts.
     *
     * @param Host|\ArrayAccess $hosts
     * @param string $title
     * @param bool|null $isolation
     * @param string $folderAvailability
     * @return FlashMessageBag
     */
    public function enableFileDistribution(
        $hosts,
        string $title,
        bool $isolation,
        string $folderAvailability
    ): FlashMessageBag {
        return $this->fileDistributionRpc(
            self::FD_ON,
            $this->hostManager->getIpsForHosts($hosts),
            $title,
            $isolation,
            $folderAvailability
        );
    }
    
    /**
     * Disable file distribution on hosts.
     *
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function disableFileDistribution($hosts): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_OFF, $this->hostManager->getIpsForHosts($hosts));
    }
    
    /**
     * Disable sound on hosts.
     *
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function soundLock($hosts): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_SOUNDOFF, $this->hostManager->getIpsForHosts($hosts));
    }
    
    /**
     * Enable sound on hosts.
     *
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function soundUnlock($hosts): FlashMessageBag
    {
        return $this->fileDistributionRpc(self::FD_SOUNDON, $this->hostManager->getIpsForHosts($hosts));
    }
    
    /**
     * Enable exam mode on hosts.
     *
     * @param Host|\ArrayAccess $hosts
     * @param string $title
     * @return FlashMessageBag
     *
     * // TODO remove and use interface from ExamBundle!
     */
    public function examOn($hosts, $title)
    {
        return $this->hostManager->netrpc(self::NETRPC_EXAM_ON, $this->hostManager->getIpsForHosts($hosts), $title);
    }

    /**
     * Disable exam mode on hosts.
     *
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     *
     * // TODO remove and use interface from ExamBundle!
     */
    public function examOff($hosts)
    {
        return $this->hostManager->netrpc(self::NETRPC_EXAM_OFF, $this->hostManager->getIpsForHosts($hosts));
    }

    /**
     * Execute a command and return a FlashMessageBag with STDOUT lines as
     * warning messages and STDERR lines as error messages.
     * Similar to the original from HostManager, but filter out empty
     * stdout lines.
     *
     * @param string $cmd
     * @param mixed[] $args
     * @param mixed $stdIn
     * @param array $env
     * @return FlashMessageBag STDOUT and STDERR contents as FlashMessageBag
     */
    private function shellMsg(
        string $cmd,
        array $args = null,
        $stdIn = null,
        array $env = null
    ): FlashMessageBag {
        try {
            $this->shell->exec($cmd, $args, $stdIn, $env);
        } catch (ShellExecException $error) {
            throw new \RuntimeException('Could not execute shell command!', 0, $error);
        }

        $messages = new FlashMessageBag();
        foreach ($this->shell->getOutput() as $output) {
            if (strlen($output) !== 0) {
                $messages->addMessage('warning', $output);
            }
        }

        foreach ($this->shell->getError() as $error) {
            $messages->addMessage('error', $error);
        }

        return $messages;
    }
}
