<?php

/**
 * DSpace Repository Plugin
 *
 * @copyright  2010 Enovation Solutions
 * @author     Enovation Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This code was developed by Enovation Solutions on behalf of the Irish National Digital Learning Resource (NDLR) service (www.ndlr.ie)
 * The NDLR  is funded by the Irish Higher Education Authority and is a collaborative service that provides a platform and support for
 * the development and sharing of reusable digital learning objects.
 *
 *
 */
class repository_dspace extends repository {

    public $response;

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $USER, $DB;
        parent::__construct($repositoryid, $context, $options);
        $this->dspace_url = $this->get_option('dspace_url');
        $this->username = $this->get_option('dspace_username');
        $this->password = $this->get_option('dspace_password');
    }

    public static function get_type_option_names() {
        $option_names = array('dspace_url', 'pluginname', 'dspace_password', 'dspace_username');

        return $option_names;
    }

    public function type_config_form($mform) {
        //    global $CFG;
        parent::type_config_form($mform);
        $dspace_url = get_config('dspace', 'dspace_url');
        $dspace_username = get_config('dspace', 'dspace_username');
        $dspace_password = get_config('dspace', 'dspace_password');

        $strrequired = get_string('required');

        $mform->addElement('text', 'dspace_url', get_string('dspaceurl', 'repository_dspace'), array('value' => $dspace_url, 'size' => '50'));

        $mform->addRule('dspace_url', $strrequired, 'required', null, 'client');
        $str_getkey = get_string('dspaceurlinfo', 'repository_dspace');
        $mform->addElement('static', null, '', $str_getkey);

        $mform->addElement('text', 'dspace_username', get_string('dspaceusername', 'repository_dspace'), array('value' => $dspace_username, 'size' => '50'));
        $mform->addElement('password', 'dspace_password', get_string('dspacepassword', 'repository_dspace'), array('value' => $dspace_password, 'size' => '50'));
    
        $mform->addRule('dspace_username', $strrequired, 'required', null, 'client');
        $mform->addRule('dspace_password', $strrequired, 'required', null, 'client');

    }

    public function match_rights( $metadata = false ) {
        global $DB;

        if ($this->dspace_usemapping) {
            return false;
        }
        if (!$metadata) return false;

        if ( preg_match( '/name="DC.relation" content="([^"]+)" \/>/i', $metadata, $matches ) ) {
            return true;
        }
        return false;
    }

    public function check_login() {
        return true;
    }

    public function get_listing($id) {
        $ret = array();
        $ret['nologin'] = true;
        $ret['list'] = array();
        $ret['dynload']=true;
        return $ret;
    }

    public function search($query) {
        global $OUTPUT;
        $ret = array();
        $ret['nologin'] = true;
        $ret['list'] = array();
        $url = $this->dspace_url . '/search.xml?query=' . urlencode($query).'&user='.$this->username.'&pass='.$this->password;
        $sac_curl = curl_init();
        curl_setopt($sac_curl, CURLOPT_HTTPGET, true);
        curl_setopt($sac_curl, CURLOPT_URL, $url);
        curl_setopt($sac_curl, CURLOPT_VERBOSE, true);
        curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($sac_curl, CURLOPT_HEADER, false);
        $resp = curl_exec($sac_curl);
        @curl_close($sac_curl);

        $xml = new SimpleXMLElement($resp);
        $search_collection = (string) $xml->Message->search_collection;
        foreach ($xml->search as $search) {
            if ($search->name != '') {
                $bitS = Array();
                $haslicense = $this->match_rights( (string)$search->metadata );
                foreach($search->bitstreams->bitstreamentity as $bs){
                    $bitstream['title']=((string)$bs->name);
                    $bitstream['source'] = ((string)$this->dspace_url . '/bitstream/'.$bs->id.'/receive.xml?user='.$this->username.'&pass='.$this->password);
                    if ( $haslicense ) {
                        $bitstream['haslicense'] = true;
                    }
                    $mimetype = (string)$bs->mimeType;
                    switch($mimetype){
                        case 'application/msword':
                            $bitstream['thumbnail'] = $OUTPUT->pix_url(file_extension_icon('docx-32.png'))->out(false);
                            break;
                        case 'application/pdf':
                            $bitstream['thumbnail'] = $OUTPUT->pix_url(file_extension_icon('pdf.gif'))->out(false);
                            break;
                        case 'application/vnd.ms-powerpoint':
                            $bitstream['thumbnail'] = $OUTPUT->pix_url(file_extension_icon('powerpoint-32.png'))->out(false);
                            break;
                        case 'video/mpeg':
                        case 'video/vnd.vivo':
                        case 'video/quicktime':
                        case 'video/x-msvideo':
                        case 'application/octet-stream':
                            $bitstream['thumbnail'] = $OUTPUT->pix_url(file_extension_icon('video-32.png'))->out(false);
                            break;
                        default:
                            $bitstream['thumbnail'] = $OUTPUT->pix_url(file_extension_icon('odp.gif'))->out(false);

                    }
                    array_push($bitS,$bitstream);
                }
                $ret['list'][] = array(
                    'title' => ((string) $search->name),
                    'haslicense' => $haslicense,
                    'size' => '654654',
                    'thumbnail' => $OUTPUT->pix_url('f/zip-32')->out(false),
                    'source' => 'searchfile1.txt', 'children' =>$bitS);
            }
        }
        return $ret;
    }

    public function global_search() {
        return true;
    }

    public function logout() {
        return false;
    }

    public function get_name() {
        return get_string('pluginname', 'repository_dspace');
    }

    public function supported_filetypes() {
        return '*';
    }

    public function set_option($options = array()) {
        if (!empty($options['dspace_url'])) {
            set_config('dspace_url', trim($options['dspace_url']), 'dspace');
        }
        if (!empty($options['dspace_username'])) {
            set_config('dspace_username', trim($options['dspace_username']), 'dspace');
        }
        if (!empty($options['dspace_password'])) {
            set_config('dspace_password', trim($options['dspace_password']), 'dspace');
        }

        unset($options['dspace_url']);
        unset($options['dspace_username']);
        unset($options['dspace_password']);
        $ret = parent::set_option($options);
        return $ret;
    }

    /**
     *
     * @param string $config
     * @return mixed
     */
    public function get_option($config = '') {
        if (preg_match('/^dspace_/', $config)) {
            return trim(get_config('dspace', $config));
        }

        $options = parent::get_option($config);
        return $options;
    }

    public function restCall($url){
        $sac_curl = curl_init();
        
        curl_setopt($sac_curl, CURLOPT_URL, $url);
        curl_setopt($sac_curl, CURLOPT_VERBOSE, true);
        curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($sac_curl, CURLOPT_HEADER, false);
        $resp = curl_exec($sac_curl);
        @curl_close($sac_curl);
        return $resp;
    }
}

