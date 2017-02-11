<?php

namespace Stsbl\FileDistributionBundle\Entity;

use IServ\HostBundle\Entity\HostRepository as BaseHostRepository;
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
 * Description of HostRepository
 *
 * @author felix
 */
class HostRepository extends BaseHostRepository
{
    public function getDecoratorColumns() 
    {
        $ret = parent::getDecoratorColumns();
        
        $ret['fileDistributionOwner'] = '(SELECT CONCAT(u2.firstname, \' \', u2.lastname) FROM IServCoreBundle:User u2 WHERE u2.username IN (SELECT f2.act FROM StsblFileDistributionBundle:FileDistribution f2 WHERE f2.hostname = parent.name))';
        $ret['fileDistribution'] = '(SELECT f.title FROM StsblFileDistributionBundle:FileDistribution f WHERE f.hostname = parent.name)';
        return $ret;
    }
}
