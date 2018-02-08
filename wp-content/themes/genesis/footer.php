<?php wp_footer(); ?>
<?php $pods = pods('theme_settings'); ?>
<footer class="centered border" data-background="<?php echo $pods->field('footer_image.guid'); ?>">
    <img id="footerImage" src="<?php echo $pods->field('footer_image.guid'); ?>" alt="footer image"/>
    <?php $pods = pods('social'); ?>
    <nav class="socialMenu">
                <ul>
                    <li><a class="mail" href="mailto:<?php echo $pods->field('email'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/mail-icon-lg.png" alt="email us" data-alternative="<?php echo get_template_directory_uri(); ?>/images/mail-icon-sm-@2x.png"/></a></li>
                    <li><a class="phone" href="tel:<?php echo $pods->field('phone'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/tel-icon-lg.png" alt="call us" data-alternative="<?php echo get_template_directory_uri(); ?>/images/tel-icon-sm-@2x.png"/></a></li>
                    <li><a class="twit" href="<?php echo $pods->field('twitter'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/twit-icon-lg.png" alt="Find us on Twitter" data-alternative="<?php echo get_template_directory_uri(); ?>/images/twit-icon-sm-@2x.png"/></a></li>
                    <li><a class="lin" href="<?php echo $pods->field('linkedin'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/lin-icon-lg.png" alt="Find us on LinkedIn" data-alternative="<?php echo get_template_directory_uri(); ?>/images/lin-icon-sm-@2x.png"/></a></li>
                    <li><a class="g" href="<?php echo $pods->field('g'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/g-icon-lg.png" alt="Find us on Google+" data-alternative="<?php echo get_template_directory_uri(); ?>/images/g-icon-sm-@2x.png"/></a></li>
                </ul>
            </nav>
    <address><a href="tel:<?php  echo $pods->field('phone'); ?>"><?php  echo $pods->field('phone'); ?></a><a href="mailto:<?php  echo $pods->field('email'); ?>"><?php  echo $pods->field('email'); ?></a>110 Denman Rd Cranford, NJ</address>
</footer>

</body>
</html>
