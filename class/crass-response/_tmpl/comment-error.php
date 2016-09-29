<?php get_template_part('templates/head'); ?>
<body <?php body_class(); ?>>

  <!--[if lt IE 7]><div class="alert">Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</div><![endif]-->

  <?php
    // Use Bootstrap's navbar if enabled in config.php
    if (current_theme_supports('bootstrap-top-navbar')) {
      get_template_part('templates/header-top-navbar');
    } else {
      get_template_part('templates/header');
    }
  ?>

  <div id="wrap" class="container" role="document">
    <div id="content" class="row">
      <div id="main" class="<?php roots_main_class(); ?>" role="main">
        
        <!-- error message -->
        <h2>Oops!</h2>
        <div class="alert alert-warn">
          
          <p>
          <?php echo $message['text']; ?>
          </p>
        
          <p>
            <a href="<?php echo $message['link']; ?>">Use this link to return to the post and try again.</a>
          </p>
        
          <p>
            <strong>Note:</strong> Please do not use your browser's back button (use the link above).
          </p>
        
        </div><!-- /error message -->
        
      </div>
    </div><!-- /#content -->
  </div><!-- /#wrap -->

  <?php get_template_part('templates/footer'); ?>

</body>
</html>
