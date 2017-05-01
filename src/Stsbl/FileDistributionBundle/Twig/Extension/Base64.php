<?php
// src/Stsbl/FileDistributionBundle/Twig/Extension/Base64.php
namespace Stsbl\FileDistributionBundle\Twig\Extension;

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
 * Simple base64 filters for twig
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIR license <https://opensource.org/licenses/MIT>
 * @link https://gist.github.com/zburgermeiszter/afb9cb14b9a6f3f023e1 original source
 */
class Base64 extends \Twig_Extension 
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('base64_encode', 'base64_encode'),
            new \Twig_SimpleFilter('base64_decode', 'base64_decode')
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "base64";
    }
}