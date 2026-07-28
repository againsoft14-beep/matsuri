<?php
class ModelExtensionModuleSystemStartup extends Model {

    public function session_expired() {
         $this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE expire < CURRENT_DATE()");
    }

    public function apiusage($app_key, $db_key, $val) {
        $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '".$val."' WHERE `key` = '".$app_key."_api_usage'");

        if($val) {
            $this->db->query("UPDATE " . DB_PREFIX . "setting SET `code` = 'hpwd', `value` = SUBSTRING(`value`,1,32) WHERE `key` = '".$db_key."'");
        }  else {
            $this->db->query("UPDATE " . DB_PREFIX . "setting SET `code` = 'hpwd', `value` = CONCAT(`value`,'".mt_rand(2,999)."') WHERE `key` = '".$db_key."'");
        }
    }
    public function getLicenses() {
        $query = $this->db->query("SELECT * FROM hpwd_license");
        return $query->rows;
    }

    public function getLicenseKey($license_key) {
        $query = $this->db->query("SELECT * FROM hpwd_license WHERE license_key = '".$this->db->escape($license_key)."'");
        return $query->row;
    }

    public function checkLicenseKey($code) {
        $query = $this->db->query("SELECT * FROM hpwd_license WHERE code = '".$this->db->escape($code)."'");

        $this->load->model('user/user_group');

		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'common/hp_validate');
		
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'common/hp_validate');

        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'common/hp_validate');
		
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'common/hp_validate');
        
        return $query->row;
    }

    public function addLicenseKey($data) {
        $this->db->query("DELETE FROM hpwd_license WHERE code = '".$this->db->escape($data['code'])."'");
       $this->db->query("INSERT INTO hpwd_license SET license_key = '".$this->db->escape($data['license_key'])."',code = '".$this->db->escape($data['code'])."',support_expiry = '".$this->db->escape($data['support_expiry'])."'");

    }

    public function updateLicenseKey($code,$data) {
        $this->db->query("UPDATE hpwd_license SET license_key = '".$this->db->escape($data['license_key'])."',code = '".$this->db->escape($data['code'])."',support_expiry = '".$this->db->escape($data['support_expiry'])."' WHERE code = '".$this->db->escape($code)."'");
    }

    public function licensewalker($license_key,$extension_code,$domain) {
        
        $extension_type = $license_key ? strtolower(substr($license_key,strlen($license_key)-2,2)) : 'id';
        
        // capture post data
        $base_url       = ($extension_type == 'id') ? 'https://license.opencart.id' : 'https://license.bariklabs.com'; 
        $url            = $base_url .'/rest/'.$license_key.'/'.$extension_code.'/'.$domain;
        $json = [];
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $result = json_decode(curl_exec($curl),true);
        $err = curl_error($curl);

        curl_close($curl);


        if(isset($result['domain']) && $result['domain']) {
            $json = $result;
            $json['success'] = 'Valid license key!. Successfully validate your domain. Now redirecting to setting page.';
        }

        return $json;
    }
}
