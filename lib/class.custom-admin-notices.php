<?php

use k1sul1\custom_admin_notices\Settings;

class customAdminNotices {

  public $types = array("error", "warning", "info");

  public function __construct(){
    $this->setup();
    add_action("admin_notices", array($this, "checkBanners"));
  }

  public function setup(){

    register_post_type("custom_notice", array(
      "labels" => array(
        "name" => __("Notices", "custom-admin-notices"),
        "singular_name" =>  __("Notice", "custom-admin-notices")
      ),
      "public" => false,
      "has_archive" => false,
      "show_ui" => true,
      "menu_position" => 80
    ));

    add_action("add_meta_boxes", array($this, "renderMetabox"));
    add_action("save_post", array($this, "saveMeta"));
    add_action("wp_ajax_notice_dismissed", array($this, "ajaxDismiss"));
  }

  public function renderMetabox(){
    return add_meta_box(
        "custom_notice_settings",
        __("Notice settings", "custom-admin-notices"),
        array($this, "renderMetaboxOptions"),
        "custom_notice",
        "side",
        "high"
    );
  }

  public function renderMetaboxOptions(){
    global $post;

    $noticeType = get_post_meta($post->ID, "can_type", true);
    $dismissible = get_post_meta($post->ID, "can_dismissible", true);
    $environment = get_post_meta($post->ID, "can_environment", true);

    $nonce = wp_create_nonce(plugin_basename(__FILE__));

    $options = Settings\getFieldValues(true, "default");

    if(defined('WP_DEBUG') && WP_DEBUG){
      var_dump($options);
    }

    echo "<input type='hidden' name='can_noncename' value='$nonce'>";

    echo "<p>" . __("Notice type", "custom-admin-notices") . "</p>";

    foreach($this->types as $type){
      $checked = checked($noticeType, $type, false);

      echo "<label>";
      echo "<input type='radio' name='can_type' value='$type' $checked>";
      echo ucfirst($type);
      echo "</label>";
      echo "<br>";
    }

    echo "<p>" . __("Is dismissible?", "custom-admin-notices") . "</p>";

    $checked =  checked($dismissible, "1", false);

    echo "<label>";
    echo "<input type='checkbox' name='can_dismissible' value='1' $checked>";
    echo __("Yes", "custom-admin-notices");
    echo "</label>";
    echo "<br>";


    if(isset($options["allow-environments"])){
      echo "<p>" . __("Show only when these criterias are met?", "custom-admin-notices") . "</p>";

      if($options["determine-environment"]){
        foreach(explode("\r\n", strtolower($options["environments"])) as $line){
          $checked = "";

          if(strpos($environment, $line) > -1){
            $checked = "checked='checked'";
          }

          echo "<label><input type='checkbox' name='can_environment[]' value='$line' $checked>";
          echo $line;
          echo "</label>";
          echo "<br>";
        }
      ?>

      <?php
      } else {
        $value = "";

        if(!empty($environment)){
          $value = "value='$environment'";
        }

        echo "<label>Match by URL:<br>";
        echo "<input type='text' name='can_environment' placeholder='example.dev' $value>";
        echo "</label><br>";
      }

    }
  }

  public function saveMeta($post_id){


      if(!$post_id){
        return false;
      }

      if(get_post_type($post_id) !== "custom_notice"){
        return $post_id;
      }

      $options = Settings\getFieldValues(true, "default");
      $allowed = true;
      $p = $_POST;

      foreach($p as $key => $value){
        if(strpos($key, "can_") !== 0){
          continue;
        }

        $p[$key] = stripslashes(strip_tags($value));
      }



      $nonce = !empty($p['can_noncename']) ? wp_verify_nonce($p['can_noncename'], plugin_basename(__FILE__)) : false;
      $can_edit = current_user_can('edit_post', $post_id);

      $dismissible = !empty($p['can_dismissible']) ? $p['can_dismissible'] : false;
      $is_dismissible = !empty($dismissible) ? 1 : 0;

      $type = !empty($p['can_type']) ? $p['can_type'] : false;
      $type_allowed = in_array($type, $this->types);

      $env = !empty($p['can_environment']) ? $p['can_environment'] : false;

      // No one got time for overly protective data validation.

      /*if($options['determine-environment']){
        // checkboxes
        $envs = explode("\r\n", strtolower($options["environments"]));
        foreach($env as $check){
          $env_allowed = in_array($check, $envs);
          if(!$env_allowed){
            // Some shady stuff going on.
            return false;
          }
        }
      } else {
        $env_allowed = strpos($options['environments'], $env) > -1 ? true : false;
      }

      if ($nonce || !$can_edit || !$type_allowed || !$env_allowed) {
        $allowed = false;
      }

      if(!$allowed){
        return false;
      }*/

      update_post_meta($post_id, "can_dismissible", $is_dismissible);
      update_post_meta($post_id, "can_type", $type);
      update_post_meta($post_id, "can_environment", $env);

      return $post_id;
  }

  public function ajaxDismiss(){
      $user = (int) $_POST['user_id'];
      $notice_id = (int) $_POST['notice_id'];


      $dismissed = get_user_meta($user, "dismissed_notices", true);
      $dismissed = empty($dismissed) ? array() : $dismissed;
      array_push($dismissed, $notice_id);
      $dismissed = array_unique($dismissed);

      update_user_meta($user, "dismissed_notices", $dismissed);
      wp_send_json(array("status" => "success", "notice_id" => $notice_id, "user" => $user));
  }

  public function checkBanners(){
    $arguments = array("post_type" => "custom_notice", "posts_per_page" => -1, "status" => "publish");
    $arguments = apply_filters("can_banner_arguments", $arguments);

    $options = Settings\getFieldValues(true, "default");

    $posts = get_posts($arguments);

    $current_user =  get_current_user_id();
    $script = <<<EOT
    <script>
      // We might as well just use $.post
      (function($){
        $(document).on("click", ".notice-dismiss", function(e){
          $.post(ajaxurl, {
            action: "notice_dismissed",
            user_id: $current_user,
            notice_id: $(e.target).parent().data("noticeid")
          }).done(function(response){
            // console.log(response);
          });
        });
      })(jQuery);

    </script>
EOT;

    echo $script;

    foreach($posts as $post){
      setup_postdata($post);
      $show_banner = true;

      $header = "<h2>" . apply_filters("the_title", $post->post_title) . "</h2>";
      $header = apply_filters("can_notice_title", $header, $post->ID);

      $text = apply_filters("the_content", $post->post_content);
      $text = apply_filters("can_notice_content", $text, $post->ID);

      $content = $header . $text;

      $is_dismissible = (int) get_post_meta($post->ID, "can_dismissible", true);
      $is_dismissible = apply_filters("can_is_dismissible", $is_dismissible, $post->ID);
      // Allow filtering the dismissible status. Basically allows to always enable dismissing or disable it, or per post.

      $type =  get_post_meta($post->ID, "can_type", true);

      $user_dismissed = get_user_meta(get_current_user_id(), "dismissed_notices", true);
      $user_dismissed = empty($user_dismissed) ? array() : $user_dismissed;
      $user_dismissed = in_array($post->ID, $user_dismissed);

      $env = get_post_meta($post->ID, "can_environment", true);

      if(isset($options["determine-environment"]) && isset($options["allow-environments"])){
        if(strpos($env, getenv("WP_ENV")) === -1){
          $show_banner = false;
        }
      } elseif(isset($options["allow-environments"])) {
        $pageurl = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $env = !empty($env) ? $env : 'not-empty-needle'; // FFS if your URL actually contains "not-empty-needle".
        if(!(strpos($pageurl, $env) > -1)){
          $show_banner = false;
        }
      }

      if(!$is_dismissible){
        $user_dismissed = false;
        // If the notice isn't dismissible, show it for all users even if they dismissed it.
      }

      if($show_banner && !$user_dismissed){
        $this->renderBanner($type, $content, $is_dismissible, $post->ID);
      }

    }
  }

  public function renderBanner($type, $content, $is_dismissible = true, $notice_id){
    $types = $this->types;

    if(!in_array($type, $types)){
      return false;
    }

    $class = "notice notice-$type ";

    if($is_dismissible){
      $class .= "is-dismissible ";
    }

    printf( '<div class="%1$s" data-noticeid="%2$s">%3$s</div>', $class, $notice_id, $content );
  }

}
