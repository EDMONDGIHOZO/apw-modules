<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Common mappings for the Drupal 6 node migrations.
 */
abstract class BaseNodeMigration extends DrupalNode6Migration {


  /**
   * Translation from field names assigned when executing the query to our
   * subfield notation.
   *
   * @var array
   *  key: DB-compatible name (e.g., field_buy_link_title).
   *  value: Subfield notation (e.g., field_buy_link:title).
   */
  protected $fixFieldNames = array();

  /**
   * Keep track of any file field data columns we'll need to unpack in
   * prepareRow().
   *
   * @var array
   */
  protected $fileDataFields = array();

  /**
   * @param array $arguments
   */
  public function __construct(array $arguments) {
    parent::__construct($arguments);
     
    // comment to update  entries
    $this->addFieldMapping('is_new')->defaultValue(TRUE); 
    $this->addFieldMapping('nid', 'nid');
    $this->addFieldMapping('title', 'title');
  
    $query = $this->query();

    $this->sourceOptions['fix_field_names'] = $this->fixFieldNames;

    $this->source = new MigrateDrupal6SourceSQL($query, $this->sourceFields, NULL,
      $this->sourceOptions);

    $this->addFieldMapping('language', 'language')
         ->defaultValue($this->defaultLanguage);
    $this->addFieldMapping('body:language', 'language', FALSE)
         ->defaultValue($this->defaultLanguage);

    if (field_info_instance('node', 'body', $this->destinationType)) {
      $this->addFieldMapping('body:summary', 'teaser');
      $this->addFieldMapping('body:format', 'format')
           ->callbacks(array($this, 'mapFormat'));
    }
    else {
      $this->addUnmigratedSources(array('teaser', 'format'));
    }

    $this->addFieldMapping(NULL, 'moderate');

    $this->addSimpleMappings(array('tnid', 'translate'));
  }

  /**
   * Query for basic node fields from Drupal 6.
   *
   * @return QueryConditionInterface
   */
  protected function query() {
    $query = Database::getConnection('default', $this->sourceConnection)
             ->select('node', 'n')
             ->fields('n', array('nid', 'vid', 'language', 'title',
                 'uid', 'status', 'created', 'changed', 'comment', 'promote',
                 'moderate', 'sticky', 'tnid', 'translate'))
             ->condition('n.type', $this->sourceType)
             ->orderBy('n.changed');
    $query->innerJoin('node_revisions', 'nr', 'n.vid=nr.vid');
    $query->fields('nr', array('body', 'teaser', 'format'));
    // Pick up simple CCK fields
    $cck_table = 'content_type_' . $this->sourceType;
    if (Database::getConnection('default', $this->sourceConnection)
          ->schema()->tableExists($cck_table)) {
      $query->leftJoin($cck_table, 'f', 'n.vid=f.vid');
      // The main column for the field should be rendered with
      // the field name, not the column name (e.g., field_foo rather
      // than field_foo_value).
      $field_info = $this->version->getSourceFieldInfo();
      foreach ($field_info as $field_name => $info) {
        if (isset($info['columns']) && !$info['multiple'] && $info['db_storage']) {
          $i = 0;
          $data = FALSE;
          foreach ($info['columns'] as $display_name => $column_name) {
            if ($i++ == 0) {
              $query->addField('f', $column_name, $field_name);
            }
            else {
              // The database API won't allow colons in column aliases, so we
              // will accept the default alias, and fix up the field names later.
              // Remember how to translate the field names.
              $clean_name = str_replace(':', '_', $display_name);
              $this->fixFieldNames[$clean_name] = $display_name;
              if ($info['type'] == 'filefield' &&
                  (strpos($display_name, ':list') || strpos($display_name, ':description') || strpos($display_name, ':alt') || strpos($display_name, ':title'))) {
                if (!$data) {
                  $this->fileDataFields[] = $field_name . '_data';
                  $query->addField('f', $field_name . '_data');
                  $data = TRUE;
                }
              }
              else {
                $query->addField('f', $column_name);
              }
            }
          }
        }
      }
    }
    // Join node_counter for Statistics support
    if (Database::getConnection('default', $this->sourceConnection)
        ->schema()->tableExists('node_counter')) {
      $query->leftJoin('node_counter', 'nc', 'n.nid=nc.nid');
      $query->addField('nc', 'daycount');
      $query->addField('nc', 'timestamp');
      $query->addField('nc', 'totalcount');
    }
    return $query;
  }

  public function prepareRow($row) {
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }

    // The property 'tnid' cannot be handled via the sourceMigration() method
    // because it might be 0 or the main node of translation set. We don't want
    // to create a stub for such cases.
    if (!empty($row->tnid)) {
      $destination_ids = $this->getMap()->lookupDestinationID(array($row->tnid));
      // There's no destination yet. Create a stub.
      if (empty($destination_ids)) {
        // Don't create stub for itself.
        if ($row->tnid != $row->nid) {
          // Check if 'tnid' is a node in the source set to prevent not
          // updatable stubs.
          $query = clone $this->query();
          $query->condition('n.nid', $row->tnid);
          $nid = $query->execute()->fetchField();
          unset($query);
          if ($nid) {
            if ($tnids = $this->createStub(NULL)) {
              // Save the mapping.
              $this->map->saveIDMapping((object) array('nid' => $row->tnid), $tnids, MigrateMap::STATUS_NEEDS_UPDATE, $this->defaultRollbackAction);
              $row->tnid = reset($tnids);
            }
          }
        }
        else {
          $row->tnid = 0;
          $row->_is_translation_source = TRUE;
        }
      }
      else {
        $row->tnid = $destination_ids['destid1'];
      }
    }

    foreach ($this->fileDataFields as $data_field) {
      if (isset($row->$data_field)) {
        $data = unserialize($row->$data_field);
        $base_field = substr($data_field, 0, strpos($data_field, '_data'));
        foreach ($data as $key => $value) {
          $field_name = $base_field . '_' . $key;
          $row->$field_name = $value;
        }
        unset($row->$data_field);
      }
    }
    // Convert the default field names to the nice-looking ones.
    foreach ($this->fixFieldNames as $clean => $display) {
      if (isset($row->$clean)) {
        $row->$display = $row->$clean;
        unset($row->$clean);
      }
    }

    // Don't populate summary if the teaser matches the generated summary.
    if (empty($row->teaser) || $row->teaser == text_summary($row->body)) {
      $row->teaser = '';
    }
  }

  public function complete($node, stdClass $row) {
    if (empty($row->_is_translation_source)) {
      return;
    }

    db_update('node')
      ->fields(array('tnid' => $node->nid))
      ->condition('nid', $node->nid)
      ->execute();
  }
}

class PageNodeMigrationMapper extends BaseNodeMigration {

  public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addFieldMapping('body', 'body');
  }

}
class MenuMigrationMapper extends DrupalMenuLinks6Migration{
  
    public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addFieldMapping('mlid', 'mlid');
  }
}
class GFOUserMigrationMapper extends DrupalUser6Migration {

  public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addFieldMapping('is_new')->defaultValue(TRUE);
    $this->addFieldMapping('uid', 'uid');
  }

}

/**
 * Description of GFOIssueMigration
 *
 * @author bobby
 */
class GFOIssueMigrationMapper extends BaseNodeMigration {

  public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addSimpleMappings(array('field_issue_number', 'field_issue_note'));
    $this->addFieldMapping('field_issue_note:format', 'field_issue_note:format')
        ->callbacks(array($this, 'mapFormat'));
    $this->addFieldMapping('field_issue_date:to', 'field_issue_date');
    $this->addfieldMapping('field_issue_note:language')->defaultValue('und');
    $this->addFieldMapping('field_issue_date', 'field_issue_date');
    $this->addfieldMapping('field_issue_date:rrule')->defaultValue('null');
    $this->addFieldMapping('field_issue_date:timezone', 'field_issue_date:timezone')
        ->arguments(array('timezone' => 'Africa/Nairobi'));
  }

}

class GFOArticleMigrationMapper extends BaseNodeMigration {

  public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addFieldMapping('field_article_issue_id', 'field_article_issue_id');
    $this->addFieldMapping('field_article_number', 'field_article_number');
    $this->addFieldMapping('field_article_type_id', 'field_article_type_id');
    $this->addfieldMapping('field_article_secondary_title', 'field_article_secondary_title');
    $this->addfieldMapping('field_article_secondary_title:language')->defaultValue('und');
    $this->addFieldMapping('field_article_tertiary_title', 'field_article_tertiary_title');
    $this->addfieldMapping('field_article_tertiary_title:language')->defaultValue('und');
    $this->addFieldMapping('field_article_author', 'field_article_author');
    $this->addfieldMapping('field_article_author:language')->defaultValue('und');
    $this->addFieldMapping('field_article_abstract', 'field_article_abstract');
    $this->addfieldMapping('field_article_abstract:language')->defaultValue('und');
    $this->addFieldMapping('field_article_abstract:format', 'field_article_abstract:format')
        ->callbacks(array($this, 'mapFormat'));
    $this->addFieldMapping('field_article_content', 'field_article_content');
    $this->addfieldMapping('field_article_content:language')->defaultValue('und');
    $this->addFieldMapping('field_article_content:format', 'field_article_content:format')
        ->callbacks(array($this, 'mapFormat'));
    $this->addFieldMapping('field_article_omit_from_nl_live', 'field_article_omit_from_nl_live');
    $this->addFieldMapping('field_article_archive', 'field_article_archive');
  }

}

class RelatedNewsMigrationMapper extends BaseNodeMigration {

  public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addFieldMapping('field_news_submit_date', 'field_news_submit_date');
    $this->addFieldMapping('field_news_origin', 'field_news_origin');
    $this->addFieldMapping('field_news_submit_by', 'field_news_submit_by');
    $this->addfieldMapping('field_news_link', 'field_news_link');
    $this->addFieldMapping('field_news_excerpt', 'field_news_excerpt');
    $this->addFieldMapping('field_news_excerpt:format', 'field_news_excerpt:format')
        ->callbacks(array($this, 'mapFormat'));
    $this->addFieldMapping('field_news_excerpt:language')->defaultValue('und');
    $this->addFieldMapping('field_news_link:title', 'field_news_link:title');
    $this->addFieldMapping('field_news_link:attributes', 'field_news_link:attributes');
    $this->addFieldMapping('field_news_link:language')->defaultValue('und');
    $this->addFieldMapping('field_news_origin:language')->defaultValue('und');
    $this->addFieldMapping('field_news_submit_by:language')->defaultValue('und');
    $this->addfieldMapping('field_news_submit_date:rrule')->defaultValue('null');
    $this->addFieldMapping('field_news_submit_date:to', 'field_news_submit_date');
    $this->addFieldMapping('field_news_submit_date:timezone', 'field_news_submit_date:timezone')
        ->arguments(array('timezone' => 'Africa/Nairobi'));
    $this->addFieldMapping(NULL, 'vid')
        ->issueGroup(t('DNM'));
  }

}

class PublicationsMigrationMapper extends BaseNodeMigration {

  protected $baseDir;

  public function prepareRow($row) {
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }
    $row->file_uri = $this->baseDir . $row->sourceid;
    // Remove the leading forward slash.
    $row->destination_file = substr($row->sourceid, 1);
  }

  public function __construct(array $arguments) {
    parent::__construct($arguments);
    $this->addFieldMapping('field_publication_type_id', 'field_publication_type_id');
    $this->addFieldMapping('field_publication_author', 'field_publication_author');
    $this->addFieldMapping('field_publication_date', 'field_publication_date');
    $this->addfieldMapping('field_publication_description', 'field_publication_description');
    $this->addFieldMapping('field_guide_category_id', 'field_guide_category_id');
    $this->addFieldMapping('field_publication_file', 'field_publication_file');
    $this->addfieldMapping('field_publication_date:rrule')->defaultValue('null');
    $this->addFieldMapping('field_publication_date:to', 'field_publication_date');
    $this->addFieldMapping('field_publication_date:timezone', 'field_publication_date:timezone')
        ->arguments(array('timezone' => 'Africa/Nairobi'));
    $this->addFieldMapping('field_publication_author::language')->defaultValue('und');
    $this->addFieldMapping('field_publication_description:language')->defaultValue('und');
    $this->addFieldMapping('field_publication_file:language')->defaultValue('und');
    $this->addFieldMapping('field_publication_file:display')->defaultValue(1);
    $this->addFieldMapping('field_publication_description:format', 'field_publication_description:format')
        ->callbacks(array($this, 'mapFormat'));
    $this->addFieldMapping('field_publication_file:file_class')
        ->defaultValue('MigrateFileFid');
    $this->addFieldMapping('field_publication_author:language')->defaultValue('und');
    $this->addFieldMapping('field_publication_file:description', 'field_publication_file:data');
    $this->addFieldMapping('field_publication_file:destination_dir')
        ->defaultValue('/home/bobby/aidspan_projects/php/apw2/sites/default/files/publications');
    $this->addFieldMapping('field_publication_file:destination_file', 'field_publication_file:list')
        ->separator(',')
        ->arguments(array('source_type' => 'file'));
  }

}
