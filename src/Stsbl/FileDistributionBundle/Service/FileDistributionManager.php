<?php
// src/Stsbl/FileDistributionBundle/Service/FileDistributionManager.php
namespace Stsbl\FileDistributionBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Service\HostManager;
use IServ\HostBundle\Service\HostStatus;

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
    
    /**
     * Get IPs for a collection of hosts.
     * Add plus if it is Windows, add dobule plus if it is Linux.
     *
     * @param HostEntity|array|ArrayCollection $hosts
     * @throws \InvalidArgumentException
     * @return array string array of ips of the hosts, prefixed with '+' if online
     */
    protected function getIpsForHosts($hosts)
    {
        if (!is_array($hosts) and !($hosts instanceof ArrayCollection)) {
            $hosts = array($hosts);
        }

        $ips = array();
        foreach ($hosts as $h) {
            $ping = $this->ping($hosts);
            if (!($h instanceof Host)) {
                throw new \InvalidArgumentException('Argument must be an instance of \Iserv\HostBundle\Entity\Host');
            }
            
            if ($ping[$h->getId()]['status'] >= HostStatus::LINUX) {
                $ips[] = '++' . $h->getIp();
            } else if ($ping[$h->getId()]['status'] >= HostStatus::WIN) {
                $ips[] = '+' . $h->getIp();
            } else {
                $ips[] = $h->getIp();
            }
        }

        return $ips;
    }
    
    /**
     * Ping hosts and check if Windows or Linux is running.
     *
     * @param HostEntity|array|ArrayCollection $hosts
     * @return array Results (key: host name, value: 0=offline, 1=online, 2=Windows)
     */
    public function ping($hosts)
    {
        if (!is_array($hosts) and !($hosts instanceof ArrayCollection)) {
            $hosts = array($hosts);
        }

        $ips = array();
        $newhosts = array();
        foreach ($hosts as $h) {
            /* @var $h HostEntity */
            if (!($h instanceof Host)) {
                throw new \InvalidArgumentException('Argument must be an instance of \Iserv\HostBundle\Entity\Host');
            }
            $ips[$h->getIp()] = $h->getId();
            $newhosts[$h->getId()] = $h;
        }
        $hosts = $newhosts;

        $res = array_fill_keys($ips, ['status' => HostStatus::OFFLINE]);
        $this->shell->exec('sudo', array_merge(array('/usr/lib/iserv/winping'), array_keys($ips)));
        $output = $this->shell->getOutput();

        foreach ($output as $v) {
            $m = null;
            if (preg_match("|Host: ([\d.]+) |", $v, $m)) {
                $res[$ips[$m[1]]] = ['status' => HostStatus::ONLINE];
                if (preg_match("|445/open/tcp/|", $v)) {
                    $res[$ips[$m[1]]]['status'] |= HostStatus::WIN;
                }
                if (preg_match("|22/open/tcp/|", $v)) {
                    $res[$ips[$m[1]]]['status'] |= HostStatus::LINUX;
                }
                $hosts[$ips[$m[1]]]->setLastseenPing(new \DateTime);
                $this->em->persist($hosts[$ips[$m[1]]]);
            }
        }

        $this->em->flush();

        $this->status->merge($res);

        return $res;
    }
}
