<?php

$finder = (new PhpCsFixer\Finder())
    ->in('src/')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP8x4Migration' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        '@Symfony' => true,
    ])
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setFinder($finder)
    ;
