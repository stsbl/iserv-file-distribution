<?php
// src/Stsbl/FileDistributionBundle/Service/Rpc.php
namespace Stsbl\FileDistributionBundle\Service;

use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Shell;
use Stsbl\FileDistributionBundle\Entity\Host;

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
 * FileDistribution rpc service
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class Rpc 
{
    const COMMAND = '/usr/lib/iserv/file_distribution_rpc';
    const COMMAND_NETRPC = '/usr/lib/iserv/netrpc';
    
    // Constants for file_distribution_rpc
    const ON = 'fdon';
    const OFF = 'fdoff';
    const SOUNDON = 'soundon';
    const SOUNDOFF = 'soundoff';
    
    // Constants for netrpc
    const NETRPC_MESSAGE = 'msg';
    
    /**
     * @var string
     */
    private $arg;
    
    /**
     * @var boolean
     */
    private $isolation;
    
    /**
     * @var array<Host>
     */
    private $hosts;
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * Set arg for the next operation
     * 
     * @param string $arg
     */
    public function setArg($arg)
    {
        $this->arg = $arg;
    }
    
    /**
     * Legacy title function
     * 
     * @param string $title
     * @deprecated
     */
    public function setTitle($title)
    {
        @trigger_error('setTitle is deprecated, use setArg instead!', E_USER_DEPRECATED);
        
        $this->setArg($title);
    }

    /**
     * Set isolation for the next operation
     *
     * @param boolean $isolation 
     */
    public function setIsolation($isolation)
    {
        $this->isolation = $isolation;
    }
    
    /**
     * Set hosts for next operation
     * 
     * @param array<Host> $hosts
     */
    public function setHosts(array $hosts)
    {
        $this->hosts = $hosts;
    }
    
    /**
     * Add a single host
     * 
     * @param Host $host
     */
    public function addHost(Host $host)
    {
        $this->hosts[] = $host;
    }
    
    /**
     * Returns base command line.
     * 
     * @param $args
     * @return array
     */
    private function getBaseCommandLine()
    {
        return [
            self::COMMAND,
            $this->securityHandler->getUser()->getUsername()
        ];
    }

    /**
     * Returns base command line.
     * 
     * @param $args
     * @return array
     */
    private function getNetrpcBaseCommandLine()
    {
        return [
            self::COMMAND_NETRPC,
            $this->securityHandler->getUser()->getUsername()
        ];
    }
    /**
     * Prepare hosts for command line
     * 
     * @param array $args
     * @return array
     */
    private function addHostsToCommandLine(array $args)
    {
        if (count($this->hosts) < 1) {
            throw new \InvalidArgumentException('No hosts specified!');
        }
        
        foreach ($this->hosts as $h) {
            $args[] = $h->getIp();
        }
        
        return $args;
    }
    
    /**
     * Executes command with given arguments
     * 
     * @param array $args
     * @param mixed $arg
     * @param integer $isolation
     */
    private function execute(array $args, $arg = null, $isolation = null)
    {
        $env = ['SESSPW' => $this->securityHandler->getSessionPassword()];
        
        if (!is_null($arg)) {
            $env['ARG'] = $arg;
        }
        
        if (!is_null($isolation)) {
            $env['FD_ISOLATION'] = $isolation;
        }
        
        $this->shell->exec('closefd setsid sudo', $args, null, $env);
    }
    
    /**
     * The constructor
     * 
     * @param Shell $shell
     * @param SecurityHandler $securityHandler
     */
    public function __construct(Shell $shell, SecurityHandler $securityHandler) 
    {
        $this->shell = $shell;
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * Enable file distribution for the hosts which were previously set via <tt>setHosts</tt>. 
     */
    public function enable()
    {
        $args = $this->getBaseCommandLine();
        $args[] = self::ON;
        $args = $this->addHostsToCommandLine($args);
        
        if (empty($this->arg)) {
            throw new \InvalidArgumentException('No argument specified!');
        }
        
        if (!is_bool($this->isolation)) {
            throw new \InvalidArgumentException(sprintf('Isolation must be a booelan value, %s given.', gettype($this->isolation)));
        }
            
        if (true === $this->isolation) {
            $isolation = 1;
        } else {
            $isolation = 0;
        }
        
        $this->execute($args, $this->arg, $isolation);
    }
    
    /**
     * Disable file distribution for the hosts which were previously set via <tt>setHosts</tt>.
     */
    public function disable()
    {
        $args = $this->getBaseCommandLine();
        $args[] = self::OFF;
        $args = $this->addHostsToCommandLine($args);
        
        $this->execute($args);
    }
    
    /**
     * Lock sound for the hosts which were previously set via <tt>setHosts</tt>.
     */
    public function soundLock()
    {
        $args = $this->getBaseCommandLine();
        $args[] = self::SOUNDOFF;
        $args = $this->addHostsToCommandLine($args);
        
        $this->execute($args);
    }

    /**
     * Unlock sound for the hosts which were previously set via <tt>setHosts</tt>.
     */
    public function soundUnlock()
    {
        $args = $this->getBaseCommandLine();
        $args[] = self::SOUNDON;
        $args = $this->addHostsToCommandLine($args);
        
        $this->execute($args);
    }
    
    /**
     * Send message to hosts the hosts which were previouśly set via <tt>setHosts</tt> via IServ netrpc.
     */
    public function sendMessage()
    {
        $args = $this->getNetrpcBaseCommandLine();
        $args[] = self::NETRPC_MESSAGE;
        $args = $this->addHostsToCommandLine($args);
        
        if (empty($this->arg)) {
            throw new \InvalidArgumentException('No argument specified!');
        }
        
        $this->execute($args, $this->arg);
    }
    
    /**
     * Get last shell output
     * 
     * @return array
     */
    public function getOutput()
    {
        return $this->shell->getOutput();
    }
    
    /**
     * Get last shell error output
     * 
     * @return array
     */
    public function getErrorOutput()
    {
        return $this->shell->getError();
    }
    
    /**
     * Gets last shell exit code
     * 
     * @return integer
     */
    public function getExitCode()
    {
        return $this->shell->getExitCode();
    }
}
