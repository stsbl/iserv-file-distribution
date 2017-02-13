<?php

namespace Stsbl\FileDistributionBundle\Entity;

use IServ\HostBundle\Entity\HostRepository as BaseHostRepository;

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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class HostRepository extends BaseHostRepository
{
    public function getDecoratorColumns() 
    {
        $ret = parent::getDecoratorColumns();
        
        $ret['fileDistributionOwner'] = '(SELECT CONCAT(u2.firstname, \' \', u2.lastname) FROM IServCoreBundle:User u2 WHERE u2.username = (SELECT f2.act FROM StsblFileDistributionBundle:FileDistribution f2 WHERE f2.hostname = parent.name))';
        $ret['fileDistribution'] = '(SELECT f.title FROM StsblFileDistributionBundle:FileDistribution f WHERE f.hostname = parent.name)';
        $ret['sambaUserDisplay'] = '(SELECT CONCAT(u3.firstname, \' \', u3.lastname) FROM IServCoreBundle:User u3 WHERE u3.username = (SELECT MAX(s2.act) FROM IServHostBundle:SambaUser s2 WHERE s2.ip = parent.ip AND s2.since = (SELECT MAX(v2.since) FROM IServHostBundle:SambaUser v2)))';
        return $ret;
    }
}
