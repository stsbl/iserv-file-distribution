<?php
// src/Stsbl/FileDistributionBundle/Enttiy/Set.php
namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\HostBundle\Entity\Host;
use IServ\CoreBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

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
 * FileDistributionBundle:FileDistribution
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity
 * @ORM\Table(name="file_distribution")
 * @DoctrineAssert\UniqueEntity("ip", message="File distribution is already enabled for this host.")
 * @DoctrineAssert\UniqueEntity("hostname", message="File distribution is already enabled for this host.")
 */
class FileDistribution implements CrudInterface {
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @var int
     */
    private $id;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Regex("/^([ !#&()*+,\-.0-9:<=>?\@A-Z\[\]^_a-z{|}~$])/", message="The title contains invalid characters.")
     *
     * @var string
     */
    private $title;
    
    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="act", referencedColumnName="act", onDelete="CASCADE")
     * @Assert\NotBlank()
     *
     * @var User
     */
    private $user;
    
    /**
     * @ORM\OneToOne(targetEntity="\IServ\HostBundle\Entity\Host", fetch="EAGER")
     * @ORM\JoinColumn(name="hostname", referencedColumnName="name", onDelete="CASCADE")
     * @Assert\NotBlank()
     * 
     * @var \IServ\HostBundle\Entity\Host
     */
    private $hostname;
    
    /**
     * DO NOT ADD ANY REFERENCES to Host here, because Symfony do not like it!
     * 
     * //@ORM\OneToOne(targetEntity="\IServ\HostBundle\Entity\Host", fetch="EAGER")
     * //@ORM\JoinColumn(name="ip", referencedColumnName="ip", onDelete="CASCADE")
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Ip()
     * 
     * @var string
     */
    private $ip;
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->hostname;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hostname
     *
     * @param \IServ\HostBundle\Entity\Host $hostname
     *
     * @return FileDistribution
     */
    public function setHostname(Host $hostname = null)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Get hostname
     *
     * @return Host
     */
    public function getHostname()
    {
        return $this->hostname;
    }
    
    /**
     * Get displayable hostname
     * 
     * @return string
     */
    public function getHostnameDisplay()
    {
        return $this->hostname->getName();
    }

    /**
     * Set ip
     *
     * @param Host $ip
     *
     * @return FileDistribution
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return Host
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return FileDistribution
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return sprintf('%s - %s', $this->title, (string)$this->user);
    }
    
    /**
     * Get plain title
     * 
     * @return string
     */
    public function getPlainTitle()
    {
        return $this->title;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return FileDistribution
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
