<?php
// src/Stsbl/FileDistributionBundle/Entity/Specification/FileDistributionSpecification.php
namespace Stsbl\FileDistributionBundle\Entity\Specification;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use IServ\CrudBundle\Doctrine\Specification\AbstractSpecification;

/*
 * The MIT License
 *
 * Copyright 2017 felix.
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
 * Specification to filter allowed file distribution hosts.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionSpecification extends AbstractSpecification
{
    /**
     * @var bool
     */
    private $invert;
    
    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * The constructor
     * 
     * @param bool $invert
     * @param EntityManager $em
     */
    public function __construct($invert, EntityManager $em) 
    {
        $this->invert = $invert;
        $this->em = $em;
    }
    
    /**
     * {@inheritdoc}
     */
    public function match(QueryBuilder $qb, $dqlAlias) {
        $subQb = $this->em->createQueryBuilder();           
        $subQb
            ->select('fr')
            ->from('StsblFileDistributionBundle:FileDistributionRoom', 'fr')
            ->where('fr.room = '.$dqlAlias.'.room')
        ;        
            
        if (true === $this->invert) {
            return $qb->expr()->andX($qb->expr()->not($qb->expr()->exists($subQb)), $dqlAlias.'.controllable = true');
        } else {
            return $qb->expr()->andX($qb->expr()->exists($subQb), $dqlAlias.'.controllable = true');
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function supports($className) 
    {
        return 'Stsbl\FileDistributionBundle\Entity\Host' === $className;
    }

}
