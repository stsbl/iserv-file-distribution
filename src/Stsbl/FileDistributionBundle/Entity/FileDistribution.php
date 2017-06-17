<?php
// src/Stsbl/FileDistributionBundle/Enttiy/Set.php
namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
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
 * StsblFileDistributionBundle:FileDistribution
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity(repositoryClass="FileDistributionRepository")
 * @ORM\Table(name="file_distribution")
 * @DoctrineAssert\UniqueEntity("ip", message="File distribution is already enabled for this host.")
 */
class FileDistribution implements CrudInterface 
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @var integer
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
     * @ORM\Column(type="text", nullable=false)
     * 
     * @var string
     */
    private $act;
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     * 
     * @var boolean
     */
    private $isolation;
    
    /**
     * @ORM\Column(type="inet", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Ip()
     * 
     * @var string
     */
    private $ip;
    
    /**
     * @ORM\Column(name="FolderAvailability", type="text", nullable=false)
     * @Assert\Choice(choices = {"keep", "readonly", "replace"}, message = "Choose a valid folder availability.")
     *
     * @var string
     */
    private $folderAvailability;
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->title;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set host
     *
     * @param string $ip
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
     * @return string
     */
    public function getIp()
    {
        return $this->$ip;
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

    /**
     * Set act
     *
     * @param string $act
     *
     * @return FileDistribution
     */
    public function setAct($act)
    {
        $this->act = $act;

        return $this;
    }

    /**
     * Get act
     *
     * @return string
     */
    public function getAct()
    {
        return $this->act;
    }

    /**
     * Set isolation
     *
     * @param boolean $isolation
     *
     * @return FileDistribution
     */
    public function setIsolation($isolation)
    {
        $this->isolation = $isolation;

        return $this;
    }

    /**
     * Get isolation
     *
     * @return boolean
     */
    public function getIsolation()
    {
        return $this->isolation;
    }

    /**
     * Set folderAvailability
     *
     * @param string $folderAvailability
     *
     * @return FileDistribution
     */
    public function setFolderAvailability($folderAvailability)
    {
        $this->folderAvailability = $folderAvailability;

        return $this;
    }

    /**
     * Get folderAvailability
     *
     * @return string
     */
    public function getFolderAvailability()
    {
        return $this->folderAvailability;
    }

    /**
     * Set host
     *
     * @param \Stsbl\FileDistributionBundle\Entity\Host $host
     *
     * @return FileDistribution
     */
    public function setHost(\Stsbl\FileDistributionBundle\Entity\Host $host = null)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get host
     *
     * @return \Stsbl\FileDistributionBundle\Entity\Host
     */
    public function getHost()
    {
        return $this->host;
    }
}
