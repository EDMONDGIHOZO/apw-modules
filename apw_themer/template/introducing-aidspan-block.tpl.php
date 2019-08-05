<div id="header">
    <div id="logo">
        <?php global $theme; global $language; ?>

        <a href="<?php if('en' != $language->language) print '/' . $language->language ?>/node/2" title="Return to home page">
            <img src="<?php
            $base_path = base_path() . drupal_get_path('theme', $theme);
            $logo_path = '/images/sena/logo.png';
            if('fr' == $language->language)
                    $logo_path = '/images/sena/logo_fr.png';
            elseif('ru' == $language->language)
                    $logo_path = '/images/sena/logo_ru.png';
            elseif('es' == $language->language)
                    $logo_path = '/images/sena/logo_es.png';
            
            print $base_path . $logo_path;
            ?>" alt="Aidspan - Independent observer of the Global Fund"
            />
        </a>
    </div>
    <div id="socialmedia"><!-- AddThis Button BEGIN -->
        <div class="addthis_toolbox addthis_default_style" style="float:right; padding-left:10px;">
            <!--<a href="http://www.addthis.com/bookmark.php?v=250&amp;username=xa-4d2c2bef083e6065" class="addthis_button_compact">Share</a>
            <span class="addthis_separator">|</span>-->
            <span><a class="addthis_button_facebook"></a></span>
            <span><a class="addthis_button_twitter"></a></span>
            <a class="addthis_button_email"></a>
            <a class="addthis_button_linkedin"></a>
        </div>
        <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f73b2eb37b58154"></script>
        <!-- AddThis Button END -->

        <?php if (!$user->uid): ?>
        <?php
            echo l(t("Log In"), "user", array(
                'attributes' => array(
                    'class' => 'login',
                        'id' => 'loginanchor'
                )
            )); ?>
        <?php if (!$registration_enabled): ?>
        <?php
                echo l(t("Create An Account"), "user/register", array(
                    'attributes' => array(
                        'class' => 'login',
                        'id' => 'whatisthisanchor'
                    )
                ));
        ?>
                        <!--<span id="" class="login whatisthis" style="font-size: 9px;">What's this?</span>-->
        <?php endif; ?>
        <?php else: ?>
                    <span>
            <?php echo t("Welcome <strong>!user</strong>", array('!user' => l($user->name, "user"))); ?>&nbsp;|&nbsp;
            <?php echo l(t("MyAidspan"), "user/" . $user->uid . "/edit"); ?></span>&nbsp;|&nbsp;
                <span><?php echo l(t("Log out"), "logout"); ?></span>

        <?php endif; ?>

        <?php if ($feed_icons): ?>
                        <span><a href="<?php echo url("rss.xml"); ?>"><img src="<?php echo base_path() . path_to_theme() ?>/images/rss.png"  alt="RSS" /></a></span>
        <?php endif; ?>
                        <!--<span><?php echo l(t("sitemap"), "sitemap"); ?></span>-->
    </div>
</div>
<script type="text/javascript">
    <?php
    print "var loginText = '" . t("You are required to create an account and log in to access some services on this website. These services include commenting on published articles and starting a new subscription to the GFO newsletter.") . "';";
    ?>
        
    $("#whatisthisanchor").qtip({
        content: loginText,
        style: {
            name: "light" // Inherit from preset style
        }
    });
    $("#loginanchor").qtip({
        content: loginText,
        style: {
            name: "light" // Inherit from preset style
        }
    });
</script>
