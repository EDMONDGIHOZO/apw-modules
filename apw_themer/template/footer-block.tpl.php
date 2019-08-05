<!-- start footer -->
<?php global $language; ?>

<div id="footer">
	<?php print '<div id="copyright">Copyright © 2002-' . date('Y') . ' Aidspan.</div>' ?>
    <div id="lower_navigation">
    	<ul>
            <li class="first"><a href="/"><?php print t('Home') ?></a></li>
            <li><a href="<?php if('en' != $language->language) print '/' . $language->language ?>/sitemap"><?php print t('Sitemap') ?></a></li>
            <li><?php print l(t('Privacy Policy'), 'node/' . _translation_nodelink(938) ) ?></li>
            <li class="last"><a href="<?php if('en' != $language->language) print '/' . $language->language ?>/contact"><?php print t('Contact') ?></a></li>
        </ul>
    </div>
</div>
<!-- start footer -->