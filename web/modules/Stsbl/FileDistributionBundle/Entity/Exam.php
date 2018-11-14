<?php
// src/Stsbl/FileDistributionBundle/Entity/Exam.php
namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CoreBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

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
 * Temporary entity for exam mode, so that we can detect if a computer is in exam mode
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity(repositoryClass="ExamRepository")
 * @ORM\Table(name="exam")
 * @DoctrineAssert\UniqueEntity("ip", message="The computer is already in exam mode.")
 */
class Exam implements CrudInterface
{
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
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Regex("/^([ !#&()*+,\-.0-9:<=>?\@A-Z\[\]^_a-z{|}~$])/", message="The title contains invalid characters.")
     *
     * @var string
     */
    private $title;
    
    /**
     * DO NOT ADD ANY REFERENCES to Host here, because Symfony do not like it!
     * 
     * //@ORM\OneToOne(targetEntity="\IServ\HostBundle\Entity\Host", fetch="EAGER")
     * //@ORM\JoinColumn(name="ip", referencedColumnName="ip", onDelete="CASCADE")
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Ip()
     * @ORM\Id
     * 
     * @var string
     */
    private $ip;

    /**
     * {@inheritdoc}
     */
    public function __toString() 
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId() 
    {
        return $this->ip;
    }


    /**
     * Set act
     *
     * @param string $act
     *
     * @return Exam
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
     * Set title
     *
     * @param string $title
     *
     * @return Exam
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
        return $this->title;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return Exam
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
        return $this->ip;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Exam
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
