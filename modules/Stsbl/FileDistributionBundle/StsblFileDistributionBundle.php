<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle;

use IServ\CoreBundle\Routing\AutoloadRoutingBundleInterface;
use Stsbl\FileDistributionBundle\DependencyInjection\StsblFileDistributionExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT License <https://opensource.org/licenses/MIT>
 */
final class StsblFileDistributionBundle extends Bundle implements AutoloadRoutingBundleInterface
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new StsblFileDistributionExtension();
    }
}
