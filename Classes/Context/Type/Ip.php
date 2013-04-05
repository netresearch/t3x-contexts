<?php

/**
 * Matches on the current ip
 */
class Tx_Contexts_Context_Type_Ip extends Tx_Contexts_Context_Abstract
{
    public function match(array $arDependencies = array())
    {
       $strCurIp = $_SERVER['REMOTE_ADDR'];

       $arIpRange = explode("\n", $this->getConfValue('field_ip'));

       if (count($arIpRange)) {
           $strRange = implode(',', $arIpRange);
           return $this->isIpInRange($strCurIp, $strRange);
       }

       return false;

    }
    /**
     * Check if the remote ip in range
     * switch IPv4 and IPv6
     *
     * @param string $strIp   remote ip number
     * @param string $arRange defined range
     * @return boolean
     */
    protected function isIpInRange($strIp, $arRange)
    {

        if(filter_var($strIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return t3lib_div::cmpIPv4($strIp, $arRange);

        }

        if(filter_var($strIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return t3lib_div::cmpIPv6($strIp, $arRange);

        }

        return false;
    }

}
?>
