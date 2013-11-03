<?php
require_once __DIR__ . '/../../../../t3lib/class.t3lib_div.php';

if (!defined('TYPO3_MODE')) {
    define('TYPO3_MODE', 'BE');
}

if (!class_exists('t3lib_div')
    && class_exists('TYPO3\CMS\Core\Utility\GeneralUtility')
) {
    class t3lib_div extends TYPO3\CMS\Core\Utility\GeneralUtility
    {
    }
}

abstract class TestBase extends PHPUnit_Framework_TestCase
{
    /**
     * Temp copy of TYPO3_CONF_VARS
     * @var array
     */
    protected static $confVars;
    
    /**
     * Override TYPO3_CONF_VARS
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        
        if (array_key_exists('TYPO3_CONF_VARS', $GLOBALS)) {
            self::$confVars = $GLOBALS['TYPO3_CONF_VARS'];
        }
        
        $GLOBALS['TYPO3_CONF_VARS'] = array(
            'SYS' => array(
                'enableDeprecationLog' => false,
            ),
            'SC_OPTIONS' => array(
                't3lib/class.t3lib_div.php' => array(
                    'devLog' => null
                )
            )
        );
    }
    
    /**
     * Restore TYPO3_CONF_VARS
     */
    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
        $GLOBALS['TYPO3_CONF_VARS'] = self::$confVars;
    }


    /**
     * Make a proteced/private method accessible.
     *
     * @param string $strClass      Class name
     * @param string $strMethodName Method name
     *
     * @return ReflectionMethod
     */
    public function getAccessibleMethod($strClass, $strMethodName)
    {
        $reflectedClass = new ReflectionClass($strClass);

        /* @var $method ReflectionMethod */
        $method = $reflectedClass->getMethod($strMethodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Call a protected method on an object and return the result
     *
     * @param object $obj           Object the method should be called on
     * @param string $strMethodName Method to be called
     * @param mixed  $param1        First method parameter
     * @param mixed  $param2        Second method parameter
     * @param mixed  ...            ...
     *
     * @return mixed Whatever the method returns
     */
    public function callProtected($obj, $strMethodName)
    {
        $params = func_get_args();
        array_shift($params);
        array_shift($params);
        $m = $this->getAccessibleMethod(get_class($obj), $strMethodName);
        return $m->invokeArgs($obj, $params);
    }
}
?>
