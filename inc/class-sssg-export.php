<?php

class SSSG_Export {

    private $options;
    private $site_url;
    private $paths = [];
    private $files = [];

    public function __construct() {
        add_action('save_post', array($this, 'export_post'));
        add_action('save_page', array($this, 'export_page'));
        add_action('edit_page', array($this, 'export_page'));
        add_action('publish_page', array($this, 'export_page'));
        add_action('upgrader_process_complete', array($this, 'on_upgrader_process_complete'), 10, 2);
        add_action('after_switch_theme', array($this, 'on_after_switch_theme'));
        $this->options = get_option('sssg_general');
        $this->site_url = get_option('siteurl');
    }

    public function export_post($post_id) {
        // When a post is saved, if automatic export is enabled,
        // call the function to export the post...
    }

    public function export_page($page_id) {
        // When a post is saved, if automatic export is enabled,
        // call the function to export the post...
        $page = get_post($page_id);
        $additional_files = explode("\n", $this->options['additional_files']);

        if (count($additional_files)){
            foreach ($additional_files as $path){
                $path = trim($path);
                if ($path){
                    $this->add_path($path);
                }
            }
        }

        $link = $this->export_link(get_permalink($page_id));
        $code = $this->get_html_content($link);
        $this->copy_static_files('wp-content',$code);
        $this->copy_static_files('wp-includes',$code);
        $code = $this->html_replace('wp-content',$code);
        $code = $this->html_replace('wp-includes',$code);
        if ($page_id == get_option('page_on_front')){
            $file_name = '/index';
        } else {
            $file_name = $page->post_name;
        }
        $link_path = explode($page->post_name, explode($this->site_url, get_permalink($page_id))[1])[0];
        $this->put_file($link_path.$file_name.'.html', $code);
    }

    public function on_upgrader_process_complete($upgrader_object, $options) {
        // When a plugin is updated, if automatic export is enabled,
        // call the function to check if any static files have been updated...
    }

    public function on_after_switch_theme() {
        // When a theme is switched, if automatic export is enabled,
        // call the function to export the entire site...
    }

    private function export_link($link) {
        if (strpos('?',$link) !== false) {
            $link .= '&';
        } else {
            $link .= '?';
        }
        $link.= 'sssg_export=true';
        return $link;
    }

    private function copy_static_files($folder,$code,$file=false) {
        // Copy all the static files to the static folder...
        $content_path = $this->site_url.'/'.$folder;
        $assets_folder = $this->options['assets_path'];

        $pre = explode($folder, $code);
        array_shift($pre);

        foreach ($pre as $post){
            $path = explode(')',explode('\'',explode('"', $post)[0])[0])[0];
            $this->add_path($folder.'/'.$path);
        }

        if ($file){
            if (strpos($code, 'sourceMappingURL') !== false){
                $this->paths[$folder][] = substr($file,strlen($assets_folder)+1).'.map';
            }
            $pre = explode('url(./', $code);
            $url_folder = $this->url_folder($file);
            foreach ($pre as $post){
                $this->add_path($url_folder.explode('\'',explode('"', $post)[0])[0]);
            }
            $pre = explode('url(../', $code);
            $url_folder = $this->url_folder($this->url_folder($file));
            foreach ($pre as $post){
                $this->add_path($url_folder.explode('\'',explode('"', $post)[0])[0]);
            }
        }

        foreach ($this->paths[$folder] as $path) {
            if (!in_array($folder.'||'.$path, $this->files)){
                $file_path = $content_path.'/'.$path;
                $file_content = $this->get_html_content($file_path);
                $new_path = $assets_folder.'/'.$path;
                $ext = $this->file_extension($new_path);
                $this->files[]=$folder.'||'.$path;
                if ($file_content && strlen($ext)<=5){
                    if (in_array($ext, ['css','js'])){
                        $this->copy_static_files($folder,$file_content, $new_path);
                        $file_content = $this->html_replace($folder,$file_content);
                    }
                    $this->put_file($new_path, $file_content);
                }
            }
        }
    }

    private function html_replace($folder,$code) {
        // Replace all the URLs in the HTML with the static URLs...
        $site_url = get_option('siteurl');

        // Replace escaped urls
        $escaped_site_url = str_replace('/', '\/', $site_url).'/'.$folder;
        $escaped_base_url = str_replace('/', '\/', $this->options['base_url'].'/'.$this->options['assets_path']);
        $code = str_replace($escaped_site_url, $escaped_base_url, $code);
        
        // Replace normal urls
        $code = str_replace($site_url.'/'.$folder, $this->options['base_url'].'/'.$this->options['assets_path'], $code);
        
        // Replace non-protocol urls
        $non_protocol_site_url = $this->non_protocol_url($site_url).'/'.$folder;
        $non_protocol_base_url = $this->non_protocol_url($this->options['base_url']).'/'.$this->options['assets_path'];
        $code = str_replace($non_protocol_site_url, $non_protocol_base_url, $code);

        // Replace only folder
        $code = str_replace('"'.$folder, '"'.$this->options['assets_path'], $code);
        $code = str_replace("'".$folder, "'".$this->options['assets_path'], $code);
        $code = str_replace('"/'.$folder, '"/'.$this->options['assets_path'], $code);
        $code = str_replace("'/".$folder, "'/".$this->options['assets_path'], $code);

        // Replace only site url
        $code = str_replace($site_url.'"', $this->options['base_url'].'"', $code);
        $code = str_replace($site_url."'", $this->options['base_url']."'", $code);
        $code = str_replace($site_url.'/"', $this->options['base_url'].'/"', $code);
        $code = str_replace($site_url."/'", $this->options['base_url']."/'", $code);
        $code = str_replace($site_url, $this->options['base_url'], $code);
        $code = str_replace($this->options['base_url'].'/'.$folder, $this->options['base_url'].'/'.$this->options['assets_path'], $code);
        
        return $code;
    }

    private function put_file($filename, $content) {
        if (in_array($filename, $this->files)){
            return false;
        }
        $base_path = explode('wp-content',__DIR__)[0].'wp-content';
        $parent_folder = $this->options['base_path'];
        $file_path = $parent_folder . '/' . $filename;
        $full_path = $base_path.'/'.$file_path;
        
        // Crear carpetas necesarias en la ruta si no existen
        $folders = explode('/', $file_path);
        array_pop($folders);
        $current_path = $base_path;
        foreach ($folders as $folder) {
            $current_path .= '/' . $folder;
            if (!is_dir($current_path)) {
                mkdir($current_path);
            }
        }
        
        // Copiar o crear el archivo
        if ($content) {
            if (file_exists($full_path) && file_get_contents($full_path) == $content) {
                return false;
            }
            file_put_contents($full_path, $content);
        }
    }

    private function get_html_content($url) {
        ob_start();
        include_once(ABSPATH . WPINC . '/class-wp-http.php');
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $code_content = wp_remote_retrieve_body($response);
        ob_end_clean();
        
        return $code_content;
    }

    private function url_folder($url){
        $url = explode($this->site_url, $url);
        if (count($url) == 1){
            $url = $url[0];
        } else {
            $url = $url[1];
        }
        $url = explode('/', $url);
        array_pop($url);
        return implode('/', $url);
    }
    private function file_extension($file){
        $file = explode('.', $file);
        return end($file);
    }

    private function file_root($file){
        if (strpos($file,'/') === false){
            return $file;
        }
        $file = explode('/', $file);
        while (!$res = array_shift($file)) continue;
        return $res;
    }

    private function non_protocol_url($url){
        return str_replace(['http://','https://'], '//', $url);
    }

    private function add_path($path){
        $multiple = explode(',', $path);
        if (count($multiple)>1){
            foreach ($multiple as $path){
                $this->add_path(trim(explode(' ',$path)[0]));
            }
            return;
        }
        $root = $this->file_root($path);
        if (!$root){
            return;
        }
        $path = str_replace([' ','//'], ['?','/'], $path);
        if (strpos($path, '/') === 0){
            $path = substr($path, 1);
        }
        $path = explode('?',$path)[0];
        $path = explode('#',$path)[0];
        if (!isset($this->paths[$root])){
            $this->paths[$root] = [];
        }
        $path = explode($root, $path);
        if (count($path)>1){
            $path = $path[1];
        } else {
            $path = $path[0];
        }
        if (strpos($path, '/') === 0){
            $path = substr($path, 1);
        }
        if (!in_array($path, $this->paths) && strlen($path) < 255){
            $this->paths[$root][] = $path;
        }
    }
}


// add_action('save_post', 'static_export_site'); // Ejecuta la exportación estática al guardar una publicación
// add_action('edit_post', 'static_export_site'); // Ejecuta la exportación estática al editar una publicación
// add_action('publish_post', 'static_export_site'); // Ejecuta la exportación estática al publicar una publicación
// add_action('edit_page', 'static_export_site'); // Ejecuta la exportación estática al editar una página
// add_action('publish_page', 'static_export_site'); // Ejecuta la exportación estática al publicar una página