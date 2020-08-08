<?php

namespace App\DependencyInjection\Compiler;


use App\Service\Scraper\ScraperChainService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScraperCompilerPass implements CompilerPassInterface
{

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has(ScraperChainService::class)) {
            return;
        }

        $definition = $container->findDefinition(ScraperChainService::class);

        // find all service IDs with the app.scrapers tag
        $taggedServices = $container->findTaggedServiceIds('app.scrapers');

        foreach ($taggedServices as $id => $tags) {
            // a service could have the same tag twice
            foreach ($tags as $attributes) {
                // add the scraper service to the ScraperChain service
                $definition->addMethodCall(
                    'addScraper',
                    [
                        new Reference($id),
                        $attributes['alias']
                    ]
                );
            }
        }
    }
}