<?php
// src/Stsbl/FileDistributionBundle/Service/RPC.php
namespace Stsbl\FileDistributionBundle\Service;

use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Shell;
use Stsbl\FileDistributionBundle\Entity\Host;

/*
 * The MIT License
 *
 * Copyright 2017 Fleix Jacobi.
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
 * FileDistribution RPC service
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class Rpc 
{
    const COMMAND = '/usr/lib/iserv/file_distribution_rpc';
    
    // Constants for file_distribution_rpc
    const ON = 'fdon';
    const OFF = 'fdoff';
    
    /**
     * @var string
     */
    private $title;
    
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
     * Set title for the next operation
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
        $args = [];
        $args[] = self::COMMAND;
        $args[] = $this->securityHandler->getUser()->getUsername();
        $args[] = self::ON;
        
        if (count($this->hosts) < 1) {
            throw new \InvalidArgumentException('No hosts specified!');
        }
        
        if (empty($this->title)) {
            throw new \InvalidArgumentException('No title specified!');
        }
        
        foreach ($this->hosts as $h) {
            $args[] = $h->getIp();
        }
        
        $this->shell->exec('closefd setsid sudo', $args, null, ['ARG' => $this->title, 'SESSPW' => $this->securityHandler->getSessionPassword()]);
    }
    
    /**
     * Disable file distribution for the hosts which were previously set via <tt>setHosts</tt>.
     */
    public function disable()
    {
        $args = [];
        $args[] = self::COMMAND;
        $args[] = $this->securityHandler->getUser()->getUsername();
        $args[] = self::OFF;
        
        if (count($this->hosts) < 1) {
            throw new \InvalidArgumentException('No hosts specified!');
        }
        
        foreach ($this->hosts as $h) {
            $args[] = $h->getIp();
        }
        
        $this->shell->exec('closefd setsid sudo', $args, null, ['SESSPW' => $this->securityHandler->getSessionPassword()]);
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
