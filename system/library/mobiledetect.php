<?php
class Mobiledetect {
      
    private $mobileAgents = array('android', 'avantgo', 'blackberry', 'bolt', 'boost', 'cricket', 'docomo', 'fone', 'hiptop', 'mini', 'mobi', 'palm', 'phone', 'pie', 'tablet', 'up\.browser', 'up\.link', 'webos', 'wos');
    public function isMobileDevice() {
        $is_mobile = false;
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $is_mobile = preg_match("/(".implode("|", $this->mobileAgents).")/i", $_SERVER["HTTP_USER_AGENT"]);
            if (!$is_mobile) {
                if(stripos($_SERVER['HTTP_USER_AGENT'],"mobile") && stripos($_SERVER['HTTP_USER_AGENT'],"Android") ){
                    $is_mobile = true;
                }
            }
        }
       
        return $is_mobile;
    }
    
}