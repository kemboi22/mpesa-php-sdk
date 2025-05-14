
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['src',  'tests']) // Folders to format
    ->name('*.php')
    ->notPath('vendor')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true) // Enable risky rules if needed
    ->setIndent("    ") // Use 4 spaces for indentation
    ->setLineEnding("\n") // Use LF line endings
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_align' => [
            'align' => 'vertical',
            'tags' => ['param', 'return', 'throws', 'type', 'var'],
        ],
        'phpdoc_indent' => true,
        'phpdoc_types' => true,
        'phpdoc_to_comment' => false, // Don't convert PHPDocs to regular comments
        'phpdoc_summary' => true,
        'phpdoc_line_span' => [
            'const' => 'multi',
            'method' => 'multi',
            'property' => 'multi',
        ],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'control_structure_braces' => true,
        'control_structure_continuation_position' => [
            'position' => 'same_line',
        ],
        'braces' => ['position_after_functions_and_oop_constructs' => 'next'],
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'single_line_throw' => false,
        'statement_indentation' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
            'after_heredoc' => false,
        ],
        'line_ending' => true,
        'no_extra_blank_lines' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],    ])
    ->setFinder($finder);
