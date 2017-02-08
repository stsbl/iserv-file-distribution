<?php
// src/Stsbl/FileDistributionBundle/Form/DataTransformer/FileDistributionTransformer.php
namespace Stsbl\FileDistributionBundle\Form\DataTransformer;

use IServ\CoreBundle\Security\Core\SecurityHandler;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
 * Adds required values to FileDistribution Entity
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionTransformer implements DataTransformerInterface 
{
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * The constructor
     * 
     * @param SecurityHandler $securityHandler
     */
    public function __construct(SecurityHandler $securityHandler = null) 
    {
        if (is_null($securityHandler)) {
            throw new \InvalidArgumentException('securityHandler is empty. Did you forget to pass it to the constructor?');
        }
        
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * Adds required attribute to FileDistribution Entity
     * 
     * @param \Stsbl\FileDistributionBundle\Entity\FileDistribution $value
     * @return \Stsbl\FileDistributionBundle\Entity\FileDistribution
     */
    public function reverseTransform($value) 
    {
        try {
            $host = $value->getHostname();
            
            if (is_null($host)) {
                $value->setIp(null);
            } else {
                $value->setIp($host->getIp());
            }
            
            $value->setUser($this->securityHandler->getUser());
            
            return $value;
        } catch (\Exception $e) {
            throw new TransformationFailedException();
        }
    }
    
    /**
     * No transformation required - just pass
     * 
     * @param \Stsbl\FileDistributionBundle\Entity\FileDistribution $value
     */
    public function transform($value) 
    {
        return $value;
    }

}
