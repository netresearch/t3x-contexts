<?php
/**
 * Loads contexts and provides access to them
 */
class Tx_Contexts_Context_Container extends ArrayObject
{
    /**
     * @var Tx_Contexts_Context_Container
     */
    protected static $instance;



    /**
     * Singleton accessor
     *
     * @return Tx_Contexts_Context_Container
     */
    public static function get()
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * Loads all contexts and checks if they match
     *
     * @return Tx_Contexts_Context_Container
     */
    public function initMatching()
    {
        $this->setActive($this->match($this->loadAvailable()));
        return $this;
    }

    /**
     * Loads all contexts.
     *
     * @return Tx_Contexts_Context_Container
     */
    public function initAll()
    {
        $this->setActive($this->loadAvailable());
        return $this;
    }

    /**
     * Make the given contexts active (available in this container)
     *
     * @param array $arContexts Array of context objects
     *
     * @return Tx_Contexts_Context_Container
     */
    protected function setActive($arContexts)
    {
        $this->exchangeArray($arContexts);
        $aliases = array();
        foreach ($arContexts as $context) {
            $aliases[] = $context->getAlias();
        }
        t3lib_div::devLog(
            count($this) . ' active contexts: ' . implode(', ', $aliases),
            'tx_contexts', 0
        );

        return $this;
    }

    /**
     * Loads all available contexts from database and instantiates them
     * and checks if they match.
     *
     * @return array Array of available Tx_Contexts_Context_Abstract objects,
     *               key is their uid
     */
    protected function loadAvailable()
    {
        $arRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*', 'tx_contexts_contexts', 'deleted=0'
        );

        $contexts = array();
        foreach ($arRows as $arRow) {
            $context = Tx_Contexts_Context_Factory::createFromDb($arRow);
            if ($context !== null) {
                $contexts[$arRow['uid']] = $context;
            }
        }

        return $contexts;
    }

    /**
     * Matches all context objects. Resolves dependencies.
     *
     * @param array $arContexts Array of available context objects
     *
     * @return array Array of matched Tx_Contexts_Context_Abstract objects,
     *               key is their uid
     */
    protected function match($arContexts)
    {
        $matched          = array();
        $notMatched       = array();
        $arContextsHelper = $arContexts;

        $loops = 0;
        do {
            foreach (array_keys($arContexts) as $uid) {
                /* @var $context Tx_Contexts_Context_Abstract */
                $context = $arContexts[$uid];

                if ($context->getDisabled()) {
                    continue;
                }

                // resolve dependencies
                $arDeps = $context->getDependencies($arContextsHelper);
                $unresolvedDeps = count($arDeps);
                foreach ($arDeps as $depUid => $enabled) {
                    if ($enabled) {
                        if (isset($matched[$depUid])) {
                            $arDeps[$depUid] = (object) array(
                                'context' => $matched[$depUid],
                                'matched' => true
                            );
                            $unresolvedDeps--;
                        } elseif (isset($notMatched[$depUid])) {
                            $arDeps[$depUid] = (object) array(
                                'context' => $notMatched[$depUid],
                                'matched' => false
                            );
                            $unresolvedDeps--;
                        }
                    } else {
                        $arDeps[$depUid] = (object) array(
                            'context' => $arContextsHelper[$depUid],
                            'matched' => 'disabled'
                        );
                        $unresolvedDeps--;
                    }
                    // FIXME: what happens when dependency context is not
                    // available at all (e.g. deleted)?
                }
                if ($unresolvedDeps > 0) {
                    // not all dependencies available yet, so skip this
                    // one for now
                    continue;
                }

                if ($context->match($arDeps)) {
                    $matched[$uid] = $context;
                } else {
                    $notMatched[$uid] = $context;
                }
                unset($arContexts[$uid]);
            }
        } while (count($arContexts) > 0 && ++$loops < 10);

        return $matched;
    }

    /**
     * Find context by uid or alias
     *
     * @param int|string $uidOrAlias
     *
     * @return Tx_Contexts_Context_Abstract
     */
    public function find($uidOrAlias)
    {
        if (is_numeric($uidOrAlias) && isset($this[$uidOrAlias])) {
            return $this[$uidOrAlias];
        }

        foreach ($this as $context) {
            if ($context->getAlias() === $uidOrAlias
                || $context->getUid() == $uidOrAlias
            ) {
                return $context;
            }
        }

        return null;
    }
}
?>
