<?php

/**
 * PHP-CS-Fixer configuration for TYPO3 extension.
 *
 * @see https://cs.symfony.com/doc/rules/index.html
 * @see https://github.com/TYPO3/coding-standards
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/..')
    ->exclude([
        '.Build',
        '.ddev',
        'Build',
        'public',
        'vendor',
    ])
    ->notPath([
        'ext_emconf.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // PSR-12 coding standard
        '@PSR12' => true,

        // PHP 8.2+ features
        '@PHP82Migration' => true,
        '@PHP80Migration:risky' => true,

        // Strict typing
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,

        // Array syntax
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        'no_whitespace_before_comma_in_array' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        // Class and method ordering
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'ordered_interfaces' => true,
        'single_class_element_per_statement' => true,

        // Imports
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],

        // Type declarations
        'fully_qualified_strict_types' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => true,
        ],
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],

        // Spacing and formatting
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'single_blank_line_at_eof' => true,

        // Control structures
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'yoda_style' => false,

        // Comments
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'single_line_comment_spacing' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],

        // Semicolons
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_empty_statement' => true,
        'semicolon_after_instruction' => true,

        // Strings
        'explicit_string_variable' => true,
        'simple_to_complex_string_variable' => true,
        'single_quote' => true,

        // Return statements
        'no_useless_return' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'simplified_null_return' => true,

        // Native functions
        'native_constant_invocation' => true,
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced',
            'strict' => true,
        ],

        // PHPUnit specific
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => ['target' => 'newest'],
        'php_unit_expectation' => ['target' => 'newest'],
        'php_unit_fqcn_annotation' => true,
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_mock' => ['target' => 'newest'],
        'php_unit_mock_short_will_return' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => true,
        'php_unit_test_annotation' => ['style' => 'annotation'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    ])
    ->setFinder($finder);
