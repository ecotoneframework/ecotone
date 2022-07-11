<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/packages'
    ])
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PHP80Migration' => true,
    '@PSR12' => true,
    '@PSR12:risky' => true,
    'array_indentation' => true,
    'align_multiline_comment' => true,
    'fully_qualified_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ],
    'no_empty_phpdoc' => true,
    'no_useless_return' => true,
    'ordered_imports' => true,
    'php_unit_internal_class' => true,
    'php_unit_method_casing' => [
        'case' => 'snake_case'
    ],
    'not_operator_with_successor_space' => true,
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_unused_imports' => true,
    'no_useless_nullsafe_operator' => true,
    'single_quote' => true,
    'simplified_null_return' => true,
    'trailing_comma_in_multiline' => true,
    'whitespace_after_comma_in_array' => true,
])->setFinder($finder);