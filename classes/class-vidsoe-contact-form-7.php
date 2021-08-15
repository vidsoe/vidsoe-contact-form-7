<?php

if(!class_exists('Vidsoe_Contact_Form_7')){
    final class Vidsoe_Contact_Form_7 {

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// private
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private $additional_data = [], $data_options = '', $file = '', $meta_data = [], $posted_data = [];

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    	private function __clone(){}

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    	private function __construct($file = ''){
            $this->file = $file;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// private static
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private static $instance = null;

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// private
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function filter_data_options($data_options = []){
			$options = [];
        	foreach($data_options as $option){
                if(strpos($option, 'v.') !== 0){
                    continue;
                }
                $option = explode('.', $option, 2);
                $option = array_filter($option);
				if(isset($option[1])){
                    $options[] = $option[1];
				}
			}
			return $options;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function fix_data_option($value, $value_orig, $tag){
            $data = $tag->get_data_option();
            if(!$data){
                return $value;
            }
            if(!v()->is_array_assoc($data)){
                return $value;
            }
            $data_flip = array_flip($data);
            if(is_array($value)){
                $label = [];
                foreach($value as $key => $v){
                    $label[$key] = $v;
                    if(isset($data_flip[$v])){
                        $value[$key] = $data_flip[$v];
                    }
                }
            } else {
                $label = $value;
                if(isset($data_flip[$value])){
                    $value = $data_flip[$value];
                }
            }
            $this->additional_data[$tag->name . '_label'] = $label;
            return $value;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function fix_free_text($value, $value_orig, $tag){
            if(!$tag->has_option('free_text')){
                return $value;
            }
            $value = (array) $value;
            $value_orig = (array) $value_orig;
            $last_val = array_pop($value);
            list($tied_item) = array_slice(WPCF7_USE_PIPE ? $tag->pipes->collect_afters() : $tag->values, -1, 1);
            $tied_item = html_entity_decode($tied_item, ENT_QUOTES, 'UTF-8');
            if(0 === strpos($last_val, $tied_item)){
                $value[] = $tied_item;
                $this->additional_data[$tag->name . '_free_text'] = $this->free_text($tag->name);
                $this->additional_data[$tag->name . '_with_free_text'] = $last_val;
            } else {
                $value[] = $last_val;
            }
            return $value;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function fix_pipes($value, $value_orig, $tag){
            if(WPCF7_USE_PIPE and $tag->pipes instanceof WPCF7_Pipes and !$tag->pipes->zero()){
                $this->additional_data[$tag->name . '_label'] = $value_orig;
            }
            return $value;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function free_text($name = ''){
            $name .= '_free_text';
            return $this->get_posted_data($name);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function sanitize_posted_data($value){
            if(is_array($value)){
    			$value = array_map([$this, 'sanitize_posted_data'], $value);
    		} elseif(is_string($value)){
    			$value = wp_check_invalid_utf8($value);
    			$value = wp_kses_no_null($value);
    		}
    		return $value;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function setup_meta_data($contact_form = null, $submission = null){
            if(null === $contact_form){
                $contact_form = wpcf7_get_current_contact_form();
            }
            if(null === $contact_form){
                return [];
            }
            if(null === $submission){
                $submission = WPCF7_Submission::get_instance();
            }
            if(null === $submission){
                return [];
            }
            $meta_data = [
                'contact_form_id' => $contact_form->id(),
                'contact_form_locale' => $contact_form->locale(),
                'contact_form_name' => $contact_form->name(),
                'contact_form_title' => $contact_form->title(),
                'container_post_id' => $submission->get_meta('container_post_id'),
                'current_user_id' => $submission->get_meta('current_user_id'),
                'remote_ip' => $submission->get_meta('remote_ip'),
                'remote_port' => $submission->get_meta('remote_port'),
                'submission_response' => $submission->get_response(),
                'submission_status' => $submission->get_status(),
                'timestamp' => $submission->get_meta('timestamp'),
                'unit_tag' => $submission->get_meta('unit_tag'),
                'url' => $submission->get_meta('url'),
                'user_agent' => $submission->get_meta('user_agent'),
            ];
            $this->meta_data = $meta_data;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function setup_posted_data(){
            $this->posted_data = $this->sanitize_posted_data($_POST);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// public
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function add_data_option($option = '', $data = []){
            if(!wpcf7_is_name($option) or !is_array($data)){
                return false;
            }
            $this->data_options[$option] = $data;
            return true;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function fix($methods = []){
            v()->call_prefixed_methods($this, $methods, 'fix_');
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function fix_ip_addr(){
            v()->one('wpcf7_remote_ip_addr', [$this, 'wpcf7_remote_ip_addr']);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function fix_posted_data(){
            v()->one('wpcf7_posted_data', [$this, 'wpcf7_posted_data']);
            v()->one('wpcf7_posted_data_checkbox', [$this, 'wpcf7_posted_data_fix'], 10, 3);
            v()->one('wpcf7_posted_data_checkbox*', [$this, 'wpcf7_posted_data_fix'], 10, 3);
            v()->one('wpcf7_posted_data_radio', [$this, 'wpcf7_posted_data_fix'], 10, 3);
            v()->one('wpcf7_posted_data_radio*', [$this, 'wpcf7_posted_data_fix'], 10, 3);
            v()->one('wpcf7_posted_data_select', [$this, 'wpcf7_posted_data_fix'], 10, 3);
            v()->one('wpcf7_posted_data_select*', [$this, 'wpcf7_posted_data_fix'], 10, 3);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function get_meta_data($name = ''){
            if(!$this->meta_data){
                $this->setup_meta_data();
            }
            if('' === $name){
                return $this->meta_data;
            }
            if(!array_key_exists($name, $this->meta_data)){
                return '';
            }
            return $this->meta_data[$name];
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        /*public function get_post($post = null){
            if(is_array($post)){
                $args = array_merge($post, [
                    'posts_per_page' => 1,
                ]);
                $posts = get_posts($args);
                if($posts){
                    return $posts[0];
                } else {
                    return null;
                }
            } elseif(is_string($post) and 1 === preg_match('/^[a-z0-9]{13}$/', $post)){
                return $this->get_post([
                    'meta_key' => '_uniqid',
                    'meta_value' => $post,
                    'post_status' => 'any',
                ]);
            } else {
                return get_post($post);
            }
        }*/

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function get_posted_data($name = ''){
            if(!$this->posted_data){
                $this->setup_posted_data();
            }
            if('' === $name){
                return $this->posted_data;
            }
            if(!array_key_exists($name, $this->posted_data)){
                return '';
            }
            return $this->posted_data[$name];
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        /*public function get_user($user = null){
            if(is_array($user)){
                $args = array_merge($user, [
                    'posts_per_page' => 1,
                ]);
                $users = get_users($args);
                if($users){
                    return $users[0];
                } else {
                    return null;
                }
            } elseif(is_string($user) and 1 === preg_match('/^[a-z0-9]{13}$/', $user)){
                return $this->get_user([
                    'meta_key' => '_uniqid',
                    'meta_value' => $user,
                    'post_status' => 'any',
                ]);
            } else {
                return get_user($user);
            }
        }*/

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function mail($contact_form = null){
            if(null === $contact_form){
                $contact_form = wpcf7_get_current_contact_form();
            }
            if(null === $contact_form){
                return false;
            }
            $skip_mail = $this->skip_mail($contact_form);
            if($skip_mail){
            	return true;
            }
            $result = WPCF7_Mail::send($contact_form->prop('mail'), 'mail');
            if(!$result){
                return false;
            }
            $additional_mail = [];
        	if($mail_2 = $contact_form->prop('mail_2') and $mail_2['active']){
        		$additional_mail['mail_2'] = $mail_2;
        	}
        	$additional_mail = apply_filters('wpcf7_additional_mail', $additional_mail, $contact_form);
        	foreach($additional_mail as $name => $template){
        		WPCF7_Mail::send($template, $name);
        	}
        	return true;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function pre_delete_post($delete, $post, $force_delete){
            if('wpcf7_contact_form' !== $post->post_type){
                return $delete;
            }
            return false;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function prevent($methods = []){
            v()->call_prefixed_methods($this, $methods, 'prevent_');
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function prevent_accidental_delete(){
            v()->one('pre_delete_post', [$this, 'pre_delete_post'], 10, 3);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function prevent_autop(){
            v()->one('wpcf7_autop_or_not', [$this, 'wpcf7_autop_or_not']);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function save($meta_type = '', $object_id = 0, $contact_form = null, $submission = null){
            if(null === $contact_form){
                $contact_form = wpcf7_get_current_contact_form();
            }
            if(null === $contact_form){
                return false;
            }
            if(null === $submission){
                $submission = WPCF7_Submission::get_instance();
            }
            if(null === $submission){
                return false;
            }
            if(!in_array($meta_type, ['post', 'user'])){
                return false;
            }
            if(0 === $object_id){
                return false;
            }
            if('post' === $meta_type){
                $the_post = wp_is_post_revision($object_id);
                if($the_post){
                    $object_id = $the_post; // Make sure meta is added to the post, not a revision.
                }
            }
            $meta_data = $this->get_meta_data();
            $meta_data = apply_filters('v_cf7_meta_data', $meta_data, $meta_type, $object_id);
            if($meta_data){
                foreach($meta_data as $key => $value){
                    $key = '_' . $key;
                    add_metadata($meta_type, $object_id, $key, $value, true);
                }
            }
            $posted_data = $submission->get_posted_data();
            $posted_data = apply_filters('v_cf7_posted_data', $posted_data, $meta_type, $object_id);
            if($posted_data){
                foreach($posted_data as $key => $value){
                    if(is_array($value)){
                        delete_metadata($meta_type, $object_id, $key);
                        foreach($value as $single){
                            add_metadata($meta_type, $object_id, $key, $single);
                        }
                    } else {
                        update_metadata($meta_type, $object_id, $key, $value);
                    }
                }
            }
            $uploaded_files = $submission->uploaded_files();
            $uploaded_files = apply_filters('v_cf7_uploaded_files', $uploaded_files, $meta_type, $object_id);
            if($uploaded_files){
                foreach($uploaded_files as $key => $value){
                    delete_metadata($meta_type, $object_id, $key . '_attachment_id');
                    delete_metadata($meta_type, $object_id, $key . '_filename');
                    foreach((array) $value as $single){
                        if('post' === $meta_type){
                            $post_id = $object_id;
                        } else {
                            $post_id = 0;
                        }
                        $attachment_id = v()->upload_file($single, $post_id);
                        if(is_wp_error($attachment_id)){
                            add_metadata($meta_type, $object_id, $key . '_attachment_id', 0);
                            add_metadata($meta_type, $object_id, $key . '_filename', $attachment_id->get_error_message());
                            do_action('v_cf7_attachment_error', $attachment_id, $meta_type, $object_id);
                        } else {
                            add_metadata($meta_type, $object_id, $key . '_attachment_id', $attachment_id);
                            add_metadata($meta_type, $object_id, $key . '_filename', wp_basename($single));
                            do_action('v_cf7_add_attachment', $attachment_id, $meta_type, $object_id);
                        }
                    }
                }
            }
            do_action("v_cf7_save_{$contact_form->id()}", $object_id, $meta_type);
            do_action("v_cf7_save_{$meta_type}", $object_id, $contact_form->id());
            return true;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function skip_mail($contact_form = null){
            if(null === $contact_form){
                $contact_form = wpcf7_get_current_contact_form();
            }
            if(null === $contact_form){
                return false;
            }
            $skip_mail = ($contact_form->in_demo_mode() or $contact_form->is_true('skip_mail') or !empty($contact_form->skip_mail));
            $skip_mail = apply_filters('wpcf7_skip_mail', $skip_mail, $contact_form);
            return $skip_mail;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function shortcode_atts_wpcf7($out, $pairs, $atts){
            if(isset($_SERVER['HTTP_CF_IPCOUNTRY'])){
                $out['country_code'] = $_SERVER['HTTP_CF_IPCOUNTRY'];
            }
            return $out;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function support($methods = []){
            v()->call_prefixed_methods($this, $methods, 'support_');
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function support_country_code(){
            v()->one('shortcode_atts_wpcf7', [$this, 'shortcode_atts_wpcf7'], 10, 3);
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function support_data_option(){
            v()->one('wpcf7_form_tag_data_option', [$this, 'wpcf7_form_tag_data_option'], 10, 3);
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function support_logged_in_user(){
            v()->one('wpcf7_verify_nonce', 'is_user_logged_in');
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function support_shortcode(){
            v()->one('wpcf7_form_elements', 'do_shortcode');
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_autop_or_not($autop){
            $contact_form = wpcf7_get_current_contact_form();
            if(null === $contact_form){
                return $autop;
            }
            return $contact_form->is_true('autop');
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_form_tag_data_option($data, $options, $args){
            if($this->data_options){
                $options = $this->filter_data_options($options);
    			if($options){
    				$data = (array) $data;
    				foreach($options as $option){
                        if(array_key_exists($option, $this->data_options)){
                            $data = array_merge($data, $this->data_options[$option]);
                        }
    				}
    			}
            }
			return $data;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_posted_data($posted_data){
            if($this->additional_data){
                $posted_data = array_merge($posted_data, $this->additional_data);
            }
            return $posted_data;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_posted_data_fix($value, $value_orig, $tag){
            $value = $this->fix_data_option($value, $value_orig, $tag);
            $value = $this->fix_free_text($value, $value_orig, $tag);
            $value = $this->fix_pipes($value, $value_orig, $tag);
            return $value;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_remote_ip_addr($ip_addr){
            if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
                $ip_addr = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
            return $ip_addr;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// public static
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public static function get_instance($file = ''){
            if(null !== self::$instance){
                return self::$instance;
            }
            if('' === $file){
                wp_die(__('File doesn&#8217;t exist?'));
            }
            if(!is_file($file)){
                wp_die(sprintf(__('File &#8220;%s&#8221; doesn&#8217;t exist?'), $file));
            }
            self::$instance = new self($file);
            return self::$instance;
    	}

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// useful methods
    	//
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        //
        // fix_ip_addr
        // fix_posted_data
        // prevent_accidental_delete
        // prevent_autop
        // support_country_code
        // support_data_option
        // support_logged_in_user
        // support_shortcode
        //
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    }
}
