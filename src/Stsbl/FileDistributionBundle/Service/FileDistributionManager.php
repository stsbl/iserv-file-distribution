<?php
// src/Stsbl/FileDistributionBundle/Service/FileDistributionManager.php
namespace Stsbl\FileDistributionBundle\Service;

use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Service\HostManager;

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
 * Handles File Distribution functions
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionManager extends HostManager
{
    // Commands
    const NETRPC = '/usr/lib/iserv/netrpc';
    const FD_RPC = '/usr/lib/iserv/file_distribution_rpc';
    // Constants for file_distribution_rpc
    const FD_ON = 'fdon';
    const FD_OFF = 'fdoff';
    const FD_SOUNDON = 'soundon';
    const FD_SOUNDOFF = 'soundoff';
    // Constants for netrpc
    const NETRPC_MSG = 'msg';
    
    /**
     * Execute netrpc command.
     * Improved version with support for ARG environment variable.
     *
     * @param string $cmd
     * @param array $args
     * @param string $arg
     * @return FlashMessageBag
     */
    public function netrpc($cmd, array $args = array(), $arg = null)
    {
        $env = ['SESSPW' => $this->securityHandler->getSessionPassword()];
        
        if ($arg != null) {
            $env['ARG'] = $arg;
        }
        
        return $this->shellMsg(
            'sudo',
            array_merge([self::NETRPC, $this->securityHandler->getUser()->getUsername(), $cmd], $args),
            null,
            $env
        );
    }
    
    /**
     * Execute file_distribution_rpc command.
     *
     * @param string $cmd
     * @param array $args
     * @param string $arg
     * @param boolean $isolation
     * @return FlashMessageBag
     */
    public function fileDistributionRpc($cmd, array $args = array(), $arg = null, $isolation = null)
    {
        $env = ['SESSPW' => $this->securityHandler->getSessionPassword()];
        
        if ($arg != null) {
            $env['ARG'] = $arg;
        }
        
        if ($isolation != null) {
            if ($isolation === true) {
                $env['FD_ISOLATION'] = 1;
            } else if ($isolation === false) {
                $env['FD_ISOLATION'] = 0;
            } else {
                throw new \InvalidArgumentException('Isolation must be a boolean value!');
            }
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
     * @param boolean $isolation
     * @return FlashMessageBag
     */
    public function enableFileDistribution($hosts, $title, $isolation)
    {
        return $this->fileDistributionRpc(self::FD_ON, $this->getIpsForHosts($hosts), $title, $isolation);
    }
    
    /**
     * Disable file distribution on hosts.
     * 
     * @param Host\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function disableFileDistribution($hosts)
    {
        return $this->fileDistributionRpc(self::FD_OFF, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Disable sound on hosts.
     * 
     * @param Host\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function soundLock($hosts)
    {
        return $this->fileDistributionRpc(self::FD_SOUNDOFF, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Enable sound on hosts.
     * 
     * @param Host\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function soundUnlock($hosts)
    {
        return $this->fileDistributionRpc(self::FD_SOUNDON, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Send message to hosts
     * 
     * @param Host\ArrayAccess $hosts
     * @param string $msg
     * @return FlashMessageBag
     */
    public function msg($hosts, $msg)
    {
        return $this->netrpc(self::NETRPC_MSG, $this->getIpsForHosts($hosts), $msg);
    }
}
