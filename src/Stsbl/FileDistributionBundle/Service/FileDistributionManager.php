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
     * Execute file_distribution_rpc command.
     *
     * @param string $cmd
     * @param array $args
     * @param string $arg
     * @param boolean $isolation
     * @return FlashMessageBag
     */
    public function fileDistributionRpc($cmd, array $args = array(), $arg = null, $isolation = null, $folderAvailability = null)
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
        
        if ($folderAvailability != null) {
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
     * @param Host|\ArrayAccess $hosts
     * @param string $title
     * @param boolean $isolation
     * @param string $folderAvailability
     * @return FlashMessageBag
     */
    public function enableFileDistribution($hosts, $title, $isolation, $folderAvailability)
    {
        return $this->fileDistributionRpc(self::FD_ON, $this->getIpsForHosts($hosts), $title, $isolation, $folderAvailability);
    }
    
    /**
     * Disable file distribution on hosts.
     * 
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function disableFileDistribution($hosts)
    {
        return $this->fileDistributionRpc(self::FD_OFF, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Disable sound on hosts.
     * 
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function soundLock($hosts)
    {
        return $this->fileDistributionRpc(self::FD_SOUNDOFF, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Enable sound on hosts.
     * 
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function soundUnlock($hosts)
    {
        return $this->fileDistributionRpc(self::FD_SOUNDON, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Send message to hosts.
     *
     * @deprecated 
     * @param Host|\ArrayAccess $hosts
     * @param string $msg
     * @return FlashMessageBag
     */
    public function msg($hosts, $msg)
    {
        @trigger_error('msg() is deprecated and will removed in future versions. Use sendMessage() instead.',
            E_USER_DEPRECATED);

        return $this->sendMessage($hosts, $msg);
    }
    
    /**
     * Enable exam mode on hosts.
     * 
     * @param Host|\ArrayAccess $hosts
     * @param string $title
     * @return FlashMessageBag
     */
    public function examOn($hosts, $title)
    {
        return $this->netrpc(self::NETRPC_EXAM_ON, $this->getIpsForHosts($hosts), $title);
    }

    /**
     * Disable exam mode on hosts.
     * 
     * @param Host|\ArrayAccess $hosts
     * @return FlashMessageBag
     */
    public function examOff($hosts)
    {
        return $this->netrpc(self::NETRPC_EXAM_OFF, $this->getIpsForHosts($hosts));
    }
    
    /**
     * Get IPs for a collection of hosts.
     * Add plus if it is Windows, add dobule plus if it is Linux.
     *
     * @param HostEntity|array|ArrayCollection $hosts
     * @throws \InvalidArgumentException
     * @return array string array of ips of the hosts, prefixed with '+' if online
     */
    public function getIpsForHosts($hosts)
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
    
    /**
     * Execute a command and return a FlashMessageBag with STDOUT lines as
     * warning messages and STDERR lines as error messages.
     * Similar to the original from HostManager, but filter out empty
     * stdout lines.
     *
     * @param string $cmd
     * @param mixed $args
     * @param mixed $stdin
     * @param array $env
     * @return FlashMessageBag STDOUT and STDERR contents as FlashMessageBag
     */
    protected function shellMsgFilter($cmd, $args = null, $stdin = null, $env = null)
    {
        $this->shell->exec($cmd, $args, $stdin, $env);

        $messages = new FlashMessageBag();
        foreach ($this->shell->getOutput() as $o) {
            if (!empty($o)) $messages->addMessage('warning', $o);
        }

        foreach ($this->shell->getError() as $e) {
            $messages->addMessage('error', $e);
        }

        return $messages;
    }

    /**
     * Execute a command and return a FlashMessageBag with STDERR 
     * lines as error messages.
     * Similar to the original from HostManager, but only show
     * STDERR lines.
     *
     * @param string $cmd
     * @param mixed $args
     * @param mixed $stdin
     * @param array $env
     * @return FlashMessageBag STDERR content as FlashMessageBag
     */
    protected function shellMsgError($cmd, $args = null, $stdin = null, $env = null)
    {
        $this->shell->exec($cmd, $args, $stdin, $env);

        $messages = new FlashMessageBag();
        foreach ($this->shell->getError() as $e) {
            $messages->addMessage('error', $e);
        }

        return $messages;
    }
    
    /**
     * Execute activation command.
     * 
     * @return FlashMessageBag STDOUT and STDERR contents as FlashMessageBag
     */
    public function activation()
    {
        return $this->shellMsgError('sudo', '/usr/lib/iserv/activation');
    }
    
    /**
     * {@inheritdoc}
     */
    public function wol($hosts)
    {
        $messages = new FlashMessageBag();
        foreach ($hosts as $h) {
            if ($mac = $h->getMac()) {
                // determine broadcast route to the host
                if (!($route = $this->ifBcast($this->findRoute($h->getIp())))) {
                    $route = $h->getIp();
                }

                $this->status->update($h, HostStatus::WOL);

                // send a WOL packet
                $messages->addAll(
                    $this->shellMsgError('wakeonlan', array('-i', $route, $mac))
                );
            } else {
                $messages->addMessage(
                    "warning",
                    _("Cannot wake host \"%host%\" because it has no MAC address set."),
                    ["%host%" => $h->getName()]
                );
            }
        }
        
        return $messages;
    }
}
