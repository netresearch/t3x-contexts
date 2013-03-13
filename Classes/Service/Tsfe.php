<?php
class Tx_Contexts_Service_Tsfe
{
    /**
     * Initialize the frontend user - contexts are initialized here.
     *
     * @param tslib_fe $pObj Calling object
     *
     * @return void
     */
    public function initFEuser($pObj)
    {
        Tx_Contexts_Context_Container::get()->initMatching();
    }
}