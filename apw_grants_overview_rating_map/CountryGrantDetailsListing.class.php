<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CountryGrantDetailsListing
 *
 * @author bobby
 */
class CountryGrantDetailsListing {

  //put your code here

  function country_grant_amount($start_date = null, $end_date = null) {

    $map_data = 'var dataC = {
    "countries": [';
    //Approved funding for the countries
    $sql = "SELECT c.id, c.name as name, c.fc_id, c.iso_code_2, SUM(ga.agreement_amount) as agreement_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN grant_agreement ga ON ga.gf_grant_id = gg.id
GROUP BY c.name
ORDER BY name";

    $result = db_query($sql);
$country_grant_info=  array();

    foreach ($result as $country) {
      //get the disbursement for the current country
      $sql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id = " . $country->id;

      $dc1sql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id =" . $country->id . " and gg.disease_component_id=1";

      $dc2sql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id =" . $country->id . " and gg.disease_component_id=2";

      $dc3sql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id =" . $country->id . " and gg.disease_component_id=3";

      $dc4sql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id =" . $country->id . " and gg.disease_component_id=4";

      $dc6sql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id =" . $country->id . " and gg.disease_component_id=6";


      $disbursed_result = db_query($sql);
      $data = $disbursed_result->fetchObject();
      $disbursed_result_dc1 = db_query($dc1sql);
      $data_dc1 = $disbursed_result_dc1->fetchObject();
      $disbursed_result_dc2 = db_query($dc2sql);
      $data_dc2 = $disbursed_result_dc2->fetchObject();
      $disbursed_result_dc3 = db_query($dc3sql);
      $data_dc3 = $disbursed_result_dc3->fetchObject();
      $disbursed_result_dc4 = db_query($dc4sql);
      $data_dc4 = $disbursed_result_dc4->fetchObject();
      $disbursed_result_dc6 = db_query($dc6sql);
      $data_dc6 = $disbursed_result_dc6->fetchObject();


      //get the performance rating for this country and also the data for the map
      $service = new CountryGrantDetailsListing();
      $country_rating_info = $service->country_performance_rating($country->id, 0, '2010-01-01', date('Y-m-d'));
      $country_component_grant_info = $service->country_grant_by_disease($country->id, 0, '2010-01-01', date('Y-m-d'));



      if (next($result) === false) {
        $map_data .= sprintf(',{"ccode":"%s","cname":"%s","cid":"%s","amount_disbursed":"%s","agreement_amount":"%s","number_of_rated_grants":%s,"country_grant_rating":"%s","country_grant_rating_color":"#%s","latest_grant_rating":"%s","latest_grant_rating_color":"#%s","total_disbursments_hiv":"%s","total_disbursments_tb":"%s","total_disbursments_malaria":"%s","total_disbursments_hivtb":"%s","total_disbursments_hss":"%s","number_of_hiv_grants":%s,"number_of_tb_grants":%s,"number_of_malaria_grants":%s,"number_of_hivtb_grants":%s,"number_of_hss_grants":%s,"number_of_active_grants":%s}', $country->iso_code_2, $country->name, $country->id, _format_money_amount($data->disbursed_amount, 1), _format_money_amount($country->agreement_amount, 1), $country_rating_info[2], $country_rating_info[0], $country_rating_info[1], $country_rating_info[3], $country_rating_info[4], _format_money_amount($data_dc1->disbursed_amount, 1), _format_money_amount($data_dc2->disbursed_amount, 1), _format_money_amount($data_dc3->disbursed_amount, 1), _format_money_amount($data_dc4->disbursed_amount, 1), _format_money_amount($data_dc6->disbursed_amount, 1), (int) $country_component_grant_info[1], $country_component_grant_info[2], $country_component_grant_info[3], $country_component_grant_info[4], $country_component_grant_info[5], $country_component_grant_info[6],0);
      }
      else {

        $map_data .= sprintf('{"ccode":"%s","cname":"%s","cid":"%s","amount_disbursed":"%s","agreement_amount":"%s","number_of_rated_grants":%s,"country_grant_rating":"%s","country_grant_rating_color":"#%s","latest_grant_rating":"%s","latest_grant_rating_color":"#%s","total_disbursments_hiv":"%s","total_disbursments_tb":"%s","total_disbursments_malaria":"%s","total_disbursments_hivtb":"%s","total_disbursments_hss":"%s","number_of_hiv_grants":%s,"number_of_tb_grants":%s,"number_of_malaria_grants":%s,"number_of_hivtb_grants":%s,"number_of_hss_grants":%s,"number_of_active_grants":%s}', $country->iso_code_2, $country->name, $country->id, _format_money_amount($data->disbursed_amount, 1), _format_money_amount($country->agreement_amount, 1), $country_rating_info[2], $country_rating_info[0], $country_rating_info[1], $country_rating_info[3], $country_rating_info[4], _format_money_amount($data_dc1->disbursed_amount, 1), _format_money_amount($data_dc2->disbursed_amount, 1), _format_money_amount($data_dc3->disbursed_amount, 1), _format_money_amount($data_dc4->disbursed_amount, 1), _format_money_amount($data_dc6->disbursed_amount, 1), (int) $country_component_grant_info[1], $country_component_grant_info[2], $country_component_grant_info[3], $country_component_grant_info[4], $country_component_grant_info[5], $country_component_grant_info[6],0);
      }
 
 
      
    }

    //close the map data
    $map_data .= "]
};";

//get the ratings info
    $sql = "SELECT count(gdr.id) count
                FROM gf_disbursement_rating gdr
                WHERE rating is not null AND rating != '' AND rating != 'x' AND rating != 'NR'";
    if (null != $start_date) {
      $sql .= " AND pu_end_date >= '" . $start_date . "'";
    }
    if (null != $end_date) {
      $sql .= " AND pu_end_date <= '" . $end_date . "'";
    }

    //get the legend for the map
    $legend_data = array();
    $sql = "SELECT gf_rating, color FROM performance_rating";
    $result = db_query($sql);

    foreach ($result as $rating) {
      $legend_data[$rating->gf_rating] = $rating->color;
    }

    $legend_data['NR'] = '726E6D';

    return array( $map_data, $legend_data);
  }

  function charset_decode_utf_8($string) {
    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (!preg_match("/[\200-\237]/", $string) and ! preg_match("/[\241-\377]/", $string))
      return $string;

    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e", "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'", $string);

    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e", "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'", $string);

    return $string;
  }

  function data_fact_links_for_country($country) {
    $links = array();

    if ($country->global_health_facts_id) {
      $links[] = theme('country_specific_info_from_globalhealth', $country->global_health_facts_id);
    }
    if ($country->who_id) {
      $links[] = theme('country_specific_info_from_who', $country->who_id);
    }

    return $links;
  }

  function gf_links_for_country($country) {
    $links = array();
    $links[] = l(t('Grant documents'), 'http://portfolio.theglobalfund.org/en/Country/Index/' . $country->iso_code_3, array('attributes' => array('target' => '_blank')));
    $links[] = l(t('All CCM contacts'), 'http://portfolio.theglobalfund.org/en/Contacts/CountryCoordinatingMechanisms/' . $country->iso_code_3, array('attributes' => array('target' => '_blank')));
    $links[] = l(t('Principal Recipient(s)'), 'http://portfolio.theglobalfund.org/en/Contacts/PrincipalRecipients/' . $country->iso_code_3, array('attributes' => array('target' => '_blank')));
    $links[] = l(t('LFA'), 'http://portfolio.theglobalfund.org/en/Contacts/LocalFundAgents/' . $country->iso_code_3, array('attributes' => array('target' => '_blank')));

    return $links;
  }

  public function country_performance_rating($country_id, $rating_threshold = 3, $start_date = null, $end_date = null, $disease_id = null) {

    $country_rating_info = array('n/a', '726E6D', 0, 'n/a', '726E6D');
    $no_of_ratings = 0;
    $total_rating_weight = 0;
    $average_rating = 0;

    $weight_holder = array();
    $rating_config = array();
    $color_config = array();
    $color_config['N/A'] = '000000';

    $weights = db_query("SELECT * FROM performance_rating WHERE gf_rating != 'A' ORDER BY rating_weight DESC");
    foreach ($weights as $record) {
      $rating_config[$record->gf_rating] = $record->rating_weight;
      $color_config[$record->gf_rating] = $record->color;
      $weight_holder[] = $record->lower_bound;
    }

    //fetch all the reporting period information for this country
    $sql = "SELECT pu.id, pu.rating
                FROM progress_update pu
INNER JOIN gf_grant gg ON pu.gf_grant_id = gg.id
                WHERE gg.country_id = :country_id AND 
                rating is NOT NULL AND rating != '' AND rating != 'x' AND rating != 'NR' AND rating != 'Non' AND rating != 'N/A'";
    if (null != $start_date) {
      $sql .= " AND pu_end_date >= '" . $start_date . "'";
    }
    if (null != $end_date) {
      $sql .= " AND pu_end_date <= '" . $end_date . "'";
    }
    if (null != $disease_id) {
      $sql .= ' AND gg.disease_component_id = ' . $disease_id;
    }
    $sql .= " ORDER BY pu_end_date DESC";

    $disbursement_ratings = db_query($sql, array(':country_id' => $country_id));

    foreach ($disbursement_ratings as $disbursement_rating) {

      $rating_weight = $this->get_rating_weight($rating_config, $disbursement_rating->rating);

      if (null != $rating_weight) {
        $total_rating_weight += $rating_weight;
        $no_of_ratings++;
      }
    }

    if ($no_of_ratings > 0 && $no_of_ratings >= $rating_threshold) {

      $average_rating = $total_rating_weight / $no_of_ratings;

      for ($i = 0; $i < sizeof($rating_config); $i++) {

        $upper_bound = 0;
        $lower_bound = 0;

        if ($i == 0) {
          //means that this is the first rating, which is the highest rating
          $upper_bound = 110; //set it as high as possible
          $lower_bound = $weight_holder[$i];
        }
        else {
          $upper_bound = $weight_holder[$i - 1];
          $lower_bound = $weight_holder[$i];
        }

        if ($average_rating >= $lower_bound && $average_rating < $upper_bound) {
          $converted_weight = ($lower_bound + $upper_bound) / 2;
          //var_dump($converted_weight);var_dump($lower_bound);var_dump($upper_bound);
          foreach ($rating_config as $gf_rating => $weight) {
            if ($converted_weight == $weight) {
              $country_rating_info = array();

              $country_rating_info[] = $gf_rating; // . '(' . $average_rating . ')';
              $country_rating_info[] = $color_config[$gf_rating];
              $country_rating_info[] = $no_of_ratings;
              //get the latest rating for this country
              $sql .= " LIMIT 1";

              $latest_rating = db_query($sql, array(':country_id' => $country_id))->fetchObject();
              $country_rating_info[] = $latest_rating->rating;
              $country_rating_info[] = $color_config[$latest_rating->rating];

              break;
            }
          }
        }
      }
    }

    return $country_rating_info; // . ' (' . number_format($average_rating, 0) . ')';
  }

  public function retrieve_grant_rating($grant_id, $rating_threshold, $start_date = null, $end_date = null) {

    $info = array('n/a', '726E6D', 0, 'n/a', '726E6D');

    $weight_holder = array();
    $rating_config = array();
    $color_config = array();

    $weights = db_query("SELECT * FROM performance_rating WHERE gf_rating != 'A' ORDER BY rating_weight DESC");
    foreach ($weights as $record) {
      $rating_config[$record->gf_rating] = $record->rating_weight;
      $color_config[$record->gf_rating] = $record->color;
      $weight_holder[] = $record->lower_bound;
    }

    $grant_rating_info = $this->assign_rating($grant_id, $rating_config, $weight_holder, $start_date, $end_date);

    if ($grant_rating_info[1] >= $rating_threshold) {

      $info = array();

      $info[] = $grant_rating_info[0];
      $info[] = $color_config[$grant_rating_info[0]];
      $info[] = $grant_rating_info[1];
      $info[] = $grant_rating_info[2];
      $info[] = $color_config[$grant_rating_info[2]];
    }

    return $info;
  }

  private function assign_rating($grant_id, $rating_config, $weight_holder, $start_date = null, $end_date = null) {

    #fetch all the dibursements for this grant, from the rating data
    $sql = "SELECT id, rating
                FROM gf_disbursement_rating
                WHERE gf_grant_id = :gf_grant_id AND rating is not null AND rating != '' AND rating != 'x' AND rating != 'NR' AND rating != 'Non' AND rating != 'N/A'";
    if (null != $start_date) {
      $sql .= " AND pu_end_date >= '" . $start_date . "'";
    }
    if (null != $end_date) {
      $sql .= " AND pu_end_date <= '" . $end_date . "'";
    }
    $sql .= " ORDER BY pu_end_date DESC";

    $disbursement_ratings = db_query($sql, array(':gf_grant_id' => $grant_id));

    $no_of_ratings = 0;
    $total_rating_weight = 0;

    foreach ($disbursement_ratings as $disbursement_rating) {

      $rating_weight = $this->get_rating_weight($rating_config, $disbursement_rating->rating);

      if (null != $rating_weight) {
        $total_rating_weight += $rating_weight;
        $no_of_ratings++;
      }
    }

    $grant_rating = array();
    $latest_rating = '';

    if ($no_of_ratings >= 1) {

      $average_rating = $total_rating_weight / $no_of_ratings;

      for ($i = 0; $i < sizeof($rating_config); $i++) {

        $upper_bound = 0;
        $lower_bound = 0;

        if ($i == 0) {
          //means that this is the first rating, which is the highest rating
          $upper_bound = 110; //set it as high as possible
          $lower_bound = $weight_holder[$i];
        }
        else {
          $upper_bound = $weight_holder[$i - 1];
          $lower_bound = $weight_holder[$i];
        }

        if ($average_rating >= $lower_bound && $average_rating < $upper_bound) {
          $converted_weight = ($lower_bound + $upper_bound) / 2;

          foreach ($rating_config as $gf_rating => $weight) {
            if ($weight == $converted_weight) {
              $grant_rating = $gf_rating;
              //get the latest rating
              $sql .= " LIMIT 1";

              $latest_rating_result = db_query($sql, array(':gf_grant_id' => $grant_id))->fetchObject();
              $latest_rating = $latest_rating_result->rating;

              break;
            }
          }
        }
      }
    }

    return array($grant_rating, $no_of_ratings, $latest_rating);
  }

  private function get_rating_weight($rating_config, $disbursement_rating) {

    foreach ($rating_config as $gf_rating => $weight) {
      if ($gf_rating == $disbursement_rating) {
        return $weight;
      }
    }

    return null;
  }

  function country_grant_by_disease($country_id, $start_date, $end_date) {
    //Approved funding for the countries per disease
    $sql = "SELECT count(pu.rating) as rated_disbursments,
      ( SELECT  count(gg.id) from  gf_grant gg inner join grant_status gs 
 on  gg.grant_status_id=gs.id  where 
( gs.parent_id in(1,2,3,4,5,43)) and  gg.country_id= :country_id) as total_active_grants,
 (SELECT  count(gg.id) from  gf_grant gg inner join grant_status gs 
 on  gg.grant_status_id=gs.id  where 
( gs.parent_id in(1,2,3,4,5,43)) and 1=gg.disease_component_id and gg.country_id = :country_id ) as total_hiv_grants,
 ( SELECT  count(gg.id) from  gf_grant gg inner join grant_status gs 
 on  gg.grant_status_id=gs.id  where 
( gs.parent_id in(1,2,3,4,5,43)) and 2=gg.disease_component_id and gg.country_id = :country_id ) as total_tb_grants,
( SELECT  count(gg.id) from  gf_grant gg inner join grant_status gs 
 on  gg.grant_status_id=gs.id  where 
( gs.parent_id in(1,2,3,4,5,43)) and 3=gg.disease_component_id and gg.country_id = :country_id ) as total_malaria_grants,
(SELECT  count(gg.id) from  gf_grant gg inner join grant_status gs 
 on  gg.grant_status_id=gs.id  where 
( gs.parent_id in(1,2,3,4,5,43)) and 4=gg.disease_component_id and gg.country_id = :country_id ) as total_hivtb_grants,
( SELECT  count(gg.id) from  gf_grant gg inner join grant_status gs 
 on  gg.grant_status_id=gs.id  where 
( gs.parent_id in(1,2,3,4,5,43)) and 6=gg.disease_component_id and gg.country_id = :country_id ) as total_hss_grants
                FROM progress_update pu
INNER JOIN grant_status gs ON gs.id=3
INNER JOIN gf_grant gg ON pu.gf_grant_id = gg.id
                WHERE gg.country_id = :country_id 
 ";
    if (null != $start_date) {
      $sql .= " AND pu_end_date >= '" . $start_date . "'";
    }
    if (null != $end_date) {
      $sql .= " AND pu_end_date <= '" . $end_date . "'";
    }
    $sql .= "ORDER BY pu_end_date DESC";

    $result = db_query($sql, array(':country_id' => $country_id));

    $country_component_grant_info = array();

    foreach ($result as $disease) {
      $country_component_grant_info[] = (int) ($disease->rated_disbursments);
      $country_component_grant_info[] = (int) ($disease->total_hiv_grants);
      $country_component_grant_info[] = (int) ($disease->total_tb_grants );
      $country_component_grant_info[] = (int) ( $disease->total_malaria_grants);
      $country_component_grant_info[] = (int) ($disease->total_hivtb_grants );
      $country_component_grant_info[] = (int) ($disease->total_hss_grants); 
      $country_component_grant_info[] = (int) ($disease->total_active_grants);
    }



    return $country_component_grant_info;
  }
  public function get_dc_id() 
 {
     $sql = "SELECT dc.id FROM disease_component dc" ;
     return $result = db_query($sql);

    
  } 
  public function country_dc_grant_data($country_id, $rating_threshold = 3, $start_date = null, $end_date = null, $disease_id = null) {
    $dc_id = get_dc_id();
    $country_dc_grant_data = array();
    foreach ($result as $disease) {
      $dcsql = "SELECT c.id, c.name as name,SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id =" . $country_id . " and gg.disease_component_id=" . $result->id;
      $disbursed_result_dc = db_query($dcsql);
      $data_dc = $disbursed_result_dc->fetchObject();
      $country_component_grant_info = array();
    }
  }

}
