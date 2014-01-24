<?php
class PHPPI
{
	var $settings;
	var $vars;
	
	function showSetup($step) {
		$this->vars['tests'] = $this->reqCheck();
		require('pages/setup_' . $step . '.php');
		
		//$this->outputVarsArray();
	}
	
	function showAdmin() {
		if ($_SERVER['QUERY_STRING'] == "" || $_SERVER['QUERY_STRING'] == "status") {
			require('pages/admin_status.php');
		} else if ($_SERVER['QUERY_STRING'] == "settings") {
			if (isset($_POST['save_settings'])) {
				if ($this->checkErrors()) {
					if ($this->saveSettings()) { 
						$this->loadSettings();
						$status = "Settings saved successfully";
					} else {
						$status = "Settings failed to save, check your file permissions";
					}
				} else {
					if (!$this->importSettings()) { $this->showError("Unable to import settings, your old or current settings file may contain errors. Remove the file and try again."); exit; }
					$status = "Some fields did not validate, check for any errors and try again";
				}
			}
			
			require('pages/admin_settings.php');
		}
		
		//$this->outputVarsArray();
	}
	
	function showLogin($error = "") {
		require('pages/login.php');	
	}
	
	function showError($error = "") {
		require('pages/error.php');	
	}
	
	function outputChecks() {
		echo "<div style=\"border-bottom: 1px solid #eeeeee; margin-bottom: 30px;\">\n";
		foreach($this->vars['tests'] as $key=>$value) {
			echo "<div class=\"test-container\">\n";
			echo "<div class=\"" . (($value[0] == true) ? 'pass' : 'fail') . "\"></div>\n";
			echo "<div class=\"test-content\">" . $value[1] . "</div>\n";
			echo "<div style=\"clear: both;\"></div>\n";
			echo "</div>";
		}
		echo "</div>\n";
	}
	
	function reqCheck() {
		//Checks compatibility
		
		$tests = array();
		
		//PHPPI folder is writable
		if (is_writeable($this->vars['dir']['local'])) {
			$tests['WRITE_PHPPI_FOLDER'] = array(true, '<b>' . $this->vars['dir']['local'] . '</b> is writable');
		} else {
			$tests['WRITE_PHPPI_FOLDER'] = array(false, '<b>' . $this->vars['dir']['local'] . '</b> is not writable');
		}
		
		//GD v2 is installed
		if (extension_loaded('gd')) {
			if (function_exists('gd_info')) {
				$gd = gd_info();
				preg_match('/\d/', $gd['GD Version'], $m);
				$version = $m[0];
				if ($version == 2) {
					$tests['GD_INSTALLED'] = array(true, '<b>GD Version 2.0</b> is installed');
					
					//GD GIF support
					$gd = gd_info();
					if ($gd['GIF Read Support'] == true && $gd['GIF Create Support'] == true) {
						$tests['GD_GIF_SUPPORT'] = array(true, 'Your GD install has <b>GIF support</b>');	
					} else {
						$tests['GD_GIF_SUPPORT'] = array(false, 'Your GD install is missing <b>GIF support</b>');
					}
					
					//GD JPEG support
					$gd = gd_info();
					if ($gd['JPEG Support'] == true) {
						$tests['GD_JPEG_SUPPORT'] = array(true, 'Your GD install has <b>JPEG support</b>');	
					} else {
						$tests['GD_JPEG_SUPPORT'] = array(false, 'Your GD install is missing <b>JPEG support</b>');
					}
					
					//GD PNG support
					$gd = gd_info();
					if ($gd['PNG Support'] == true) {
						$tests['GD_PNG_SUPPORT'] = array(true, 'Your GD install has <b>PNG support</b>');	
					} else {
						$tests['GD_PNG_SUPPORT'] = array(false, 'Your GD install is missing <b>PNG support</b>');
					}
				} else {
					$tests['GD_INSTALLED'] = array(false, '<b>GD</b> is installed but isn\'t version 2.0');	
				}
			} else {
				$tests['GD_INSTALLED'] = array(false, '<b>GD Version 2.0</b> is not installed or your PHP version is less than 4.3.0');	
			}
		} else {
			$tests['GD_INSTALLED'] = array(false, '<b>GD Version 2.0</b> is not installed');	
		}
		
		return $tests;
	}
	
	function getURL($query = true)
	{
		//Original - http://snipplr.com/view.php?codeview&id=2734
		
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		
		if ($query == false) {
			$temp_uri = substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['REQUEST_URI']) - strlen($_SERVER['QUERY_STRING']) - 1);
		} else {
			$temp_uri = $_SERVER['REQUEST_URI'];
		}
		
		return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $temp_uri;
	}
	
	function getBool($value) {
		if (is_bool($value)) {
			switch($value) {
				case true: return 'true';
				case false: return 'false';
			}
		} else {
			return $value;
		}
	}
	
	function isInt($int){
    	if (is_numeric($int) === true) {
			if((int)$int == $int){
				return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
	
	function checkUpdate() {
		if ($update_file = @file_get_contents("http://phppi.pixelizm.com/update.txt")) {
			$update = explode(",", $update_file);
			if ($update[0] > $this->vars['installed_version']) {
				return $update;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function checkErrors() {
		if ($_POST['site_name'] == "") { $this->vars['errors']['site_name'] = "Field is empty"; }
		if ($_POST['theme'] == "") { $this->vars['errors']['theme'] = "No theme selected"; } else {
			if (!is_dir("../themes/gallery/" . $_POST['theme'])) { $this->vars['errors']['theme'] = "Theme does not exist"; }
		}
		if ($_POST['thumbnail_theme'] == "") { $this->vars['errors']['thumbnail_theme'] = "No theme selected"; } else {
			if (!is_dir("../themes/thumbnail/" . $_POST['thumbnail_theme'])) { $this->vars['errors']['thumbnail_theme'] = "Theme does not exist"; }
		}
		if ($_POST['admin_password'] == "") { $this->vars['errors']['admin_password'] = "Field is empty"; }
		if ($_POST['thumb_size_small'] == "") { $this->vars['errors']['thumb_size_small'] = "Field is empty"; }
		if ($_POST['thumb_size_medium'] == "") { $this->vars['errors']['thumb_size_medium'] = "Field is empty"; }
		if ($_POST['thumb_size_large'] == "") { $this->vars['errors']['thumb_size_large'] = "Field is empty"; }
		if ($_POST['use_popup_image_viewer'] == "true" && $_POST['popup_thumb_size'] == "") { $this->vars['errors']['popup_thumb_size'] = "Field is empty"; }
		if ($_POST['access_log'] == "on" && $_POST['log_timezone'] == "") { $this->vars['errors']['log_timezone'] = "Field is empty"; }
		if ($_POST['gzip_compression_level'] == "") { $this->vars['errors']['gzip_compression_level'] = "Field is empty"; } else {
			if (!$this->isInt($_POST['gzip_compression_level'])) { $this->vars['errors']['gzip_compression_level'] = "Value must be a number"; }
			if ($_POST['gzip_compression_level'] < 0 || $_POST['gzip_compression_level'] > 9) { $this->vars['errors']['gzip_compression_level'] = "Value must be 0 to 9"; }
		}
		if ($_POST['jpeg_quality'] == "") { $this->vars['errors']['jpeg_quality'] = "Field is empty"; } else {
			if (!$this->isInt($_POST['jpeg_quality'])) { $this->vars['errors']['jpeg_quality'] = "Value must be a number"; }
			if ($_POST['jpeg_quality'] < 0 || $_POST['jpeg_quality'] > 100) { $this->vars['errors']['jpeg_quality'] = "Value must be 0 to 100"; }
		}
		if ($_POST['gd_cache_expire'] == "") { $this->vars['errors']['gd_cache_expire'] = "Field is empty"; } else {
			if (!$this->isInt($_POST['gd_cache_expire'])) { $this->vars['errors']['gd_cache_expire'] = "Value must be a number"; }
		}
		if ($_POST['expire_file_cache'] == "") { $this->vars['errors']['expire_file_cache'] = "Field is empty"; } else {
			if (!$this->isInt($_POST['expire_file_cache'])) { $this->vars['errors']['expire_file_cache'] = "Value must be a number"; }
		}
		if ($_POST['thumb_size_large'] == "") { $this->vars['errors']['thumb_size_large'] = "Field is empty"; }
		if ($_POST['thumb_size_large'] == "") { $this->vars['errors']['thumb_size_large'] = "Field is empty"; }
		if ($_POST['use_gd'] == "true") { 
			if ($_POST['cache_folder'] == "") { $this->vars['errors']['cache_folder'] = "Field is empty"; }
		} else {
			if ($_POST['thumbs_folder'] == "") { $this->vars['errors']['thumbs_folder'] = "Field is empty"; }
		}
		if ($_POST['log_timezone'] == "") { $this->vars['errors']['log_timezone'] = "Field is empty"; }
		if ($_POST['gallery_folder'] == "") { $this->vars['errors']['gallery_folder'] = "Field is empty"; } else {
			if (substr($_POST['gallery_folder'], 0, 1) == '/') {
				if (!is_dir($_POST['gallery_folder'])) { $this->vars['errors']['gallery_folder'] = "Folder does not exist"; }
			} else {
				if (!is_dir("../../" . $_POST['gallery_folder'])) { $this->vars['errors']['gallery_folder'] = "Folder does not exist"; }
			}
		}
		
		if (isset($this->vars['errors'])) { return false; } else { return true; }
	}
	
	function importSettings() {		
		if (!isset($this->vars['errors'])) {
			//Defaults
			
			$this->settings['site_name'] = "My Gallery";
			$this->settings['site_notice'] = "";
			$this->settings['page_title_show_full_path'] = true;
			$this->settings['page_title_logo'] = "";
			$this->settings['nav_menu_style'] = "auto";
			$this->settings['theme'] = "pix";
			$this->settings['thumbnail_theme'] = "pix";
			$this->settings['admin_password'] = "";
			$this->settings['thumb_size_small'] = 125;
			$this->settings['thumb_size_medium'] = 175;
			$this->settings['thumb_size_large'] = 225;
			$this->settings['thumb_size_default'] = "medium";
			$this->settings['enable_thumb_size_change'] = false;
			$this->settings['thumb_file_ext'] = "jpg";
			$this->settings['thumb_folder_show_thumbs'] = true;
			$this->settings['thumb_folder_shuffle'] = true;
			$this->settings['thumb_folder_use_cache_only'] = false;
			$this->settings['thumb_use_jpeg_for_png'] = true;
			$this->settings['image_show_title_on_hover'] = true;
			$this->settings['folder_show_title_on_hover'] = false;
			$this->settings['use_popup_image_viewer'] = false;
			$this->settings['disable_popup_image_viewer_for_mobile'] = true;
			$this->settings['show_thumbs_under_viewer'] = false;
			$this->settings['popup_thumb_size'] = 100;
			$this->settings['enable_mousewheel'] = false;
			$this->settings['nextprev_image_animation'] = "none";
			$this->settings['open_image_animation'] = "fade";
			$this->settings['close_image_animation'] = "fade";
			$this->settings['enable_hotkeys'] = true;
			$this->settings['enable_up_hotkey'] = false;
			$this->settings['enable_click_next'] = true;
			$this->settings['debug_mode'] = false;
			$this->settings['debug_show_vars'] = false;
			$this->settings['debug_show_settings'] = false;
			$this->settings['access_log'] = "off";
			$this->settings['access_log_no_thumbnail'] = true;
			$this->settings['log_timezone'] = "Australia/Sydney";
			$this->settings['cyrillic_support'] = true;
			$this->settings['allow_mobile_theme'] = true;
			$this->settings['use_gzip_compression'] = "on";
			$this->settings['gzip_compression_level'] = 1;
			$this->settings['php_memory'] = 100;
			$this->settings['use_gd'] = true;
			$this->settings['use_gd_cache'] = true;
			$this->settings['jpeg_quality'] = 75;
			$this->settings['gd_cache_expire'] = 172800;
			$this->settings['use_file_cache'] = true;
			$this->settings['expire_file_cache'] = 86400;
			$this->settings['cache_folder'] = "phppi/cache";
			$this->settings['thumbs_folder'] = "phppi/thumbs";
			$this->settings['gallery_folder'] = substr($this->vars['dir']['local'], 0, -6) . "gallery";
			$this->settings['use_javascript_navigation'] = false;
			
			if ($this->vars['setup_mode'] == "legacy") {
				//Perform Legacy Upgrade (1.1.0 or 1.1.1)
				
				unset($this->settings['theme']);			
				if (!require('../settings.php')) { return false; }
				unset($this->settings['theme']);			
				
				$this->settings['site_name'] = $this->settings['general']['site_name'];
				$this->settings['site_notice'] = $this->settings['general']['site_notice'];
				$this->settings['page_title_show_full_path'] = $this->settings['general']['page_title_show_full_path'];
				$this->settings['theme'] = "pix";
				$this->settings['thumb_size_small'] = $this->settings['general']['thumb_size']['small'];
				$this->settings['thumb_size_medium'] = $this->settings['general']['thumb_size']['medium'];
				$this->settings['thumb_size_large'] = $this->settings['general']['thumb_size']['large'];
				$this->settings['thumb_size_default'] = $this->settings['general']['thumb_size_default'];
				$this->settings['enable_thumb_size_change'] = $this->settings['general']['enable_thumb_size_change'];
				$this->settings['thumb_file_ext'] = $this->settings['general']['thumb_file_ext'];
				$this->settings['thumb_folder_show_thumbs'] = $this->settings['general']['thumb_folder_show_thumbs'];
				$this->settings['thumb_folder_shuffle'] = $this->settings['general']['thumb_folder_shuffle'];
				$this->settings['thumb_folder_use_cache_only'] = $this->settings['general']['thumb_folder_use_cache_only'];
				$this->settings['image_show_title_on_hover'] = $this->settings['general']['image_show_title_on_hover'];
				$this->settings['folder_show_title_on_hover'] = $this->settings['general']['folder_show_title_on_hover'];
				$this->settings['use_popup_image_viewer'] = $this->settings['general']['use_popup_image_viewer'];
				$this->settings['disable_popup_image_viewer_for_mobile'] = $this->settings['general']['disable_popup_image_viewer_for_mobile'];
				$this->settings['show_thumbs_under_viewer'] = $this->settings['general']['show_thumbs_under_viewer'];
				$this->settings['popup_thumb_size'] = $this->settings['general']['popup_thumb_size'];
				$this->settings['enable_mousewheel'] = $this->settings['general']['enable_mousewheel'];
				$this->settings['nextprev_image_animation'] = $this->settings['general']['nextprev_image_animation'];
				$this->settings['open_image_animation'] = $this->settings['general']['open_image_animation'];
				$this->settings['close_image_animation'] = $this->settings['general']['close_image_animation'];
				$this->settings['enable_hotkeys'] = $this->settings['general']['enable_hotkeys'];
				$this->settings['enable_up_hotkey'] = $this->settings['general']['enable_up_hotkey'];
				$this->settings['enable_click_next'] = $this->settings['general']['enable_click_next'];
				$this->settings['debug_mode'] = $this->settings['advanced']['debug_mode'];
				$this->settings['access_log'] = $this->settings['advanced']['access_log'];
				$this->settings['access_log_no_thumbnail'] = $this->settings['advanced']['access_log_no_thumbnail'];
				$this->settings['log_timezone'] = $this->settings['advanced']['log_timezone'];
				$this->settings['cyrillic_support'] = $this->settings['advanced']['cyrillic_support'];
				$this->settings['allow_mobile_theme'] = $this->settings['advanced']['allow_mobile_theme'];
				$this->settings['use_gzip_compression'] = $this->settings['advanced']['use_gzip_compression'];
				$this->settings['gzip_compression_level'] = $this->settings['advanced']['gzip_compression_level'];
				$this->settings['use_gd'] = $this->settings['advanced']['use_gd'];
				$this->settings['use_gd_cache'] = $this->settings['advanced']['use_gd_cache'];
				$this->settings['jpeg_quality'] = $this->settings['advanced']['jpeg_quality'];
				$this->settings['gd_cache_expire'] = $this->settings['advanced']['gd_cache_expire'];
				$this->settings['use_file_cache'] = $this->settings['advanced']['use_file_cache'];
				$this->settings['expire_file_cache'] = $this->settings['advanced']['expire_file_cache'];
				$this->settings['cache_folder'] = $this->settings['advanced']['cache_folder'];
				$this->settings['thumbs_folder'] = $this->settings['advanced']['thumbs_folder'];
				$this->settings['gallery_folder'] = substr($this->vars['dir']['local'], 0, -6) . "gallery";
				$this->settings['use_javascript_navigation'] = $this->settings['advanced']['use_javascript_navigation'];			
				
				unset($this->settings['general']);
				unset($this->settings['advanced']);
				
				return true;
			} else if ($this->vars['setup_mode'] == "upgrade") {
				//Perform Upgrade
				
				if (!$this->loadSettings()) { return false; } else { return true; }
			}
			
			return true;
		} else {
			//Retrieve posted data
			
			foreach ($_POST as $key=>$value) {
				if ($key !== 'step_2' && $key !== 'step_3' && $key !== 'save_settings') {
					$this->settings[$key] = $value;
				}
			}
			
			return true;
		}
	}
	
	function loadSettings() {
		if (!is_file('../phppi_settings.php'))
		{
			return false;
		} else {
			require('../phppi_settings.php');
			return true;		
		}
    }
	
	function saveSettings() {
		unset($_POST['step_1']);
		unset($_POST['step_2']);
		unset($_POST['step_3']);
		unset($_POST['save_settings']);
		
		$output = "<?php\n";
		
		foreach ($_POST as $key=>$value) {
			if (substr($key, 0, 4) !== "var_") { 
				if ($key == "admin_password") { $value = hash("sha256", $value); }
				
				if ($_POST['var_' . $key] == "string") {
					$value = "\"" . $value . "\"";
				}
				
				$output .= '$this->settings[\'' . $key . '\'] = ' . $value . ';';
				$output .= "\n";
			}
		}
		
		$output .= "?>";
		
		if (is_writable($this->vars['dir']['local'])) {
			if ($fh = @fopen($this->vars['dir']['local'] . "phppi_settings.php", 'w')) {
				if (fwrite($fh, $output)) {
					fclose($fh);
					$this->vars['settings_output'] = "";
					if ($fh = @fopen($this->vars['dir']['local'] . "version.txt", 'w')) {
						fwrite($fh, $this->vars['version']);
						fclose($fh);
					}
					return true;
				} else {
					fclose($fh);
				}
			}
		}
		
		$this->vars['settings_output'] = $output;
		return false;
	}
	
	function loadVars() {
		$this->vars['dir']['local'] = substr(realpath(dirname($_SERVER['SCRIPT_FILENAME'])), 0, -5); //			/var/www/installdirectory/
	}
	
	function loadSettingsFields() {
		$this->vars['settings'] = array(
			"General" => array(
				"Site Name" => array(
					"id" => "site_name",
					"desc" => "Name you'd like to use for the site's title",
					"type" => "text",
					"var" => "string"
				),
				"Site Notice" => array(
					"id" => "site_notice",
					"desc" => "Display a message on all pages of the site",
					"type" => "text",
					"var" => "string"
				),
				"Site Logo" => array(
					"id" => "page_title_logo",
					"desc" => "URL of the image you'd like to use for the logo of the site",
					"type" => "text",
					"var" => "string"
				),
				"Page Title Full Path" => array(
					"id" => "page_title_show_full_path",
					"desc" => "Enable to show the full path of the folder/file your on in the title or disable to show only the current folder/file name",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Navigation Menu Style" => array(
					"id" => "nav_menu_style",
					"desc" => "Choose whether to use bread crumb style navigation, classic (previous button only) style or automatic which switches depending on screen size",
					"type" => "select",
					"data" => array(
						"Automatic" => "auto",
						"Breadcrumb" => "new",
						"Classic" => "classic"
					),
					"var" => "string"
				),
				"Theme" => array(
					"id" => "theme",
					"desc" => "Theme to use for the gallery (installed in phppi/themes/gallery)",
					"type" => "select",
					"data" => array(),
					"var" => "string"
				),
				"Thumbnail Theme" => array(
					"id" => "thumbnail_theme",
					"desc" => "Theme to use for thumbnails (installed in phppi/themes/thumbnail)",
					"type" => "select",
					"data" => array(),
					"var" => "string"
				),
				"Gallery Folder" => array(
					"id" => "gallery_folder",
					"desc" => "Local folder path that contains the images for your gallery",
					"type" => "text",
					"var" => "string"
				),
				"Administration Password" => array(
					"id" => "admin_password",
					"desc" => "Password you would like to use to access the admin section of PHPPI. Note: If you forget your password you will have to either manually edit the settings.xml file or remove it so that PHPPI can create a new settings file",
					"type" => "password",
					"var" => "string"
				)
			),		
			"Thumbnails" => array(
				"Small" => array(
					"id" => "thumb_size_small",
					"desc" => "Size in pixels of small thumbnails",
					"type" => "text",
					"var" => "integer"
				),
				"Medium" => array(
					"id" => "thumb_size_medium",
					"desc" => "Size in pixels of medium thumbnails",
					"type" => "text",
					"var" => "integer"
				),
				"Large" => array(
					"id" => "thumb_size_large",
					"desc" => "Size in pixels of large thumbnails",
					"type" => "text",
					"var" => "integer"
				),
				"Default Size" => array(
					"id" => "thumb_size_default",
					"desc" => "Size of thumbnails to use by default",
					"type" => "select",
					"data" => array(
						"Small" => "small",
						"Medium" => "medium",
						"Large" => "large"
					),
					"var" => "string"
				),
				"Size Changer" => array(
					"id" => "enable_thumb_size_change",
					"desc" => "Enable or disable thumbnail size changer so users can change between small, medium and large thumbnails",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"File Extension" => array(
					"id" => "thumb_file_ext",
					"desc" => "File extension to use for non-dynamic thumbnails (only used if GD thumbnails are disabled)",
					"type" => "text",
					"var" => "string"
				),
				"Folder Thumbnails" => array(
					"id" => "thumb_folder_show_thumbs",
					"desc" => "Show thumbnails from inside folders as the folder's picture",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Shuffle Folder Thumbnails" => array(
					"id" => "thumb_folder_shuffle",
					"desc" => "If folder thumbnails are enabled this will shuffle the images shown",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Use Cache for Folder Thumbnails" => array(
					"id" => "thumb_folder_use_cache_only",
					"desc" => "Only use cached images for folder thumbnails (improves performance)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"PNG to JPEG" => array(
					"id" => "thumb_use_jpeg_for_png",
					"desc" => "Create jpeg thumbnails for png files if using dynamic thumbnails (lowers file size)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Image Title on Hover" => array(
					"id" => "image_show_title_on_hover",
					"desc" => "Enable to show the title of the image on hover or disable to show all the time",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Folder Title on Hover" => array(
					"id" => "folder_show_title_on_hover",
					"desc" => "Enable to show the title of the folder on hover or disable to show all the time",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				)
			),
			"Pop-up Image Viewer" => array(
				"Enable Pop-up Image Viewer" => array(
					"id" => "use_popup_image_viewer",
					"desc" => "Enables Fancybox instead of built in viewer",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Disable for Mobile" => array(
					"id" => "disable_popup_image_viewer_for_mobile",
					"desc" => "Enable to turn use built in viewer instead when browsing on mobile devices",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Show Thumbnails" => array(
					"id" => "show_thumbs_under_viewer",
					"desc" => "Displays thumbnails below pop-up when viewing image (each thumbnail is loaded from the full image which could cause high bandwidth usage)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Thumbnail Size" => array(
					"id" => "popup_thumb_size",
					"desc" => "Size in pixels of thumbnails below pop-up",
					"type" => "text",
					"var" => "integer"
				),
				"Enable Mousewheel" => array(
					"id" => "enable_mousewheel",
					"desc" => "Allows use of mousewheel to scroll through images",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Next/Prev Image Animation" => array(
					"id" => "nextprev_image_animation",
					"desc" => "Animation to use when browsing through images in the pop-up viewer",
					"type" => "select",
					"data" => array(
						"Elastic" => "elastic",
						"Fade" => "fade",
						"None" => "none"
					),
					"var" => "string"
				),
				"Open Image Animation" => array(
					"id" => "open_image_animation",
					"desc" => "Animation to use when opening an image",
					"type" => "select",
					"data" => array(
						"Elastic" => "elastic",
						"Fade" => "fade",
						"None" => "none"
					),
					"var" => "string"
				),
				"Close Image Animation" => array(
					"id" => "close_image_animation",
					"desc" => "Animation to use when closing an image",
					"type" => "select",
					"data" => array(
						"Elastic" => "elastic",
						"Fade" => "fade",
						"None" => "none"
					),
					"var" => "string"
				)
			),
			"Built in Viewer" => array(
				"Enable Hotkeys" => array(
					"id" => "enable_hotkeys",
					"desc" => "Pressing left arrow on the keyboard will go to the previous image, right arrow will go to the next image",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Enable Up Hotkey" => array(
					"id" => "enable_up_hotkey",
					"desc" => "Pressing up arrow on the keyboard will go to the parent folder",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Enable Click Next" => array(
					"id" => "enable_click_next",
					"desc" => "Clicking the full view image will go to the next image",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Use Javascript Navigation" => array(
					"id" => "use_javascript_navigation",
					"desc" => "Uses javascript for navigation instead of loading a new page, can speed up browsing",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				)
			),
			"Advanced" => array(
				"Debug Mode" => array(
					"id" => "debug_mode",
					"desc" => "Shows PHP errors",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Debug Show Variables" => array(
					"id" => "debug_show_vars",
					"desc" => "Shows variables used by PHPPI",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Debug Show Settings" => array(
					"id" => "debug_show_settings",
					"desc" => "Shows settings used by PHPPI",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Access Log" => array(
					"id" => "access_log",
					"desc" => "Records ip addresses, times and actions of users accessing your gallery (stored in phppi/logs/access.log)",
					"type" => "select",
					"data" => array(
						"On" => "on",
						"Off" => "off"
					),
					"var" => "string"
				),
				"Access Log No Thumbnails" => array(
					"id" => "access_log_no_thumbnail",
					"desc" => "Enable to not record access to thumbnails (lessens size of log)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Log Timezone" => array(
					"id" => "log_timezone",
					"desc" => "Set timezone for logs (see <a href=\"http://www.php.net/manual/en/timezones.php\" target=\"_blank\">http://www.php.net/manual/en/timezones.php</a> for acceptable values)",
					"type" => "text",
					"var" => "string"
				),
				"Cyrillic Support" => array(
					"id" => "cyrillic_support",
					"desc" => "Enable support for symbols from other languages",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Allow Mobile Themes" => array(
					"id" => "allow_mobile_theme",
					"desc" => "Displays mobile variations of themes if supported by the theme and displayed on a mobile device",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"GZIP Compression" => array(
					"id" => "use_gzip_compression",
					"desc" => "Enables GZIP compression for pages displayed by PHPPI (can improve page loading speed)",
					"type" => "select",
					"data" => array(
						"On" => "on",
						"Off" => "off"
					),
					"var" => "string"
				),
				"GZIP Compression Level" => array(
					"id" => "gzip_compression_level",
					"desc" => "GZIP compression level, 0 to 9 (9 being the most compression, 1 is usually enough)",
					"type" => "text",
					"var" => "integer"
				),
				"PHP Memory Limit" => array(
					"id" => "php_memory",
					"desc" => "Set the maximum amount of memory that PHP can use (in MB). Note: 100MB is not uncommon for generating a thumbnail from a photo with a resolution of 4000x3000 or greater. Setting this too low can cause pictures to not display or display with corruption.",
					"type" => "text",
					"var" => "string"
				),
				"Dynamic Thumbnails" => array(
					"id" => "use_gd",
					"desc" => "Enables dynamic creation of thumbnails using GD version 2.0 (if installed)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Dynamic Thumbnail Cache" => array(
					"id" => "use_gd_cache",
					"desc" => "Cache dynamically created thumbnails for quicker future page loads (needs write access to cache folder)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"Thumbnail Cache Expiry" => array(
					"id" => "gd_cache_expire",
					"desc" => "Time in seconds before PHPPI creates a new copy of the thumbnail",
					"type" => "text",
					"var" => "integer"
				),
				"Thumbnail JPEG Quality" => array(
					"id" => "jpeg_quality",
					"desc" => "Quality of JPEGs created for dynamic thumbnails (0 to 100, higher value increases file size and quality)",
					"type" => "text",
					"var" => "integer"
				),
				"File Cache" => array(
					"id" => "use_file_cache",
					"desc" => "Cache folder/file lists for quicker future page loads (needs write access to cache folder)",
					"type" => "select",
					"data" => array(
						"Enabled" => "true",
						"Disabled" => "false"
					),
					"var" => "boolean"
				),
				"File Cache Expiry" => array(
					"id" => "expire_file_cache",
					"desc" => "Time in seconds before PHPPI creates a new copy of the folder/file list for the currently viewed folder",
					"type" => "text",
					"var" => "integer"
				),
				"Cache Folder" => array(
					"id" => "cache_folder",
					"desc" => "Local folder path to your cache directory (requires write access to work) (recommended to leave as default)",
					"type" => "text",
					"var" => "string"
				),
				"Thumbnail Folder" => array(
					"id" => "thumbs_folder",
					"desc" => "Local folder path to your thumbnails folder (this is not for dynamic thumbnails, and is only used if dynamic thumbnails are disabled)",
					"type" => "text",
					"var" => "string"
				)
			)
		);
		
		$this->populateThemes();
	}
	
	function populateThemes() {
		$gallery_dir = "../themes/gallery/";
		$thumb_dir = "../themes/thumbnail/";
		
		if (is_dir($gallery_dir)) {
			if ($dh = opendir($gallery_dir)) {
				while (($item = readdir($dh)) !== false) {
					if (filetype($gallery_dir . $item) == 'dir' && $item !== "." && $item !== "..")
					{						
						$temp_gallery[$item] = $item;
					}
				}
				closedir($dh);
				
				$this->vars['settings']['General']['Theme']['data'] = $temp_gallery;
			} else {
				$this->vars['settings']['General']['Theme']['data'] = array("No themes found" => "");
			}
		} else {
			$this->vars['settings']['General']['Theme']['data'] = array("No themes found" => "");
		}
		
		if (is_dir($thumb_dir)) {
			if ($dh = opendir($thumb_dir)) {
				while (($item = readdir($dh)) !== false) {
					if (filetype($thumb_dir . $item) == 'dir' && $item !== "." && $item !== "..")
					{						
						$temp_thumb[$item] = $item;
					}
				}
				closedir($dh);
				
				$this->vars['settings']['General']['Thumbnail Theme']['data'] = $temp_thumb;
			} else {
				$this->vars['settings']['General']['Thumbnail Theme']['data'] = array("No themes found" => "");
			}
		} else {
			$this->vars['settings']['General']['Thumbnail Theme']['data'] = array("No themes found" => "");
		}
	}
	
	function outputSettingsFields() {
		//Output html for all setting fields
		
		$this->loadSettingsFields();
		
		$output = "";
		
		foreach ($this->vars['settings'] as $kc=>$vc) {
			//Category
			
			$output .= "<div class=\"title\">" . $kc . "</div>\n";
			$odd = true;
			
			foreach ($vc as $ki=>$vi) {
				//Item
				
				$output .= "<div class=\"" . (($odd == true) ? 'odd' : 'even') . " row\">\n";
				$output .= "<div class=\"item\">\n";
                $output .= "<div class=\"item-title\">" . $ki . "</div>\n";
				
                if (isset($this->vars['errors'][$vi['id']])) { 
					$output .= "<div class=\"item-error\">" . $this->vars['errors'][$vi['id']] . "</div>\n"; 
				}
				
                $output .= "<div class=\"item-desc\">" . $vi['desc'] . "</div>\n";
				$output .= "</div>\n";
                $output .= "<div class=\"" . ((isset($this->vars['errors'][$vi['id']])) ? 'error-field' : 'field') . "\">";
				
				if ($vi['type'] == "text") {
					$output .= "<input name=\"" . $vi['id'] . "\" type=\"text\" value=\"" . $this->settings[$vi['id']] . "\">";
				} else if ($vi['type'] == "select") {
					$output .= "<select name=\"" . $vi['id'] . "\">\n";
					foreach ($vi['data'] as $ko=>$vo) {
						$output .= "<option value=\"" . $vo . "\" " . (($this->getBool($this->settings[$vi['id']]) == $vo) ? 'selected="selected"' : '') . ">" . $ko . "</option>\n";
					}
					$output .= "</select>\n";
				} else if ($vi['type'] == "password") {
					$output .= "<input name=\"" . $vi['id'] . "\" type=\"password\">";
				}
				
				$output .= "<input name= \"var_" . $vi['id'] . "\" type=\"hidden\" value=\"" . $vi['var'] . "\">";
				
				$output .= "</div>\n";
                $output .= "</div>\n";
				
				if ($odd == true) { $odd = false; } else { $odd = true; }
			}			
		}
		
		echo $output;
	}
	
	/*
	function outputVarsArray() {
		echo "Variables:";
		echo '<pre>';
		print_r($this->vars);
		echo '</pre>';
		
		echo "Settings:";
		echo '<pre>';
		print_r($this->settings);
		echo '</pre>';
		
		echo "POST:";
		echo '<pre>';
		print_r($_POST);
		echo '</pre>';
	}*/
	
	function initialize() {
		//Return to Gallery once finished the setup
		if (isset($_POST['finish'])) { header("Location: ../../"); }
		
		session_start();
		
		if ($_SERVER['QUERY_STRING'] == "logout") {
			//Clear session and return to gallery
			$_SESSION = array();

			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
				);
			}
			
			session_destroy();
			
			header("Location: ../../");
		}	
		
		$this->loadVars();
		
		if (is_file('../phppi_settings.php') && $this->vars['version'] !== $this->vars['installed_version']) {
			$this->vars['setup_mode'] = "upgrade";
		} else if (!is_file('../settings.php') && !is_file('../phppi_settings.php')) {
			$this->vars['setup_mode'] = "new";
		} else if (is_file('../settings.php') && !is_file('../phppi_settings.php')) {
			$this->vars['setup_mode'] = "legacy";
		} else {
			$this->vars['setup_mode'] = "admin";
		}
		
		if (isset($_POST['login'])) {
			$_SESSION['password'] = hash("sha256", $_POST['password_field']);
		}
		
		if (!isset($_SESSION['password']) && ($this->vars['setup_mode'] == "admin" || $this->vars['setup_mode'] == "upgrade")) {
			//If admin or upgrade mode show login screen
			$this->showLogin();
		} else if (isset($_SESSION['password']) && $this->vars['setup_mode'] == "admin") {
			//Check password and allow access if correct
			$this->loadSettings();
			
			if ($_SESSION['password'] === $this->settings['admin_password']) {
				$this->showAdmin();
			} else {
				$this->showLogin("Incorrect password");
			}
		} else {
			if (isset($_SESSION['password']) && $this->vars['setup_mode'] == "upgrade") {
				$this->loadSettings();
				
				if ($_SESSION['password'] !== $this->settings['admin_password']) {
					$this->showLogin("Incorrect password");
					exit();
				}
			}

			if (isset($_POST['step_2'])) {
				if (!$this->importSettings()) { $this->showError("Unable to import settings, your old or current settings file may contain errors. Remove the file and try again."); exit; }
				$this->showSetup(2);
			} else if (isset($_POST['step_3'])) {
				if ($this->checkErrors()) {
					$this->saveSettings();
					$this->showSetup(3);
				} else {
					if (!$this->importSettings()) { $this->showError("Unable to import settings, your old or current settings file may contain errors. Remove the file and try again."); exit; }
					$this->showSetup(2);
				}
			} else {
				$this->showSetup(1);
			}
		}
	}
}
?>