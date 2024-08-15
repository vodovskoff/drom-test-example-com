<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setCacheFile("var/cache/.php-cs-fixer.cache")
    ->setFinder($finder)
;
