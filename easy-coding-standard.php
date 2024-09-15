<?php
declare(strict_types=1);

use PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\ConstantNotation\NativeConstantInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\FopenFlagsFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\NullableTypeDeclarationForDefaultNullValueFixer;
use PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\LanguageConstruct\ExplicitIndirectVariableFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitConstructFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDedicateAssertFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDedicateAssertInternalTypeFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMockFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMockShortWillReturnFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\CompactNullableTypehintFixer;
use PhpCsFixerCustomFixers\Fixer\NoImportFromGlobalNamespaceFixer;
use PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessParenthesisFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessStrlenFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer;
use PhpCsFixerCustomFixers\Fixer\SingleSpaceAfterStatementFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->dynamicSets([
        '@Symfony',
        '@Symfony:risky',
    ]);

    $ecsConfig->sets([
        SetList::ARRAY,
        SetList::CONTROL_STRUCTURES,
        SetList::STRICT,
        SetList::PSR_12,
    ]);

    $ecsConfig->rule(ModernizeTypesCastingFixer::class);
    $ecsConfig->ruleWithConfiguration(ClassAttributesSeparationFixer::class, ['elements' => ['property' => 'one', 'method' => 'one']]);
    $ecsConfig->rule(FopenFlagsFixer::class);
    $ecsConfig->ruleWithConfiguration(MethodArgumentSpaceFixer::class, ['on_multiline' => 'ensure_fully_multiline']);
    $ecsConfig->ruleWithConfiguration(NativeFunctionInvocationFixer::class, [
        'include' => [NativeFunctionInvocationFixer::SET_COMPILER_OPTIMIZED],
        'scope' => 'namespaced',
        'strict' => false,
    ]);
    $ecsConfig->rule(NativeConstantInvocationFixer::class);
    $ecsConfig->rule(NullableTypeDeclarationForDefaultNullValueFixer::class);
    $ecsConfig->rule(VoidReturnFixer::class);
    $ecsConfig->ruleWithConfiguration(ConcatSpaceFixer::class, ['spacing' => 'one']);

    $ecsConfig->rule(OperatorLinebreakFixer::class);
    $ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, ['annotations' => ['copyright', 'category']]);
    $ecsConfig->ruleWithConfiguration(NoSuperfluousPhpdocTagsFixer::class, ['allow_unused_params' => true]);
    $ecsConfig->rule(PhpdocLineSpanFixer::class);
    $ecsConfig->rule(PhpdocOrderFixer::class);
    $ecsConfig->rule(PhpUnitConstructFixer::class);
    $ecsConfig->ruleWithConfiguration(PhpUnitDedicateAssertFixer::class, ['target' => 'newest']);
    $ecsConfig->rule(PhpUnitDedicateAssertInternalTypeFixer::class);
    $ecsConfig->rule(PhpUnitMockFixer::class);
    $ecsConfig->rule(PhpUnitMockShortWillReturnFixer::class);
    $ecsConfig->rule(PhpUnitTestCaseStaticMethodCallsFixer::class);
    $ecsConfig->rule(NoUselessReturnFixer::class);
    $ecsConfig->rule(DeclareStrictTypesFixer::class);
    $ecsConfig->rule(BlankLineBeforeStatementFixer::class);
    $ecsConfig->rule(CompactNullableTypehintFixer::class);
    $ecsConfig->rule(NoImportFromGlobalNamespaceFixer::class);
    $ecsConfig->rule(NoSuperfluousConcatenationFixer::class);
    $ecsConfig->rule(NoUselessCommentFixer::class);
    $ecsConfig->rule(PhpdocNoIncorrectVarAnnotationFixer::class);
    $ecsConfig->rule(SingleSpaceAfterStatementFixer::class);
    $ecsConfig->rule(NoUselessParenthesisFixer::class);
    $ecsConfig->rule(NoUselessStrlenFixer::class);

    $ecsConfig->paths([
        'src',
        'easy-coding-standard.php',
    ]);

    $ecsConfig->skip([
        ArrayOpenerAndCloserNewlineFixer::class => null,
        ArrayListItemNewlineFixer::class => null,
        SingleLineThrowFixer::class => null,
        SelfAccessorFixer::class => null,
        ExplicitIndirectVariableFixer::class => null,
        BlankLineAfterOpeningTagFixer::class => null,
        PhpdocSummaryFixer::class => null,
        ExplicitStringVariableFixer::class => null,
        StandaloneLineInMultilineArrayFixer::class => null,
        NoSuperfluousPhpdocTagsFixer::class => [
            __DIR__ . '/.gitlab-ci/tools/src/Service/ProcessBuilder.php',
        ],
        '**/node_modules',
    ]);
};
