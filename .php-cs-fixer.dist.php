<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'bootstrap',
        'storage',
        'vendor',
        'node_modules',
        'public',
        'database/migrations'
    ])
    ->ignoreDotFiles(false)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setFinder($finder)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP84Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => ['=>' => 'align'],
        ],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        'braces' => true,
        'cast_spaces' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'class_definition' => [
            'multi_line_extends_each_single_line' => true,
            'single_item_single_line' => true,
            'single_line' => true,
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'include' => true,
        'increment_style' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_cast' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_closing_tag' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
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
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => [
            'use' => 'echo',
        ],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => [
            'positions' => ['inside', 'outside'],
        ],
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'return_type_declaration' => true,
        'semicolon_after_instruction' => true,
        'short_scalar_cast' => true,
        'single_blank_line_before_namespace' => true,
        'single_class_element_per_statement' => [
            'elements' => ['property'],
        ],
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'single_quote' => true,
        'space_after_semicolon' => [
            'remove_in_empty_for_expressions' => true,
        ],
        'standardize_not_equals' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        // 新增的程式碼品質規則
        'strict_comparison' => true,              // 強制使用嚴格比較 (=== 而非 ==)
        'no_superfluous_phpdoc_tags' => [        // 移除多餘的 PHPDoc 標籤
            'allow_mixed' => true,
            'allow_unused_params' => false,
        ],
        'phpdoc_types_order' => [                // PHPDoc 類型排序
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],

        // 命名規範相關規則
        'class_reference_name_casing' => true,   // 確保類別引用使用正確大小寫
        'constant_case' => true,                 // 確保 PHP 常數使用正確大小寫
        'magic_constant_casing' => true,         // 魔術常數大小寫規範
        'magic_method_casing' => true,           // 魔術方法大小寫規範
        'native_function_casing' => true,        // PHP 內建函式大小寫規範

        // PSR 標準加強規則
        'psr_autoloading' => true,               // 確保 PSR-4 自動載入合規
        'no_unused_imports' => true,             // 移除未使用的 use 語句
        'ordered_imports' => [                   // Import 語句排序（已存在，確保配置）
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
