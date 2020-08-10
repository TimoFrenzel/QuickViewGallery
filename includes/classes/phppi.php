<?php

ini_set('gd.jpeg_ignore_warning', 1);

class PHPPI
{
    public $settings;
    public $vars;

    public function startTimer()
    {
        $temp_time = microtime();
        $temp_time = explode(' ', $temp_time);
        $temp_time = $temp_time[1] + $temp_time[0];
        $this->vars['start_time'] = $temp_time;
    }

    public function endTimer()
    {
        $temp_time = microtime();
        $temp_time = explode(' ', $temp_time);
        $temp_time = $temp_time[1] + $temp_time[0];
        $this->vars['end_time'] = $temp_time;
        $this->vars['total_time'] = ($this->vars['end_time'] - $this->vars['start_time']);
    }

    public function logs($log, $action, $value)
    {
        $temp_output = '';

        if (!is_dir('phppi/logs/')) {
            //Create logs folder
            if (!mkdir('phppi/logs/', 0775)) {
                return false;
            }
        }

        $datetime = new DateTime('', new DateTimeZone('GMT'));
        $datetime->setTimezone(new DateTimeZone($this->settings['log_timezone']));

        if ($action == 'add') {
            if ($log == 'access' && $this->settings['access_log'] == 'on') {
                $temp_output = $datetime->format('Y-m-d H:i:s').', '.$_SERVER['REMOTE_ADDR'].': '.$value;
            } elseif ($log == 'phppi') {
            }
        }

        if ($temp_output !== '') {
            $fh = @fopen('phppi/logs/'.$log.'.log', 'a');
            fwrite($fh, $temp_output."\n");
            fclose($fh);
        }
    }

    public function setThemeMode()
    {
        require 'phppi/includes/classes/browser.php';
        $browser = new Browser();

        $this->vars['isIE'] = false;

        switch ($browser->getBrowser()) {
            case Browser::BROWSER_ANDROID:
                $this->vars['theme_mode'] = 'mobile';
                if ($this->settings['disable_popup_image_viewer_for_mobile'] == true) {
                    $this->settings['use_popup_image_viewer'] = false;
                }
                break;
            case Browser::BROWSER_IPHONE:
                $this->vars['theme_mode'] = 'mobile';
                if ($this->settings['disable_popup_image_viewer_for_mobile'] == true) {
                    $this->settings['use_popup_image_viewer'] = false;
                }
                break;
            case Browser::BROWSER_IPOD:
                $this->vars['theme_mode'] = 'mobile';
                if ($this->settings['disable_popup_image_viewer_for_mobile'] == true) {
                    $this->settings['use_popup_image_viewer'] = false;
                }
                break;
            case Browser::BROWSER_IE:
                $this->vars['theme_mode'] = 'standard';
                $this->vars['isIE'] = true;
                break;
            default:
                $this->vars['theme_mode'] = 'standard';
                break;
        }
    }

    public function loadSettings($theme = false)
    {
        //Set theme to true if you want to retrieve theme settings

        /*if ($theme == true)
        {
            if (!is_file('phppi/themes/gallery/' . $this->settings['theme'] . '/' . $this->vars['theme_mode'] . '/settings.xml'))
            {
                return false;
            } else {
                require('phppi/themes/gallery/' . $this->settings['theme'] . '/' . $this->vars['theme_mode'] . '/settings.xml');
                $this->setThumbSize(NULL);
                return true;
            }
        } else {*/
        if (!is_file('phppi/phppi_settings.php')) {
            return false;
        } else {
            require 'phppi/phppi_settings.php';
        }
        //}
    }

    public function loadVars()
    {
        $query = '';
        if ($this->settings['cyrillic_support'] == true) {
            $query = $this->cleanPath(rawurldecode($_SERVER['QUERY_STRING']));
        } else {
            $query = $this->cleanPath($_SERVER['QUERY_STRING']);
        }

        $this->vars['dir']['local'] = realpath(dirname($_SERVER['SCRIPT_FILENAME'])); //													/var/www/pictures
        $this->vars['dir']['gallery'] = $this->settings['gallery_folder']; //																/var/www/pictures/gallery

        if (!isset($_GET['image']) && !isset($_GET['thumb'])) {
            if (isset($_GET['view'])) {
                $query = substr($query, 5);
            }
            $this->vars['dir']['req']['split'] = explode('/', $query); //																	Array, [0] = first folder, [1] = second folder, etc.

            $count = count($this->vars['dir']['req']['split']);
            $this->vars['dir']['req']['parent'] = '';

            if ($count > 0) {
                for ($i = 0; $i < ($count - 1); $i++) {
                    $this->vars['dir']['req']['parent'] .= $this->vars['dir']['req']['split'][$i]; //										photos
                    if ($i < ($count - 2)) {
                        $this->vars['dir']['req']['parent'] .= '/';
                    }
                }
            }

            $this->vars['dir']['req']['full'] = $query; //																					photos/landscapes
            $this->vars['dir']['req']['curr'] = $this->vars['dir']['req']['split'][$count - 1]; //											photos
        }

        $this->vars['dir']['cache']['base'] = $this->settings['cache_folder']; //															phppi/cache

        if ($query !== '') {
            $this->vars['dir']['cache']['full'] = $this->vars['dir']['cache']['base'].'/'.$query; //									phppi/cache/photos/landscapes
        } else {
            $this->vars['dir']['cache']['full'] = $this->vars['dir']['cache']['base']; //													phppi/cache
        }
    }

    public function loadLists()
    {
        $temp_file = file_get_contents('phppi/file_blacklist.txt');
        $this->vars['file_blacklist'] = explode(',', $temp_file);

        $temp_folder = file_get_contents('phppi/folder_blacklist.txt');
        $this->vars['folder_blacklist'] = explode(',', $temp_folder);

        $temp_type = file_get_contents('phppi/file_types.txt');
        $this->vars['file_types'] = explode(',', $temp_type);
    }

    public function checkList($item, $list)
    {
        foreach ($list as $list_item) {
            if (strtolower($list_item) == strtolower($item)) {
                return true;
            }
        }

        return false;
    }

    public function cleanPath($path)
    {
        $path = str_replace('%20', ' ', $path);

        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        if (substr($path, -1, 1) == '/') {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    public function checkExploit($path, $file = false)
    {
        $real_base = realpath($this->vars['dir']['gallery']);

        $path = (DIRECTORY_SEPARATOR === '\\') ? str_replace('/', '\\', $path) : str_replace('\\', '/', $path);

        $var_path = $this->vars['dir']['gallery'].$path;
        $real_var_path = realpath($var_path);

        /*echo "Requested: " . $path . "<br>";
        echo "Base real path: " . $real_base . "<br>";
        echo "Requested real path: " . $real_var_path . "<br>";
        echo "Therefore " . $real_base . $path . " should equal " . $real_var_path . "<br>";*/

        if ($real_var_path === false || ($real_base.$path) !== $real_var_path) {
            return false;
        } else {
            return true;
        }
    }

    public function escapeString($string, $action = 'add')
    {
        if ($action == 'add') {
            if (get_magic_quotes_gpc()) {
                return $string;
            } else {
                return addslashes($string);
            }
        } elseif ($action == 'strip') {
            return stripslashes($string);
        }
    }

    public function pathInfo($path, $info)
    {
        $temp = pathinfo($path);

        if ($info == 'dir_path') {
            if ($temp['dirname'] == '.') {
                return '';
            } else {
                return $temp['dirname'];
            }
        } elseif ($info == 'full_file_name') {
            return $temp['basename'];
        } elseif ($info == 'file_ext') {
            return $temp['extension'];
        } elseif ($info == 'file_name') {
            return $temp['filename'];
        }
    }

    public function fixPath($path)
    {
        if ($path == '') {
            return '';
        } elseif (substr($path, -1) !== '/') {
            return $path.'/';
        } else {
            return $path;
        }
    }

    public function getThumbCode($type)
    {
        if ($code = file_get_contents('phppi/themes/thumbnail/'.$this->settings['thumbnail_theme'].'/'.$type.'_layout.htm')) {
            return $code;
        } else {
            return '';
        }
    }

    public function getBool($value)
    {
        switch (strtolower($value)) {
            case 'true': return true;
            case 'false': return false;
            default: return null;
        }
    }

    public function getDir($dir)
    {
        if ($dir_data = $this->getDirData($dir, 'both', true)) {
            $this->vars['folder_list'] = [];
            $this->vars['file_list'] = [];

            if (isset($dir_data['file'])) {
                if (count($dir_data['file']) > 0) {
                    $this->vars['file_list'] = $dir_data['file'];
                }
            }
            if (isset($dir_data['dir'])) {
                if (count($dir_data['dir']) > 0) {
                    $this->vars['folder_list'] = $dir_data['dir'];
                }
            }

            $cache_folder = $this->fixPath($this->vars['dir']['cache']['base']);
            $dir = $this->fixPath($dir);

            if (($this->settings['use_file_cache'] == true) && (time() - @filemtime($cache_folder.$dir.'cache.xml') > $this->settings['expire_file_cache'])) {
                $this->cacheDir($dir);
            }

            return true;
        } else {
            return false;
        }
    }

    public function getDirData($dir, $type = 'both', $cached = false, $forced_cache = false)
    {
        //$full_dir: Root folder combined with requested folder with trailing /
        //$dir: Requested folder
        //$dh: Directory Handler
        //$item: File/Dir data during directory scan
        //$fd: Found Directories array
        //$ff: Found Files array

        $cache_folder = $this->fixPath($this->vars['dir']['cache']['base']);
        $dir = $this->fixPath($dir);
        if (substr($dir, 0, 1) == '/') {
            $dir = substr($dir, 1);
        }
        $full_dir = $this->fixPath($this->vars['dir']['gallery']).'/'.$dir;
        $output = [];
        $cache_expire = true;
        $ff = [];
        $fd = [];

        if (is_dir($full_dir)) {
            if ($cached == true && $this->settings['use_file_cache'] == true && is_file($cache_folder.$dir.'cache.xml')) {
                if (((time() - filemtime($cache_folder.$dir.'cache.xml')) < $this->settings['expire_file_cache']) || $forced_cache == true) {
                    $cache_expire = false;
                } else {
                    $cache_expire = true;
                }
            } else {
                $cache_expire = true;
            }

            if ($cache_expire == false) {
                $xml = new SimpleXMLElement(file_get_contents($cache_folder.$dir.'cache.xml'));

                $x = 0;

                if (isset($xml->directories)) {
                    foreach ($xml->directories->dir as $dirs) {
                        $fd[$x]['full_path'] = (string) $dirs->path;
                        $fd[$x]['dir'] = (string) $dirs->dirname;

                        $x++;
                    }
                }

                $x = 0;

                if (isset($xml->files)) {
                    foreach ($xml->files->file as $files) {
                        $ff[$x]['full_path'] = (string) $files->path;
                        $ff[$x]['file'] = (string) $files->filename;
                        $ff[$x]['data'][0] = (int) $files->data->width;
                        $ff[$x]['data'][1] = (int) $files->data->height;
                        $ff[$x]['data'][2] = (int) $files->data->imagetype;
                        $ff[$x]['data'][3] = (string) $files->data->sizetext;
                        if (isset($files->data->bits)) {
                            $ff[$x]['data']['bits'] = (int) $files->data->bits;
                        }
                        if (isset($files->data->channels)) {
                            $ff[$x]['data']['channels'] = (int) $files->data->channels;
                        }
                        if (isset($files->data->mime)) {
                            $ff[$x]['data']['mime'] = (string) $files->data->mime;
                        }

                        $x++;
                    }
                }
            } elseif ($cache_expire == true) {
                if ($dh = opendir($full_dir)) {
                    while (($item = readdir($dh)) !== false) {
                        if (filetype($full_dir.$item) == 'dir' && $type != 'file' && $this->checkList($item, $this->vars['folder_blacklist']) == false) {
                            $fd[] = [
                                'full_path'=> $dir.$item,
                                'dir'      => $item,
                            ];

                            sort($fd);
                        } elseif (filetype($full_dir.$item) == 'file' && $type != 'dir' && $this->checkList($item, $this->vars['file_blacklist']) == false && $this->checkList($this->pathInfo($item, 'file_ext'), $this->vars['file_types']) == true) {
                            $ff[] = [
                                'full_path'=> $dir.$item,
                                'file'     => $item,
                                'data'     => getimagesize($full_dir.$item),
                            ];

                            sort($ff);
                        }
                    }
                    closedir($dh);
                } else {
                    return false;
                }
            }

            if ($type == 'both') {
                if (isset($ff)) {
                    $output['file'] = $ff;
                }
                if (isset($fd)) {
                    $output['dir'] = $fd;
                }

                return $output;
            } elseif ($type == 'file') {
                if (isset($ff)) {
                    $output = $ff;
                }

                return $output;
            } elseif ($type == 'dir') {
                if (isset($fd)) {
                    $output = $fd;
                }

                return $output;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function cacheDir($dir)
    {
        $cache_folder = $this->fixPath($this->vars['dir']['cache']['base']);
        $cache_exists = false;

        if (substr($dir, 0, strlen($this->vars['dir']['gallery']) + 1) == $this->vars['dir']['gallery'].'/') {
            $dir = substr($dir, strlen($this->vars['dir']['gallery']) + 1);
        }

        if (count($this->vars['folder_list']) > 0 or count($this->vars['file_list']) > 0) {
            $cache_exists = $this->genCacheDir($dir);

            if ($cache_exists == true) {
                $xmlstr = "<?xml version='1.0' ?>\n<cache></cache>";
                $xml = new SimpleXMLElement($xmlstr);

                if (isset($this->vars['folder_list'])) {
                    $xml_dir = $xml->addChild('directories');

                    foreach ($this->vars['folder_list'] as $dirs) {
                        $xml_dirs_data = $xml_dir->addChild('dir');
                        $xml_dirs_data->addChild('path', $dirs['full_path']);
                        $xml_dirs_data->addChild('dirname', $dirs['dir']);
                    }
                }

                if (isset($this->vars['file_list'])) {
                    $xml_files = $xml->addChild('files');

                    foreach ($this->vars['file_list'] as $files) {
                        $xml_files_data = $xml_files->addChild('file');
                        $xml_files_data->addChild('path', $files['full_path']);
                        $xml_files_data->addChild('filename', $files['file']);

                        $xml_data = $xml_files_data->addChild('data');
                        $xml_data->addChild('width', $files['data'][0]);
                        $xml_data->addChild('height', $files['data'][1]);
                        $xml_data->addChild('imagetype', $files['data'][2]);
                        $xml_data->addChild('sizetext', $files['data'][3]);
                        if (isset($files['data']['bits'])) {
                            $xml_data->addChild('bits', $files['data']['bits']);
                        }
                        if (isset($files['data']['channels'])) {
                            $xml_data->addChild('channels', $files['data']['channels']);
                        }
                        if (isset($files['data']['mime'])) {
                            $xml_data->addChild('mime', $files['data']['mime']);
                        }
                    }
                }

                $xml->asXML($cache_folder.$dir.'cache.xml');

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function genCacheDir($dir)
    {
        $cache_folder = $this->fixPath($this->vars['dir']['cache']['base']);

        if (substr($dir, 0, strlen($this->vars['dir']['gallery']) + 1) == $this->vars['dir']['gallery'].'/') {
            $dir = substr($dir, strlen($this->vars['dir']['gallery']) + 1);
        }

        if (!is_dir($cache_folder.$dir)) {
            //Create cache folder/s if possible
            $temp_folders = explode('/', substr($dir, 0, -1));
            $prefix = '';

            if (count($temp_folders) > 0) {
                foreach ($temp_folders as $dirs) {
                    if (!is_dir($cache_folder.$prefix.$dirs)) {
                        if (mkdir($cache_folder.$prefix.$dirs, 0775)) {
                            chmod($cache_folder.$prefix.$dirs, 0775);
                            $prefix .= $dirs.'/';
                            $cache_exists = true;
                        } else {
                            $cache_exists = false;
                            break;
                        }
                    } else {
                        $prefix .= $dirs.'/';
                        $cache_exists = true;
                    }
                }
            }
        } else {
            $cache_exists = true;
        }

        if ($cache_exists == true) {
            return true;
        } else {
            return false;
        }
    }

    public function genThumbURL($dir, $file_data)
    {
        $cache_folder = $this->fixPath($this->vars['dir']['cache']['base']);
        $use_cache = false;
        $file_ext = '';
        $temp_file_ext = '';
        $thumb_width = 0;
        $thumb_height = 0;
        $thumb_size = [];

        $file_ext = $this->pathInfo($file_data['full_path'], 'file_ext');
        //$temp_file_ext = strtolower($file_ext);

        $dir = $this->fixPath($dir);

        //if ($temp_file_ext == 'jpeg' or $temp_file_ext == 'jpg') { $file_ext = 'jpg'; }

        if ($this->settings['use_gd'] == true) {
            if ($this->settings['use_gd_cache'] == true) {
                $use_cache = false;

                if (!is_file($cache_folder.$dir.$this->pathInfo($file_data['full_path'], 'file_name').'_'.$this->vars['thumb_size'].'.'.$file_ext)) {
                    //Cached image does not exist, create if possible
                    $use_cache = false;
                } else {
                    //Cached image exists, check if correct image size
                    list($thumb_width, $thumb_height) = getimagesize($cache_folder.$dir.$this->pathInfo($file_data['full_path'], 'file_name').'_'.$this->vars['thumb_size'].'.'.$file_ext);

                    $thumb_size = $this->resizedSize($file_data['data'][0], $file_data['data'][1]);

                    if ($thumb_size[0] != $thumb_width and $thumb_size[1] != $thumb_height) {
                        //Cached image does not match the current thumbnail size settings, create new thumbnail
                        $use_cache = false;
                    } else {
                        //Cached image does not need updating, use cached thumbnail
                        $use_cache = true;
                    }
                }

                if ($use_cache == true) {
                    $img_url = $cache_folder.$dir.$this->pathInfo($file_data['full_path'], 'file_name').'_'.$this->vars['thumb_size'].'.'.$file_ext;
                } else {
                    $img_url = '?thumb='.$dir.$this->pathInfo($file_data['full_path'], 'file_name').'.'.$file_ext.'&size='.$this->vars['thumb_size'];
                }
            } else {
                $img_url = '?thumb='.$dir.$this->pathInfo($file_data['full_path'], 'file_name').'.'.$file_ext.'&size='.$this->vars['thumb_size'];
            }
        } else {
            $img_url = $this->fixPath($this->settings['thumbs_folder']).$dir.$this->pathInfo($file_data['full_path'], 'file_name').'.'.$this->settings['thumb_file_ext'];
        }

        return $img_url;
    }

    public function genThumbnail($filename)
    {
        //Creates thumbnail, either dynamically or for cache depending on settings
        $filename = $this->escapeString($filename, 'strip');

        if ($this->checkExploit('/'.$filename, true) == true) {
            $filename = $this->vars['dir']['gallery'].'/'.$filename;

            $temp_count = strlen($this->vars['dir']['gallery']) + 1;
            $temp_path = substr($this->fixPath($this->pathInfo($filename, 'dir_path')), $temp_count);

            $cache_folder = $this->fixPath($this->vars['dir']['cache']['base']).$temp_path;

            $create_cache_file = false;

            if ($this->settings['use_gd'] == true) {
                $create_cache_file = $this->genCacheDir($temp_path);
            }

            $file_ext = strtolower($this->pathInfo($filename, 'file_ext'));

            if ($file_ext == 'jpg' or $file_ext == 'jpeg') {
                $image = imagecreatefromjpeg($filename);
                $format = 'jpeg';
            } elseif ($file_ext == 'png') {
                $image = imagecreatefrompng($filename);
                $format = 'png';
            } elseif ($file_ext == 'gif') {
                $image = imagecreatefromgif($filename);
                $format = 'gif';
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $new_size = $this->resizedSize($width, $height);

            $new_image = imagecreatetruecolor($new_size[0], $new_size[1]);
            imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_size[0], $new_size[1], $width, $height);

            if ($create_cache_file == false) {
                header('Pragma: public');
                header('Cache-Control: maxage='.$this->settings['gd_cache_expire']);
                header('Expires: '.gmdate('D, d M Y H:i:s', time() + $this->settings['gd_cache_expire']).' GMT');
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

                if ($format == 'jpeg') {
                    header('Content-type: image/jpeg');
                    imagejpeg($new_image, null, $this->settings['jpeg_quality']);
                } elseif ($format == 'png') {
                    header('Content-type: image/png');
                    imagepng($new_image);
                } elseif ($format == 'gif') {
                    header('Content-type: image/gif');
                    imagegif($new_image);
                }
            } elseif ($create_cache_file == true) {
                header('Pragma: public');
                header('Cache-Control: maxage='.$this->settings['gd_cache_expire']);
                header('Expires: '.gmdate('D, d M Y H:i:s', time() + $this->settings['gd_cache_expire']).' GMT');
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

                if ($format == 'jpeg') {
                    header('Content-type: image/jpeg');
                    imagejpeg($new_image, $cache_folder.$this->pathInfo($filename, 'file_name').'_'.$this->vars['thumb_size'].'.'.$this->pathInfo($filename, 'file_ext'), $this->settings['jpeg_quality']);
                    imagejpeg($new_image);
                } elseif ($format == 'png') {
                    header('Content-type: image/png');
                    imagepng($new_image, $cache_folder.$this->pathInfo($filename, 'file_name').'_'.$this->vars['thumb_size'].'.'.$this->pathInfo($filename, 'file_ext'));
                    imagepng($new_image);
                } elseif ($format == 'gif') {
                    header('Content-type: image/gif');
                    imagegif($new_image, $cache_folder.$this->pathInfo($filename, 'file_name').'_'.$this->vars['thumb_size'].'.'.$this->pathInfo($filename, 'file_ext'));
                    imagegif($new_image);
                }
            }

            if ($this->settings['access_log_no_thumbnail'] == false) {
                $this->logs('access', 'add', 'Generated thumbnail (/'.$cache_folder.$this->pathInfo($filename, 'file_name').'_'.$this->vars['thumb_size'].'.'.$this->pathInfo($filename, 'file_ext').')');
            }

            imagedestroy($new_image);
        } else {
            echo 'File not found.';
        }
    }

    public function setThumbSize($size)
    {
        //$size: small|medium|large

        if (isset($size) && $this->settings['enable_thumb_size_change'] == true) {
            setcookie('thumb_size', $size);
            $this->vars['thumb_size'] = $size;
        } else {
            if (isset($_COOKIE['thumb_size']) && $this->settings['enable_thumb_size_change'] == true) {
                $this->vars['thumb_size'] = $_COOKIE['thumb_size'];
            } else {
                $this->vars['thumb_size'] = $this->settings['thumb_size_default'];
            }
        }
    }

    public function insertThumbSize($format = 0)
    {
        //0 = Output thumb size changer code
        //1 = Return thumb size changer code as string
        $output = '';

        if ($this->settings['enable_thumb_size_change'] == true && $this->settings['use_gd'] == true) {
            $output .= '<form method="post">';
            $output .= '<select id="thumb-size-select" name="thumb_size">';
            $output .= '<option value="0" '.($this->vars['thumb_size'] == 'small' ? 'selected' : '').'>Small</option>';
            $output .= '<option value="1" '.($this->vars['thumb_size'] == 'medium' ? 'selected' : '').'>Medium</option>';
            $output .= '<option value="2" '.($this->vars['thumb_size'] == 'large' ? 'selected' : '').'>Large</option>';
            $output .= '</select>&nbsp;';
            $output .= '<input type="submit" value="Set">';
            $output .= '</form>';
        } else {
            $output = '';
        }

        if ($format == 0) {
            echo $output;
        } elseif ($format == 1) {
            return $output;
        }
    }

    public function prevFolderExists()
    {
        if ($this->vars['dir']['req']['full'] != '') {
            return true;
        } else {
            return false;
        }
    }

    public function prevImageExists($ignore_javascript = false)
    {
        //Set ignore_javascript to true if you want accurate results, otherwise if use_javascript_navigation is set to true this will always return true

        if (isset($this->vars['previous_image_id'])) {
            return true;
        } elseif ($ignore_javascript == false and $this->settings['use_javascript_navigation'] == true) {
            return true;
        } else {
            return false;
        }
    }

    public function nextImageExists($ignore_javascript = false)
    {
        //Set ignore_javascript to true if you want accurate results, otherwise if use_javascript_navigation is set to true this will always return true

        if (isset($this->vars['next_image_id'])) {
            return true;
        } elseif ($ignore_javascript == false and $this->settings['use_javascript_navigation'] == true) {
            return true;
        } else {
            return false;
        }
    }

    public function noticeExists()
    {
        if ($this->settings['site_notice'] != '') {
            return true;
        } else {
            return false;
        }
    }

    public function logoExists()
    {
        if ($this->settings['page_title_logo'] != '') {
            return true;
        } else {
            return false;
        }
    }

    public function outputSettingsArray()
    {
        echo 'Settings:';
        echo '<pre>';
        print_r($this->settings);
        echo '</pre>';
    }

    public function outputVarsArray()
    {
        echo 'Variables:';
        echo '<pre>';
        print_r($this->vars);
        echo '</pre>';
    }

    public function showError($format = 0)
    {
        //0 = Output error
        //1 = Return error as string

        if ($format == 0) {
            echo $this->vars['error'];
        } elseif ($format == 1) {
            return $this->vars['error'];
        }
    }

    public function showNotice($format = 0)
    {
        //0 = Output notice
        //1 = Return notice as string

        if ($format == 0) {
            echo $this->settings['site_notice'];
        } elseif ($format == 1) {
            return $this->settings['site_notice'];
        }
    }

    public function showImage($format = 2)
    {
        //0 = Output url
        //1 = Return url as string
        //2 = Output full img tag
        //3 = Return full img tag as string

        $file = '?image='.$_GET['view'];

        if ($format == 0) {
            echo $file;
        } elseif ($format == 1) {
            return $file;
        } elseif ($format == 2) {
            $output = '<img id="image" src="'.$file.'" alt="'.$file.'">';

            if ($this->settings['enable_click_next'] == true && isset($this->vars['file_list'][$this->vars['next_image_id']])) {
                if ($this->settings['use_javascript_navigation'] == true) {
                    $output = '<a href="javascript: phppi.go_next_image();">'.$output.'</a>';
                } else {
                    $output = '<a href="?view='.$this->vars['file_list'][$this->vars['next_image_id']]['full_path'].'">'.$output.'</a>';
                }
            }

            echo $output;
        } elseif ($format == 3) {
            $output = '<img id="image" src="'.$file.'" alt="'.$file.'">';

            if ($this->settings['enable_click_next'] == true && isset($this->vars['file_list'][$this->vars['next_image_id']])) {
                if ($this->settings['use_javascript_navigation'] == true) {
                    $output = '<a href="javascript: phppi.go_next_image();">'.$output.'</a>';
                } else {
                    $output = '<a href="?view='.$this->vars['file_list'][$this->vars['next_image_id']]['full_path'].'">'.$output.'</a>';
                }
            }

            return $output;
        }
    }

    public function showFooter($format = 0)
    {
        //0 = Output footer
        //1 = Return footer as string

        if (is_file('phppi/footer.txt')) {
            $footer = file_get_contents('phppi/footer.txt');

            $this->endTimer();

            $search = [
                '[site_name]',
                '[current_item]',
                '[version]',
                '[load_time]',
            ];
            $replace = [
                $this->settings['site_name'],
                $this->vars['page_title'],
                $this->vars['version'],
                number_format($this->vars['total_time'], 4),
            ];

            if ($format == 0) {
                echo str_replace($search, $replace, $footer);
            } elseif ($format == 1) {
                return str_replace($search, $replace, $footer);
            }
        }
    }

    public function showGallery()
    {
        if ($this->vars['dir']['req']['full'] != '') {
            $request = $this->vars['dir']['req']['full'].'/';
        } else {
            $request = '';
        }

        if (isset($this->vars['folder_list'])) {
            $thumb_code['folder'] = $this->getThumbCode('folder');

            foreach ($this->vars['folder_list'] as $dir) {
                if (is_dir($this->vars['dir']['gallery'].'/'.$request.$dir['dir'])) {
                    if ($this->settings['thumb_folder_show_thumbs'] == true) {
                        if ($this->settings['thumb_folder_use_cache_only'] == true) {
                            $dir_data = $this->getDirData($request.$dir['dir'], 'both', true, true);
                        } else {
                            $dir_data = $this->getDirData($request.$dir['dir'], 'both', true);
                        }

                        if ($this->settings['thumb_folder_shuffle'] == true) {
                            shuffle($dir_data['file']);
                        }

                        if ($dir_data['file']) {
                            $temp_dir_data = $dir_data['file'][0];
                            $img_url = $this->genThumbURL($request.$dir['dir'], $temp_dir_data);
                        } else {
                            $img_url = $this->showThemeURL(1).'images/no_images.png';
                        }
                    } else {
                        $img_url = $this->showThemeURL(1).'images/no_images.png';
                    }
                } else {
                    $img_url = $this->showThemeURL(1).'images/no_images.png';
                }

                if ($this->settings['folder_show_title_on_hover'] == false) {
                    $class = '-show';
                } else {
                    $class = '';
                }

                //<<URL>> = $request . $dir['dir'] = folder/
                //<<TITLE>> = $dir['dir'] = folder
                //<<THUMB_WIDTH>> = $this->settings['thumb_size_' . $this->vars['thumb_size']] = 125 (autodetect thumb size also)
                //<<THUMB_HEIGHT>> = $this->settings['thumb_size_' . $this->vars['thumb_size']] = 125 (autodetect thumb size also)
                //<<THUMB_LOCATION>> = $this->escapeString($img_url) = phppi/cache/folder/image_small.jpg (may also set to phppi/themes/gallery/themename/images/no_images.png if no image)
                //<<THEME_LOCATION>> = $this->showThemeURL(1) = phppi/themes/gallery/themename

                $replace_codes = ['<<URL>>',
                    '<<TITLE>>',
                    '<<THUMB_WIDTH>>',
                    '<<THUMB_HEIGHT>>',
                    '<<THUMB_LOCATION>>',
                    '<<THEME_LOCATION>>',
                ];

                $replace_values = ['?'.$request.$dir['dir'],
                    $dir['dir'],
                    $this->settings['thumb_size_'.$this->vars['thumb_size']],
                    $this->settings['thumb_size_'.$this->vars['thumb_size']],
                    $this->escapeString($img_url),
                    $this->showThemeURL(1),
                ];

                echo str_replace($replace_codes, $replace_values, $thumb_code['folder']);
            }
        }

        if (isset($this->vars['file_list'])) {
            $thumb_code['file'] = $this->getThumbCode('file');

            foreach ($this->vars['file_list'] as $file) {
                $output = '';

                $img_url = $this->genThumbURL($request, $file);

                if ($this->settings['use_popup_image_viewer'] == true) {
                    $url = '?image='.$request.$file['file'];
                    if ($this->settings['show_thumbs_under_viewer'] == true) {
                        $fancy_class = 'fancybox-thumbs';
                        $fancy_attr = 'data-fancybox-group="thumb"';
                    } else {
                        $fancy_class = 'fancybox';
                        $fancy_attr = 'data-fancybox-group="gallery"';
                    }
                } else {
                    $url = '?view='.$request.$file['file'];
                    $fancy_class = '';
                    $fancy_attr = '';
                }

                //<<URL>> = $url = ?view=folder/image.jpg
                //<<FANCY_CLASS>> = $fancy_class = fancybox-thumbs (If left out fancybox will not work. Use inside class attribute on A tag)
                //<<FANCY_ATTR>> = $fancy_attr = data-fancybox-group="gallery" (If left out fancybox will not work. Use on A tag)
                //<<TITLE>> = $file['file'] = image.jpg
                //<<THUMB_WIDTH>> = $this->settings['thumb_size_' . $this->vars['thumb_size']] = 125 (autodetect thumb size also)
                //<<THUMB_HEIGHT>> = $this->settings['thumb_size_' . $this->vars['thumb_size']] = 125 (autodetect thumb size also)
                //<<THUMB_LOCATION>> = $this->escapeString($img_url) = phppi/cache/folder/image_small.jpg (may also set to phppi/themes/gallery/themename/images/no_images.png if no image)
                //<<THEME_LOCATION>> = $this->showThemeURL(1) = phppi/themes/gallery/themename

                $replace_codes = ['<<URL>>',
                    '<<FANCY_CLASS>>',
                    '<<FANCY_ATTR>>',
                    '<<TITLE>>',
                    '<<THUMB_WIDTH>>',
                    '<<THUMB_HEIGHT>>',
                    '<<THUMB_LOCATION>>',
                    '<<THEME_LOCATION>>',
                ];

                $replace_values = [$url,
                    $fancy_class,
                    $fancy_attr,
                    $file['file'],
                    $this->settings['thumb_size_'.$this->vars['thumb_size']],
                    $this->settings['thumb_size_'.$this->vars['thumb_size']],
                    $this->escapeString($img_url),
                    $this->showThemeURL(1),
                ];

                echo str_replace($replace_codes, $replace_values, $thumb_code['file']);
            }
        }

        echo "<div style=\"clear: both;\"></div>\n";
    }

    public function showPrevFolderURL($format = 0)
    {
        //0 = Output url
        //1 = Return url as string
        if ($format == 0) {
            echo '?'.$this->vars['dir']['req']['parent'];
        } elseif ($format == 1) {
            return '?'.$this->vars['dir']['req']['parent'];
        }
    }

    public function showPrevImageURL($format = 0)
    {
        //0 = Output url
        //1 = Return url as string
        if ($format == 0) {
            if ($this->settings['use_javascript_navigation'] == true) {
                echo 'javascript: phppi.go_prev_image();';
            } else {
                if (isset($this->vars['file_list'][$this->vars['previous_image_id']]['full_path'])) {
                    echo '?view='.$this->vars['file_list'][$this->vars['previous_image_id']]['full_path'];
                } else {
                    echo '';
                }
            }
        } elseif ($format == 1) {
            if ($this->settings['use_javascript_navigation'] == true) {
                return 'javascript: phppi.go_prev_image();';
            } else {
                if (isset($this->vars['file_list'][$this->vars['previous_image_id']]['full_path'])) {
                    return '?view='.$this->vars['file_list'][$this->vars['previous_image_id']]['full_path'];
                } else {
                    return '';
                }
            }
        }
    }

    public function showNextImageURL($format = 0)
    {
        //0 = Output url
        //1 = Return url as string
        if ($format == 0) {
            if ($this->settings['use_javascript_navigation'] == true) {
                echo 'javascript: phppi.go_next_image();';
            } else {
                if (isset($this->vars['file_list'][$this->vars['next_image_id']]['full_path'])) {
                    echo '?view='.$this->vars['file_list'][$this->vars['next_image_id']]['full_path'];
                } else {
                    echo '';
                }
            }
        } elseif ($format == 1) {
            if ($this->settings['use_javascript_navigation'] == true) {
                return 'javascript: phppi.go_next_image();';
            } else {
                if (isset($this->vars['file_list'][$this->vars['next_image_id']]['full_path'])) {
                    return '?view='.$this->vars['file_list'][$this->vars['next_image_id']]['full_path'];
                } else {
                    return '';
                }
            }
        }
    }

    public function showUpFolderURL($format = 0)
    {
        //0 = Output url
        //1 = Return url as string
        if ($format == 0) {
            echo '?'.$this->pathInfo($_GET['view'], 'dir_path');
        } elseif ($format == 1) {
            return '?'.$this->pathInfo($_GET['view'], 'dir_path');
        }
    }

    public function showThemeURL($format = 0)
    {
        //0 = Output url
        //1 = Return url as string
        if ($format == 0) {
            echo 'phppi/themes/gallery/'.$this->settings['theme'].'/'.$this->vars['theme_mode'].'/';
        } elseif ($format == 1) {
            return 'phppi/themes/gallery/'.$this->settings['theme'].'/'.$this->vars['theme_mode'].'/';
        }
    }

    public function showTitle($format = 0)
    {
        //0 = Output url
        //1 = Return url as string
        if ($format == 0) {
            echo $this->vars['page_title'];
        } elseif ($format == 1) {
            return $this->vars['page_title'];
        }
    }

    public function showSiteName($format = 0)
    {
        //0 = Output name
        //1 = Return name as string
        if ($format == 0) {
            echo $this->settings['site_name'];
        } elseif ($format == 1) {
            return $this->settings['site_name'];
        }
    }

    public function showLogo($format = 0)
    {
        //0 = Output img tag
        //1 = Return img tag as string
        if ($format == 0) {
            echo '<img id="page-logo" src="'.$this->settings['page_title_logo'].'" alt="'.$this->settings['site_name'].'">';
        } elseif ($format == 1) {
            return '<img id="page-logo" src="'.$this->settings['page_title_logo'].'" alt="'.$this->settings['site_name'].'">';
        }
    }

    public function showNav($format = 0, $home = '', $prev = '', $sep = '', $mode = '')
    {
        //Mode:
        //classic = Only show title and previous button
        //new = Breadcrumb style, may take up most of the page if using a large folder tree
        //auto = Depending on theme it may switch between the two depending on the screen size
        //left empty = Set based on user settings

        //$home = HTML to insert for home button
        //$prev = HTML to insert for prev button
        //$sep = HTML to insert for seperator

        $output = '';

        if ($mode == '') {
            $mode = $this->settings['nav_menu_style'];
        }

        if ($mode == 'auto' || $mode == 'new') {
            $new_output = '<ul><li class="nav-home"><a href="?">'.$home.'</a></li>';
            $url = '?';

            if ($this->vars['dir']['req']['full'] !== '') {
                $new_output .= '<li class="nav-sep">'.$sep.'</li>';

                $i = 1;
                foreach ($this->vars['dir']['req']['split'] as $value) {
                    if ($i < (count($this->vars['dir']['req']['split']))) {
                        $url .= $value.'/';
                        $new_output .= '<li><a href="'.substr($url, 0, -1).'">'.$value.'</a></li>';
                        $new_output .= '<li class="nav-sep">'.$sep.'</li>';
                    } else {
                        $new_output .= '<li class="nav-curr"><div class="title">'.$value.'</div></li>';
                    }

                    $i++;
                }
            }

            $new_output .= '</ul>';
        }

        if ($mode == 'auto' || $mode == 'classic') {
            $url = '?';

            if ($this->vars['dir']['req']['parent'] !== '') {
                $i = 1;
                foreach ($this->vars['dir']['req']['split'] as $value) {
                    if ($i < (count($this->vars['dir']['req']['split']))) {
                        $url .= $value.'/';
                    }

                    $i++;
                }

                $url = substr($url, 0, -1);
            }

            $classic_output = '<ul><li class="nav-prev"><a href="'.$url.'">'.$prev.'</a></li>';
            if ($this->vars['dir']['req']['curr'] !== '') {
                $classic_output .= '<li class="nav-sep">'.$sep.'</li>';
                $classic_output .= '<li class="nav-curr"><div class="title">'.$this->vars['dir']['req']['curr'].'</div></li>';
            }
            $classic_output .= '</ul>';
        }

        if ($mode == 'auto') {
            $output .= '<div class="nav-menu-new">'.$new_output.'</div>';
            $output .= '<div class="nav-menu-classic">'.$classic_output.'</div>';
        } elseif ($mode == 'new') {
            $output = $new_output;
        } elseif ($mode == 'classic') {
            $output = $classic_output;
        }

        //0 = Output nav
        //1 = Return nav as string
        if ($format == 0) {
            echo $output;
        } elseif ($format == 1) {
            return $output;
        }
    }

    public function showPage()
    {
        require $this->showThemeURL(1).'pages/'.$this->vars['page_requested'].'.php';
    }

    public function resizedSize($width, $height, $return = 2)
    {
        //Returns width, height or an array of width and height for the thumbnail size of a full sized image
        if ($width > $height) {
            $new_height = $this->settings['thumb_size_'.$this->vars['thumb_size']];
            $new_width = $width * ($this->settings['thumb_size_'.$this->vars['thumb_size']] / $height);
        } elseif ($width < $height) {
            $new_height = $height * ($this->settings['thumb_size_'.$this->vars['thumb_size']] / $width);
            $new_width = $this->settings['thumb_size_'.$this->vars['thumb_size']];
        } elseif ($width == $height) {
            $new_width = $this->settings['thumb_size_'.$this->vars['thumb_size']];
            $new_height = $this->settings['thumb_size_'.$this->vars['thumb_size']];
        }

        if ($return == 0) {
            //Return width
            return floor($new_width);
        } elseif ($return == 1) {
            //Return height
            return floor($new_height);
        } elseif ($return == 2) {
            //Return array with width and height
            return [floor($new_width), floor($new_height)];
        }
    }

    public function insertHeadInfo()
    {
        echo '
<!-- 
PHP Picture Index '.$this->vars['version']."

Created by: Brendan Ryan (http://www.pixelizm.com/)
Site: http://phppi.pixelizm.com/
Licence: GNU General Public License v3                   		 
http://www.gnu.org/licenses/                
-->\n\n";

        echo "<meta name=\"viewport\" content=\"width=device-width; initial-scale=1.0; user-scalable = no; maximum-scale=1.0;\">\n";
        if (isset($_GET['view']) && !isset($this->vars['error'])) {
            echo '<script type="text/javascript" src="phppi/scripts/jquery/jquery.js"></script>';
        } elseif ($this->settings['use_popup_image_viewer'] == true) {
            echo "<script type=\"text/javascript\" src=\"phppi/scripts/jquery/jquery.js\"></script>\n";
        }

        if (isset($_GET['view']) && !isset($this->vars['error'])) {
            if ($this->settings['page_title_show_full_path'] == true) {
                $temp_title_full_path = '1';
            } else {
                $temp_title_full_path = '0';
            }
            if ($this->settings['enable_hotkeys']) {
                $enable_hotkeys = 1;
            } else {
                $enable_hotkeys = 0;
            }
            if ($this->settings['enable_up_hotkey']) {
                $enable_up_hotkey = 1;
            } else {
                $enable_up_hotkey = 0;
            }

            echo '
<script type="text/javascript" src="phppi/scripts/phppi_js.js"></script>			
<script type="text/javascript">
	$(document).ready(function() { phppi.initialize(); });
	
	phppi.image_width = '.$this->vars['file_list'][$this->vars['current_image_id']]['data'][0].';
	phppi.image_height = '.$this->vars['file_list'][$this->vars['current_image_id']]['data'][1].";
	phppi.up_folder = '".$this->escapeString($this->showUpFolderURL(1))."';
	phppi.prev_image = '".$this->escapeString($this->showPrevImageURL(1))."';
	phppi.next_image = '".$this->escapeString($this->showNextImageURL(1))."';
	phppi.title_full_path = ".$temp_title_full_path.';
	phppi.enable_hotkeys = '.$enable_hotkeys.';
	phppi.enable_up_hotkey = '.$enable_up_hotkey.';';

            if ($this->settings['use_javascript_navigation'] == true) {
                $file_list = '';
                $x = 0;

                $dir = $this->pathInfo($_GET['view'], 'dir_path');

                foreach ($this->vars['file_list'] as $file) {
                    $file_list .= "['".$this->escapeString($dir).'/'.$this->escapeString($file['file'])."', '".$this->escapeString($file['file'])."', ".$file['data'][0].', '.$file['data'][1].']';

                    if ($x < (count($this->vars['file_list']) - 1)) {
                        $file_list .= ',';
                    }

                    $x++;
                }

                echo "
	phppi.site_name = '".$this->settings['site_name']."';
	phppi.page_title = '".$this->vars['page_title']."';
	phppi.current_file = ".$this->vars['current_image_id'].';
	phppi.files = ['.$file_list.'];';
            }

            echo "</script>\n";
        }

        if ($this->settings['use_popup_image_viewer'] == true) {
            echo "<script type=\"text/javascript\" src=\"phppi/scripts/fancybox/jquery.fancybox.js\"></script>\n";
            if ($this->settings['show_thumbs_under_viewer'] == true) {
                echo "<script type=\"text/javascript\" src=\"phppi/scripts/fancybox/jquery.fancybox-thumbs.js\"></script>\n";
            }
            if ($this->settings['enable_mousewheel'] == true) {
                echo "<script type=\"text/javascript\" src=\"phppi/scripts/fancybox/jquery.mousewheel-3.0.6.pack.js\"></script>\n";
            }

            if ($this->settings['show_thumbs_under_viewer'] == true) {
                //Thumb Helper Version
                echo "<script type=\"text/javascript\">
	$(document).ready(function() {
		$('.fancybox-thumbs').fancybox({
			openEffect: '".$this->settings['open_image_animation']."',
			closeEffect: '".$this->settings['close_image_animation']."',
			prevEffect: '".$this->settings['nextprev_image_animation']."',
			nextEffect: '".$this->settings['nextprev_image_animation']."',
	
			closeBtn: false,
			arrows: false,
			nextClick: true,
	
			helpers: {
				thumbs: {
					width: ".$this->settings['popup_thumb_size'].',
					height: '.$this->settings['popup_thumb_size']."
				}
			}
		});
	});
</script>\n";
            } else {
                //Normal Version
                echo "<script type=\"text/javascript\">
	$(document).ready(function() {
		$('.fancybox').fancybox({
			openEffect: '".$this->settings['open_image_animation']."',
			closeEffect: '".$this->settings['close_image_animation']."',
			prevEffect: '".$this->settings['nextprev_image_animation']."',
			nextEffect: '".$this->settings['nextprev_image_animation']."'
		});
	});
</script>\n";
            }

            echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"phppi/scripts/fancybox/jquery.fancybox.css\">\n";
            if ($this->settings['show_thumbs_under_viewer'] == true) {
                echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"phppi/scripts/fancybox/jquery.fancybox-thumbs.css\">\n";
            }
        }

        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"phppi/css/global.css\">\n";
        echo '<link rel="stylesheet" type="text/css" href="phppi/themes/thumbnail/'.$this->settings['thumbnail_theme']."/style.css\">\n";
        echo '<link rel="stylesheet" type="text/css" href="'.$this->showThemeURL(1)."style.css\">\n";
    }

    public function initialize()
    {
        //Debug Mode
        if ($this->settings['debug_mode'] == true) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        ini_set('memory_limit', $this->settings['php_memory'].'M');

        //Set Thumb Size if changed
        if (isset($_POST['thumb_size'])) {
            if ($_POST['thumb_size'] == 0) {
                $this->setThumbSize('small');
            } elseif ($_POST['thumb_size'] == 1) {
                $this->setThumbSize('medium');
            } elseif ($_POST['thumb_size'] == 2) {
                $this->setThumbSize('large');
            }
        } else {
            $this->setThumbSize(null);
        }

        //GZIP Compression
        ini_set('zlib.output_compression', $this->settings['use_gzip_compression']);
        ini_set('zlib.output_compression_level', $this->settings['gzip_compression_level']);

        //Theme Mode
        $this->setThemeMode();

        if ($this->settings['allow_mobile_theme'] == true) {
            if (!is_file('phppi/themes/gallery/'.$this->settings['theme'].'/'.$this->vars['theme_mode'].'/template.php')) {
                $this->vars['theme_mode'] = 'standard';
            }
        } else {
            $this->vars['theme_mode'] = 'standard';
        }

        //Load Variables
        $this->loadVars();

        //Load Blacklists/Whitelists
        $this->loadLists();

        //Display Content
        if (isset($_GET['thumb'])) {
            //Show thumbnail only
            $this->genThumbnail($_GET['thumb']);

            exit;
        } elseif (isset($_GET['image'])) {
            //Show image
            if ($this->checkExploit('/'.$_GET['image']) == true) {
                $file_ext = strtolower($this->pathInfo($_GET['image'], 'file_ext'));

                if ($file_ext == 'jpg' or $file_ext == 'jpeg') {
                    $format = 'jpeg';
                } elseif ($file_ext == 'png') {
                    $format = 'png';
                } elseif ($file_ext == 'gif') {
                    $format = 'gif';
                }

                header('Content-length: '.filesize($this->vars['dir']['gallery'].'/'.$_GET['image']));
                header('Content-type: image/'.$format);
                readfile($this->vars['dir']['gallery'].'/'.$_GET['image']);
            } else {
                echo "File doesn't exist.";
            }

            exit;
        } elseif (isset($_GET['view'])) {
            //Show full image view
            $req_path = $this->pathInfo($_GET['view'], 'dir_path');

            if ($req_path !== '') {
                $req_path = '/'.$req_path;
            }

            if ($this->checkExploit($req_path) == true) {
                if (!$this->getDir($req_path.'/')) {
                    $this->vars['error'] = 'Folder doesn\'t exist';
                    $this->vars['page_title'] = 'Error';
                    $this->vars['page_requested'] = 'error';

                    $this->logs('access', 'add', 'Folder not found (/'.$_GET['view'].')');
                } elseif (!is_file($this->vars['dir']['gallery'].'/'.$_GET['view'])) {
                    $this->vars['error'] = 'File doesn\'t exist';
                    $this->vars['page_title'] = 'Error';
                    $this->vars['page_requested'] = 'error';

                    $this->logs('access', 'add', 'File not found (/'.$_GET['view'].')');
                } else {
                    for ($i = 0; $i < count($this->vars['file_list']); $i++) {
                        if ($this->vars['file_list'][$i]['file'] == $this->pathInfo($_GET['view'], 'full_file_name')) {
                            $this->vars['current_image_id'] = $i;
                            $this->vars['previous_image_id'] = null;
                            $this->vars['next_image_id'] = null;

                            if ($i > 0) {
                                $this->vars['previous_image_id'] = $i - 1;
                            }
                            if ($i < (count($this->vars['file_list']) - 1)) {
                                $this->vars['next_image_id'] = $i + 1;
                            }

                            break;
                        }
                    }

                    if ($this->settings['page_title_show_full_path'] == true) {
                        $this->vars['page_title'] = $this->settings['site_name'].' - '.str_replace('/', " \ ", $_GET['view']);
                    } else {
                        $this->vars['page_title'] = $this->settings['site_name'].' - '.$this->pathInfo($_GET['view'], 'full_file_name');
                    }
                    $this->vars['page_requested'] = 'image';

                    $this->logs('access', 'add', 'Viewed image (/'.$_GET['view'].')');
                }
            } else {
                $this->vars['error'] = 'File doesn\'t exist';
                $this->vars['page_title'] = 'Error';
                $this->vars['page_requested'] = 'error';

                $this->logs('access', 'add', 'Possible exploit attempt, blocked access (/'.$_GET['view'].')');
            }

            require 'phppi/themes/gallery/'.$this->settings['theme'].'/'.$this->vars['theme_mode'].'/template.php';

            if ($this->settings['debug_show_vars'] == true) {
                $this->outputVarsArray();
            }
            if ($this->settings['debug_show_settings'] == true) {
                $this->outputSettingsArray();
            }
        } else {
            //Show folder view
            if ($this->vars['dir']['req']['full'] == '') {
                $dir_req = '';
            } else {
                $dir_req = $this->vars['dir']['req']['full'].'/';
            }

            if ($this->vars['dir']['req']['full'] == '' || $this->checkExploit('/'.$this->vars['dir']['req']['full']) == true) {
                if (!$this->getDir($dir_req)) {
                    $this->vars['error'] = 'Folder doesn\'t exist';
                    $this->vars['page_title'] = 'Error';
                    $this->vars['page_requested'] = 'error';

                    $this->logs('access', 'add', 'Folder not found (/'.$dir_req.')');
                } else {
                    if ($this->settings['page_title_show_full_path'] == true) {
                        if ($this->vars['dir']['req']['full'] == '') {
                            $sep = '';
                        } else {
                            $sep = ' - ';
                        }
                        $this->vars['page_title'] = $this->settings['site_name'].$sep.str_replace('/', " \ ", $this->vars['dir']['req']['full']);
                    } else {
                        if ($this->vars['dir']['req']['full'] == '') {
                            $sep = '';
                        } else {
                            $sep = ' - ';
                        }
                        $this->vars['page_title'] = $this->settings['site_name'].$sep.$this->vars['dir']['req']['curr'];
                    }
                    $this->vars['page_requested'] = 'folder';

                    $this->logs('access', 'add', 'Viewed folder (/'.$dir_req.')');
                }
            } else {
                $this->vars['error'] = 'Folder doesn\'t exist';
                $this->vars['page_title'] = 'Error';
                $this->vars['page_requested'] = 'error';

                $this->logs('access', 'add', 'Folder not found or exploit attempt, blocked access (/'.$dir_req.')');
            }

            require 'phppi/themes/gallery/'.$this->settings['theme'].'/'.$this->vars['theme_mode'].'/template.php';

            if ($this->settings['debug_show_vars'] == true) {
                $this->outputVarsArray();
            }
            if ($this->settings['debug_show_settings'] == true) {
                $this->outputSettingsArray();
            }
        }
    }
}
