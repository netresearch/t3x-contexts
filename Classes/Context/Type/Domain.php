<?php

/**
 * Matches on the current domain name
 */
class Tx_Contexts_Context_Type_Domain extends Tx_Contexts_Context_Abstract
{
    public function match(array $arDependencies = array())
    {
        $curHost = $_SERVER['HTTP_HOST'];
        $arDomains = explode("\n", $this->getConfValue('field_domains'));

        foreach ($arDomains as $domain) {
            if ($this->matchDomain($domain, $curHost)) {
                return $this->invert(true);
            }
        }

        return $this->invert(false);
    }

    protected function matchDomain($domain, $curHost)
    {
        if ($domain{0} != '.') {
            if ($domain == $curHost) {
                return true;
            }
            return false;
        }

        if (substr($domain, 1) == $curHost
            || substr($curHost, -strlen($domain) + 1) == substr($domain, 1)
        ) {
            return true;
        }

        return false;
    }
}
?>
