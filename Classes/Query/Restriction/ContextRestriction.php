<?php
namespace Netresearch\Contexts\Query\Restriction;

use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;


class ContextRestriction implements QueryRestrictionInterface, EnforceableQueryRestrictionInterface
{
    /**
     * @return bool
     */
    public function isEnforced(): bool
    {
        return true;
    }

    /**
     * @param array $queriedTables
     * @param ExpressionBuilder $expressionBuilder
     * @return CompositeExpression
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {

        $constraints = [];

        if ($this->isEnvironmentInFrontendMode()) {
            foreach ($queriedTables as $table) {
                foreach (Configuration::getEnableSettings($table) as $setting) {
                    $flatColumns = Configuration::getFlatColumns($table, $setting);
                    if (!$flatColumns) {
                        continue;
                    }

                    $enableConstraints = [
                        $expressionBuilder->isNull($flatColumns[1]),
                        $expressionBuilder->eq(
                            $flatColumns[1],
                            $expressionBuilder->literal('')
                        )
                    ];

                    $disableConstraints = [];

                    foreach (Container::get() as $context) {

                        $enableConstraints[] = $expressionBuilder->inSet(
                            $flatColumns[1],
                            (int)$context->getUid()
                        );

                        $disableConstraints[] = 'NOT ' . $expressionBuilder->inSet(
                                $flatColumns[0],
                                (int)$context->getUid()
                            );
                    }

                    $constraints[] = $expressionBuilder->orX(
                        ...$enableConstraints
                    );

                    if (count($disableConstraints)) {
                        $constraints[] = $expressionBuilder->orX(
                            $expressionBuilder->isNull($flatColumns[0]),
                            $expressionBuilder->eq(
                                $flatColumns[0],
                                $expressionBuilder->literal('')
                            ),
                            $expressionBuilder->andX(...$disableConstraints)
                        );
                    }
                    #DebuggerUtility::var_dump($table);
                    #DebuggerUtility::var_dump($constraints);
                    #throw new \Exception('foo', 1);
                }
            }
        }

        return $expressionBuilder->andX(...$constraints);
    }

    /**
     * @return bool
     */
    protected function isEnvironmentInFrontendMode()
    {
        return (defined('TYPO3_MODE') && TYPO3_MODE === 'FE') ?: false;
    }
}
