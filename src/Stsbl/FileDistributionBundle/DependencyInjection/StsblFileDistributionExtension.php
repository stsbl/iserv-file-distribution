<?php
// src/Stsbl/FileDistributionBundle/DependencyInjection/StsblFileDistributionExtension.php
namespace Stsbl\FileDistributionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 * 
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource,org/licenses/MIT>
 */
class StsblFileDistributionExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->getAlias());
        $this->processConfiguration($configuration, $configs);
        
        $loader = new Loader\YamlFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'stsbl_file_distribution';
    }
}
