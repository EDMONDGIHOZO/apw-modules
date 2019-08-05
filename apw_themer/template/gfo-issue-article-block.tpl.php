<?php
////var_dump(views_embed_view('gfo_issue_article_listing', 'default', 19));
$article_node = menu_get_object();

$issue_node = node_load($article_node->field_article_issue_id['und'][0]['nid']);

$issue_date = $issue_node->field_issue_date['und'][0]['value'];

$issue_articles = _gfo_issue_articles($article_node->field_article_issue_id['und'][0]['nid']);

$download_links = _fetch_issue_downloads($issue_node->field_issue_number['und'][0]['value']);
$download_text = _build_gfo_issue_download_link($download_links);
?>

<div class="secondary-column">
    <div class="upper-column">

        <?php if (is_current_gfo_issue($issue_node->field_issue_number['und'][0]['value'])): ?>
            <h2><?php print t('GFO Current Issue') ?></h2>
        <?php endif; ?>

            <p>
                <span class="issue">
                    <?php print t('Issue') ?> <?php print $issue_node->field_issue_number['und'][0]['value']; ?>
                </span>
                <span class="date">
                <?php print format_date(strtotime($issue_date), 'custom', 'j F Y', variable_get('date_default_timezone', 0)); ?>
            </span>
            <span style="padding-top: 10px;"><?php print $download_text; ?></span>
        </p>
    </div>
    <div class="lower-column">
        <h3><?php print t('Contents') ?></h3>

        <?php foreach ($issue_articles as $article): ?>
                    <h4 class="category"><?php print $article[5] . '. ' . $article[1] ?></h4>
                    <h5 class="title"><?php print l(t($article[2]), 'node/' . $article[0]) ?></h5>
        <?php print t($article[4]) ?>
        <?php endforeach; ?>

    </div>
</div>
