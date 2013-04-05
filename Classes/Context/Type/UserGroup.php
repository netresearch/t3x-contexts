<?php

/**
 * 
 */
class Tx_Contexts_Context_Type_UserGroup extends Tx_Contexts_Context_Abstract
{
    public function match(array $arDependencies = array())
    {
        $strAllowedUserGroup = $this->getConfValue('field_usergroup');
        
        if ($strAllowedUserGroup) {
           $arCurrentFeGroupIds 
               = $this->getCurrentFeGroupUids();
           
           $arAllowedFeUserGroup = explode(',', $strAllowedUserGroup);
           
           foreach ($arAllowedFeUserGroup as $allowedGroup) {
               if (in_array($allowedGroup, $arCurrentFeGroupIds)) {
                   return true;
               }
           }
           
        }
       
        return false;
        

    }
    
    /**
     * Get group uids from current fe user
     * 
     * @return array group uids from current fe user
     */
    protected function getCurrentFeGroupUids()
    {
        //NO group data available because the fe user is not initialized
        global $TSFE;
        $arData = $TSFE->fe_user->groupData;
        return $arData['uid'];
    }

}
?>
