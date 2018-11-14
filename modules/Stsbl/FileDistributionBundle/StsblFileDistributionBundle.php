<?php
// src/Stsbl/FileDistributionBundle/StsblFileDistributionBundle.php
namespace Stsbl\FileDistributionBundle;

use IServ\CoreBundle\Routing\AutoloadRoutingBundleInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Stsbl\FileDistributionBundle\DependencyInjection\StsblFileDistributionExtension;

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT License <https://opensource.org/licenses/MIT>
 */
class StsblFileDistributionBundle extends Bundle implements AutoloadRoutingBundleInterface
{
    public function getContainerExtension()
    {
        return new StsblFileDistributionExtension();
    }
}
