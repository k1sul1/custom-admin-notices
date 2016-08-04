<?php
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

    add_action("add_meta_boxes", array($this, "drawOptions"));
    add_action("save_post", array($this, "saveOptions"));
    add_action("wp_ajax_notice_dismissed", array($this, "ajaxDismiss"));
  }

  private function drawMetaOptions(){
    add_meta_box(
        "custom_notice_settings",
        __("Notice settings", "custom-admin-notices"),
        array($this, "drawOptions"),
        "custom_notice",
        "side",
        "high"
    );
  }

  private function drawOptions(){
    global $post;
    $current_type = get_post_meta($post->ID, "custom-admin-notices-type", true);
    $nonce = wp_create_nonce(plugin_basename(__FILE__));
    ?>
    <input type='hidden' name='custom-admin-notices_noncename' value='<?php echo $nonce; ?>' />
    <p><?php echo __("Notice type", "custom-admin-notices"); ?></p>
    <label><input type="radio" name="custom-admin-notices-type" value="error" <?php checked($current_type, "error"); ?>> <?php echo __("Error", "custom-admin-notices"); ?></label><br>
    <label><input type="radio" name="custom-admin-notices-type" value="warning" <?php checked($current_type, "warning"); ?>> <?php echo __("Warning", "custom-admin-notices"); ?></label><br>
    <label><input type="radio" name="custom-admin-notices-type" value="info" <?php checked($current_type, "info"); ?>> <?php echo __("Info", "custom-admin-notices"); ?></label><br>
    <p><?php echo __("Is dismissible?", "custom-admin-notices"); ?></p>
    <label><input type="checkbox" name="custom-admin-notices-dismissible" value="1" <?php checked(get_post_meta($post->ID, "custom-admin-notices-dismissible", true), "1"); ?>> <?php echo __("Yes", "custom-admin-notices"); ?></label><br>

    <?php
  }

  private function saveOptions(){
      $nonce = !empty($_POST['custom-admin-notices_noncename']) ? $_POST['custom-admin-notices_noncename'] : false;
      $dismissible = !empty($_POST['custom-admin-notices-dismissible']) ? $_POST['custom-admin-notices-dismissible'] : false;
      $type = !empty($_POST["custom-admin-notices-type"]) ? $_POST["custom-admin-notices-type"] : false;

      if (!wp_verify_nonce($nonce, plugin_basename(__FILE__))) {
        return $post_id;
      }

      if (!current_user_can('edit_post', $post_id)){
        return $post_id;
      }

      if(!in_array($type, $this->types)){
        return $post_id;
      }

      $is_dismissible = !empty($dismissible) ? 1 : 0;

      update_post_meta($post_id, "custom-admin-notices-dismissible", $is_dismissible);
      update_post_meta($post_id, "custom-admin-notices-type", $type);
      return $post_id;
  }

  private function ajaxDismiss(){
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
    $arguments = apply_filters("custom-admin-notices_banner_arguments", $arguments);

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

      $header = "<h2>" . apply_filters("the_title", $post->post_title) . "</h2>";
      $header = apply_filters("custom-admin-notices_notice_title", $header, $post->ID);

      $text = apply_filters("the_content", $post->post_content);
      $text = apply_filters("custom-admin-notices_notice_content", $text, $post->ID);

      $content = $header . $text;

      $is_dismissible = (int) get_post_meta($post->ID, "custom-admin-notices-dismissible", true);
      $is_dismissible = apply_filters("custom-admin-notices-is_dismissible", $is_dismissible, $post->ID);
      // Allow filtering the dismissible status. Basically allows to always enable dismissing or disable it, or per post.

      $type =  get_post_meta($post->ID, "custom-admin-notices-type", true);

      $user_dismissed = get_user_meta(get_current_user_id(), "dismissed_notices", true);
      $user_dismissed = empty($user_dismissed) ? array() : $user_dismissed;
      $user_dismissed = in_array($post->ID, $user_dismissed);


      if(!$is_dismissible){
        $user_dismissed = false;
        // If the notice isn't dismissible, show it for all users even if they dismissed it.
      }

      if(!$user_dismissed){
        $this->createBanner($type, $content, $is_dismissible, $post->ID);
      }

    }
  }

  public function createBanner($type, $content, $is_dismissible = true, $notice_id){
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
