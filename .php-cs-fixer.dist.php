<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PHP7x4Migration' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try', 'if', 'switch'],
        ],
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'none'],
        'echo_tag_syntax' => [
            'format' => 'short',
            'shorten_simple_statements_only' => false, // чтобы точно конвертил
        ],
        'empty_loop_body' => ['style' => 'braces'],
        'empty_loop_condition' => true,
        'function_declaration' => true,
        'function_to_constant' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'include' => true,
        'increment_style' => ['style' => 'post'],
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'operator_linebreak' => [
            'position' => 'beginning', // или 'end'
            'only_booleans' => true,
        ],
        'modernize_types_casting' => false,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'native_constant_invocation' => false,
        'native_function_casing' => true,
        'new_with_parentheses' => true,
        'no_alternative_syntax' => true,
        'no_binary_string' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => false,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'break',
                'continue',
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_braces' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_indent' => false,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => false,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => false,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => false,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'short_scalar_cast' => true,
        'blank_lines_before_namespace' => false,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'single_line_throw' => false,
        'single_quote' => true,
        'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],
        'standardize_not_equals' => true,
        'ternary_to_elvis_operator' => false,
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'array_destructuring', 'arrays']],
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'yoda_style' => false,
        'no_null_property_initialization' => false,
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    );
