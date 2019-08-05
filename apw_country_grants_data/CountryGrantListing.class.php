<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CountryGrantListing
 *
 * @author kinyua
 */
class CountryGrantListing {

  function country_key_results($country_id) {
    $key_results = array();

    //get the key results for this country
    $indicators = db_query("SELECT id, name FROM indicator WHERE indicator_type = 'top3'");

    foreach ($indicators as $indicator) {
      $row = array();

      $row[] = $indicator->name;

      $country_indicatory_result = db_query("SELECT SUM(indicator_value) tt FROM indicator_catalog WHERE country_id = :country_id AND indicator_id = :indicator_id", array(':country_id' => $country_id, ':indicator_id' => $indicator->id));
      $row[] = number_format($country_indicatory_result->fetchField());

      $key_results[] = $row;
    }

    return $key_results;
  }

  function country_dc_who_results($country_id, $dc_id, $who_stat_id) {
    $key_results = array();

    $who_epidemiological_results = db_query(" SELECT  year, statistic_value,name FROM apw2_production.who_health_statistic_result  
join who_health_statistic on who_health_statistic.id=who_health_statistic_id 
join target_year on target_year.id=target_year_id
where country_id=:country_id
and who_health_statistic_id=:who_stat_id
and disease_component_id=:dc_id order by year, name asc ", array(':country_id' => $country_id, ':dc_id' => $dc_id, ':who_stat_id' => $who_stat_id));

    $colorarray = array(1 => '#33E6D9', 2 => '#FFA600', 3 => '#A64B00', 4 => '#8CCCF2',
      5 => '#ED0000', 6 => '#8C19A3', 7 => '#A6FF00', 8 => '#00AAE6');


    mt_srand($who_stat_id);

    $color = mt_rand(1, 8);
    $years = array();
    $name = array();
    $sivalue = array();
    $seriescount = 0;
    $count = 0;
    foreach ($who_epidemiological_results as $results) {

      if (!array_search($results->name, $name)) {
        $years[] = $results->year;
        $name[$seriescount] = $results->name;
        // $series = $name[$seriescount];
        $seriescount++;
      }
      $sivalue[$results->name][$results->year] = $results->statistic_value;
    }

    //loop throgh the no of years adding null
    //or add value for year
    $years = array_unique($years);
    $name = array_unique($name);
    $seriesdata = array();

    foreach ($name as &$nvalue) {
      $series = new stdClass();
      $series->name = $nvalue;
      $series->color = $colorarray[$color];
      $data = array();
      foreach ($years as &$yvalue) { 
        
        $data[] = clean_statistic_result_values($sivalue[$nvalue][$yvalue]);
       
      }
      if (is_array_empty($data)) {
        $series->data = $data;
        $seriesdata[] = $series;
      }
      
    }
    if(!empty($seriesdata)){
    $key_results[] = array_unique(array_values($years));
     $key_results[] = $seriesdata;
    }
   

    if (empty($key_results)) {
      return null;
    }
    return $key_results;
  }
 function country_dc_unaids_results($country_id, $dc_id, $un_stat_id) {
    $key_results = array();
##unaids_statistic_indicators_result
    $epidemiological_results = db_query(" SELECT  year, statistic_value,name FROM apw2_production.unaids_statistic_indicators_result  
join unaids_statistic_indicators on unaids_statistic_indicators.id=unaids_statistic_id 
join target_year on target_year.id=target_year_id
where country_id=:country_id
and unaids_statistic_id=:who_stat_id
and disease_component_id=:dc_id order by year, name asc ", array(':country_id' => $country_id, ':dc_id' => $dc_id, ':who_stat_id' => $un_stat_id));

    $colorarray = array(1 => '#33E6D9', 2 => '#FFA600', 3 => '#A64B00', 4 => '#8CCCF2',
      5 => '#ED0000', 6 => '#8C19A3', 7 => '#A6FF00', 8 => '#00AAE6');


    mt_srand($un_stat_id);

    $color = mt_rand(1, 8);
    $years = array();
    $name = array();
    $sivalue = array();
    $seriescount = 0;
    $count = 0;
    foreach ($epidemiological_results as $results) {

      if (!array_search($results->name, $name)) {
        $years[] = $results->year;
        $name[$seriescount] = $results->name;
        // $series = $name[$seriescount];
        $seriescount++;
      }
      $sivalue[$results->name][$results->year] = $results->statistic_value;
    }

    //loop throgh the no of years adding null
    //or add value for year
    $years = array_unique($years);
    $name = array_unique($name);
    $seriesdata = array();

    foreach ($name as &$nvalue) {
      $series = new stdClass();
      $series->name = $nvalue;
      $series->color = $colorarray[$color];
      $data = array();
      foreach ($years as &$yvalue) { 
        
        $data[] = clean_statistic_result_values($sivalue[$nvalue][$yvalue]);
       
      }
      if (is_array_empty($data)) {
        $series->data = $data;
        $seriesdata[] = $series;
      }
      
    }
    if(!empty($seriesdata)){
    $key_results[] = array_unique(array_values($years));
     $key_results[] = $seriesdata;
    }
   

    if (empty($key_results)) {
      return null;
    }
    return $key_results;
  }
  
  function retrieve_grant_contact($gf_grant_id) {
    $sql = "SELECT co.id, co.name org_name, cot.name type_name, cost.name sub_type_name, COUNT(c.id) contact_count
                FROM contact c
                INNER JOIN contact_organization co ON c.contact_organization_id = co.id
                INNER JOIN contact_organization_type cot ON co.contact_organization_type_id = cot.id
                INNER JOIN contact_organization_sub_type cost ON co.contact_organization_sub_type_id = cost.id
                WHERE co.gf_grant_id = :gf_grant_id
                GROUP BY co.id
                ORDER BY co.name";

    $result = db_query($sql, array(':gf_grant_id' => $gf_grant_id));

    $country_contact = array();

    foreach ($result as $data) {
      //get the contacts in this organization
      $sql = "SELECT c.first_name, c.family_name, c.business_phone_number, c.email_address, cr.name role_name
                 FROM contact c
                 INNER JOIN contact_role cr ON c.role_id = cr.id
                 WHERE c.contact_organization_id = :contact_organization_id";

      $contacts = db_query($sql, array(':contact_organization_id' => $data->id));

      $counter = 0;
      foreach ($contacts as $contact) {
        $row = array();

        if (0 == $counter) {
          $row[] = array('data' => $data->type_name . ' (' . $data->sub_type_name . ')', 'rowspan' => $data->contact_count, 'class' => 'apw-center-placement-rowspan');
          $row[] = array('data' => $data->org_name, 'rowspan' => $data->contact_count, 'class' => 'apw-center-placement-rowspan');
        }
        $counter += 1;

        $row[] = array('data' => $contact->first_name);
        $row[] = array('data' => $contact->family_name);
        $row[] = array('data' => $contact->business_phone_number);
        //$row[] = array('data' => str_replace(".", " dot ", str_replace("@", " at ", $contact->email_address)));
        $row[] = array('data' => $contact->email_address);
        $row[] = array('data' => $contact->role_name);

        $country_contact[] = $row;
      }
    }

    return $country_contact;
  }

  function retrieve_country_contact($country_id) {
    $sql = "SELECT co.id, co.name org_name, cot.name type_name, cost.name sub_type_name, COUNT(c.id) contact_count
                FROM contact c
                INNER JOIN contact_organization co ON c.contact_organization_id = co.id
                INNER JOIN contact_organization_type cot ON co.contact_organization_type_id = cot.id
                INNER JOIN contact_organization_sub_type cost ON co.contact_organization_sub_type_id = cost.id
                WHERE co.country_id = :country_id
                GROUP BY co.id
                ORDER BY co.name";

    $result = db_query($sql, array(':country_id' => $country_id));

    $country_contact = array();

    foreach ($result as $data) {
      //get the contacts in this organization
      $sql = "SELECT c.first_name, c.family_name, c.business_phone_number, c.email_address, cr.name role_name
                 FROM contact c
                 INNER JOIN contact_role cr ON c.role_id = cr.id
                 WHERE c.contact_organization_id = :contact_organization_id";

      $contacts = db_query($sql, array(':contact_organization_id' => $data->id));

      $counter = 0;
      foreach ($contacts as $contact) {
        $row = array();

        if (0 == $counter) {
          $row[] = array('data' => $data->type_name . ' (' . $data->sub_type_name . ')', 'rowspan' => $data->contact_count, 'class' => 'apw-center-placement-rowspan');
          $row[] = array('data' => $data->org_name, 'rowspan' => $data->contact_count, 'class' => 'apw-center-placement-rowspan');
        }
        $counter += 1;

        $row[] = array('data' => $contact->first_name);
        $row[] = array('data' => $contact->family_name);
        $row[] = array('data' => $contact->business_phone_number);
        //$row[] = array('data' => str_replace(".", " dot ", str_replace("@", " at ", $contact->email_address)));
        $row[] = array('data' => $contact->email_address);
        $row[] = array('data' => $contact->role_name);

        $country_contact[] = $row;
      }
    }

    return $country_contact;
  }

  function country_dc_ids($country_id, $start_date = null, $end_date = null) {
    $sql = "SELECT distinct dc.id, dc.name component
       FROM disease_component dc 
INNER JOIN gf_grant gg ON gg.disease_component_id = dc.id
LEFT JOIN principal_recipient pr ON gg.principal_recipient_id = pr.id
LEFT JOIN principal_recipient_type prt ON pr.principal_recipient_type_id = prt.id
WHERE gg.country_id = :country_id
ORDER BY dc.sort_col ASC, gg.grant_number DESC";

    $result = db_query($sql, array(':country_id' => $country_id));

    $country_dc_id_listing = array();
    foreach ($result as $data) {
      $row = array();
      $row[] = $data->id;
      $row[] = $data->component;
      $country_dc_id_listing[] = $row;
    }

    return $country_dc_id_listing;
  }

  function country_dc_epidemiological_indicator_ids($country_id, $disease_id, $start_date = null, $end_date = null) {
    
    
    $country_dc_epidemiological_id_listing = array();
   
  if ($disease_id == 1 || $disease_id == 4) {
 $sql = "
      SELECT  unaids_statistic_id as id,name FROM apw2_production.unaids_statistic_indicators_result  
join unaids_statistic_indicators on unaids_statistic_indicators.id=unaids_statistic_id 
where country_id=:country_id
and disease_component_id=:disease_id
and  statistic_value is not null 
and statistic_value not like '%No data%'  
and CONVERT(statistic_value USING utf8)  not like CONVERT('…%' USING utf8)
and CONVERT(statistic_value USING utf8)  not like CONVERT(' [%' USING utf8)
group by name asc";

    $unaidsresult = db_query($sql, array(':country_id' => $country_id,
      ':disease_id' => $disease_id));
 
    foreach ($unaidsresult as $data) { 
      $row = array();
      $row[] = $data->id;
      $row[] = $data->name;
      $country_dc_epidemiological_id_listing[] = $row;
    }
  }
else{
  
    $sql = "
      SELECT  who_health_statistic_id as id,name FROM apw2_production.who_health_statistic_result  
join who_health_statistic on who_health_statistic.id=who_health_statistic_id 
where country_id=:country_id
and disease_component_id=:disease_id
and  statistic_value is not null 
and statistic_value not like '%No data%'  
and CONVERT(statistic_value USING utf8)  not like CONVERT('…%' USING utf8)
and CONVERT(statistic_value USING utf8)  not like CONVERT(' [%' USING utf8)
group by name asc";

    $whoresult = db_query($sql, array(':country_id' => $country_id,
      ':disease_id' => $disease_id));

    
    foreach ($whoresult as $data) {

      $row = array();
      $row[] = $data->id;
      $row[] = $data->name;
      $country_dc_epidemiological_id_listing[] = $row;
    }
    
}
    return $country_dc_epidemiological_id_listing;
  }

  function country_grant_dc_listing($country_id, $disease_id, $start_date = null, $end_date = null) {
    $sql = "SELECT gg.country_id country_id, gg.id grant_id, dc.name disease,
        gg.grant_number, pr.name pr, prt.name pr_type, gg.gf_original_grant_id
FROM gf_grant gg
INNER JOIN disease_component dc ON gg.disease_component_id = dc.id
LEFT JOIN principal_recipient pr ON gg.principal_recipient_id = pr.id
LEFT JOIN principal_recipient_type prt ON pr.principal_recipient_type_id = prt.id
WHERE gg.country_id = :country_id
and  dc.id = :disease_id
ORDER BY dc.sort_col DESC, gg.grant_number DESC";

    $result = db_query($sql, array(':country_id' => $country_id,
      ':disease_id' => $disease_id));

    $country_grant_listing = array();

    $total_agreement_amt = 0;
    $total_disbursed_amt = 0;
    $total_percentdisbursed = 0;
    $total_rating = 0;
    $total_rating_weight = array();
    $count = 0;
    foreach ($result as $data) {
      $count++;
      $row = array();

      //$row[] = array('data' => $data->disease, 'class' => 'apw-numeric-right-br');
      $row[] = array('data' => l($data->grant_number, 'country_grant/' . $data->grant_number), 'nowrap' => 'nowrap', 'class' => 'apw-numeric-right-br');
      $row[] = array('data' => $data->pr, 'class' => 'apw-numeric-right-br');
      $row[] = array('data' => $data->pr_type, 'class' => 'apw-numeric-right-br');
      //get the agreement amount for this grant
      $sql = "SELECT SUM(agreement_amount) agreement_amount FROM grant_agreement WHERE gf_grant_id = :gf_grant_id";
      $agreement_amt = db_query($sql, array(':gf_grant_id' => $data->grant_id))->fetchField();
      $row[] = array('data' => _format_money_amount($agreement_amt, 1), 'class' => 'apw-numeric-right-br', 'nowrap' => 'nowrap');
      $total_agreement_amt+=$agreement_amt;
      //get the disbursement amount for this grant
      $sql = "SELECT SUM(disbursed_amount ) disbursed_amount FROM gf_disbursement_rating WHERE gf_grant_id = :gf_grant_id";
      $disbursed_amt = db_query($sql, array(':gf_grant_id' => $data->grant_id))->fetchField();
      $total_disbursed_amt+=$disbursed_amt;
      $row[] = array('data' => _format_money_amount($disbursed_amt, 1), 'class' => 'apw-numeric-right-br', 'nowrap' => 'nowrap');
      if ($agreement_amt > 0) {
        $percentdisbursed = round($disbursed_amt / $agreement_amt * 100, 0);
        $row[] = array('data' => $percentdisbursed . '%', 'class' => 'apw-numeric-right-br');
        $total_percentdisbursed+=$percentdisbursed;
      }
      else {
        $row[] = array('data' => 0 . '%', 'class' => 'apw-numeric-right-br');
      }

//get average rating for this grant
      $service = new CountryChartService();
      $rating_info = $service->retrieve_grant_rating($data->grant_id, 1, $start_date, $end_date);

      $row[] = array('data' => $rating_info[2], 'class' => 'apw-numeric');
      $total_rating+=$rating_info[2];
      $row[] = array('data' => $rating_info[0], 'class' => 'rating-' . $rating_info[1]); //rating
      $total_rating_weight = $service->country_performance_rating($country_id, 0, $start_date, $end_date, $disease_id);
      $row[] = array('data' => $rating_info[3], 'class' => 'rating-' . $rating_info[4]);

      $json = file_get_contents('http://data-service.theglobalfund.org/v1/feeds/views/VGrantAgreements');
    $obj = json_decode($json,true);
    $grantAgreementId='';
    //var_dump($obj);
    foreach($obj as $doc)
    {
      if($doc['grantAgreementNumber']==$data->grant_number){
        $grantAgreementId=$doc['grantAgreementId'];
        break;
      }
   }
      
//gf page for the grant
      $row[] = array('data' => l('GF page', 'http://www.theglobalfund.org/en/portfolio/country/grant/?k='.$grantAgreementId.'&grant='.$data->grant_number, array('attributes' => array('target' => '_blank'))), 'nowrap' => 'nowrap', 'class' => 'apw-numeric-right-br');
//gpr for the grant
      //$tokens = explode('-', $data->grant_number);
      //if ('C' != $tokens[3] && 'S' != $current_round) {
      $row[] = array('data' => l('GPR', 'http://www.theglobalfund.org/ProgramDocuments/' . substr($data->grant_number, 0, 3) . '/' . $data->grant_number . '/' . $data->grant_number . '_GPR_0_en', array('attributes' => array('target' => '_blank'))), 'class' => 'apw-numeric-right-br');
//            } else {
//                $row[] = null;
//            }

      $country_grant_listing[] = $row;
    }




    //    $country_latest_rating_info = $service->country_lates_avg_performance_rating($country_id, 0, $start_date, $end_date, $disease->id,$count);

    $row = array(
      'data' => array(
        array('data' => '<strong>Total</strong>', 'class' => 'apw-total-row-br'),
        array('data' => ' ', 'class' => 'apw-total-row-br'),
        array('data' => ' ', 'class' => 'apw-total-row-br'),
        array('data' => _format_money_amount($total_agreement_amt, 1), 'class' => 'apw-total-row-br apw-numeric'),
//disbursements
        array('data' => _format_money_amount($total_disbursed_amt, 1), 'class' => 'apw-total-row-br apw-numeric'),
        //get the total number of valid ratings, average rating and latest rating
        array('data' => 'Avg.' . round($total_percentdisbursed / $count, 0) . '%', 'class' => 'apw-total-row-br'),
        array('data' => $total_rating, 'class' => 'apw-total-row-br'),
        array('data' => $total_rating_weight[0], 'class' => 'apw-total-row-br rating-' . $total_rating_weight[1]),
        array('data' => ' ', 'class' => 'apw-total-row-br'),
        array('data' => ' ', 'class' => 'apw-total-row-br'),
        array('data' => ' ', 'class' => 'apw-total-row-br')),
      'class' => array('tablesorter-no-sort'),
      'data-sorter' => array('false'),
    );


    $country_grant_listing[] = $row;





    return $country_grant_listing;
  }

  function country_grant_amount($start_date = null, $end_date = null) {

    $map_data = "<map
            animation='0'
            showShadow='0'
            showBevel='0'
            showLabels='0'
            showMarkerLabels='1'
            fillColor='ffffff'
            borderColor='000000'
            baseFont='Verdana'
            baseFontSize='10'
            markerBorderColor='000000'
            markerBgColor='FF5904'
            markerRadius='6'
            legendPosition='bottom'
            bgColor='f1f1f1'
            useHoverColor='0'
            showLegend='1'
            showMarkerToolTip='1'  >
	<data>";
    //Approved funding for the countries
    $sql = "SELECT c.id, c.name as name, c.fc_id, SUM(ga.agreement_amount) as agreement_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN grant_agreement ga ON ga.gf_grant_id = gg.id
GROUP BY c.name
ORDER BY name";

    $result = db_query($sql);

    $country_grant_info = array();
    $header = array(array('data' => '', 'header' => true),
      array('data' => '', 'header' => true),
      array('data' => '', 'header' => true),
      //array('data' => '', 'header' => true),
      array('data' => t('Number'), 'header' => true, 'class' => 'apw-numeric-center'),
      array('data' => t('Average'), 'class' => 'apw-amount-total', 'header' => true),
      array('data' => t('Latest'), 'class' => 'apw-amount-total', 'header' => true),
    );
    $country_grant_info[] = $header;

    foreach ($result as $country) {
      $row = array();
      $row[] = array('data' => l($country->name, 'country/' . $country->id), 'class' => 'apw-name-right-br');

      $row[] = array('data' => _format_money_amount($country->agreement_amount, 0), 'class' => 'apw-numeric-right-br');

      //get the disbursement for the current country
      $sql = "SELECT c.id, c.name as name, SUM(gdr.disbursed_amount) as disbursed_amount
FROM country c
INNER JOIN gf_grant gg ON gg.country_id = c.id
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE c.id = " . $country->id;

      $disbursed_result = db_query($sql);
      $data = $disbursed_result->fetchObject();
      $row[] = array('data' => _format_money_amount($data->disbursed_amount, 0), 'class' => 'apw-numeric-right-br');

      //get the performance rating for this country and also the data for the map
      $service = new CountryChartService();
      $country_rating_info = $service->country_performance_rating($country->id, 0, $start_date, $end_date);
      $row[] = array('data' => $country_rating_info[2], 'class' => 'apw-numeric');
      $row[] = array('data' => $country_rating_info[0], 'class' => 'rating-' . $country_rating_info[1]); //rating
      $row[] = array('data' => $country_rating_info[3], 'class' => 'rating-' . $country_rating_info[4]);

      $map_data .= sprintf('<entity id="%s" color="%s" link="../country/%d" toolText="%s"/>', $country->fc_id, $country_rating_info[1], $country->id, $this->charset_decode_utf_8($country->name) . ' (' . $country_rating_info[0] . ')');

      $country_grant_info[] = $row;
    }

    //close the map data
    $map_data .= "</data></map>";


    //get the totals for the approved amount, grant agreement and the disbursed amounts
    $row = array();
    $row[] = array('data' => 'ALL COUNTRIES', 'class' => 'apw-total-row-br');
    //approved amount
    //$row[] = array('data' => _format_money_amount(_total_approved_amount(), 0), 'class' => 'apw-total-row-br apw-numeric');
//grant agreement
    $row[] = array('data' => _format_money_amount(_total_agreement_amount(), 0), 'class' => 'apw-total-row-br apw-numeric');
//disbursements
    $row[] = array('data' => _format_money_amount(_total_disbursed_amount(), 0), 'class' => 'apw-total-row-br apw-numeric');
    //get the ratings info
    $sql = "SELECT count(pu.id) count
                FROM progress_update pu
                WHERE rating is not null AND rating != '' AND rating != 'x' AND rating != 'NR'";
    if (null != $start_date) {
      $sql .= " AND pu_end_date >= '" . $start_date . "'";
    }
    if (null != $end_date) {
      $sql .= " AND pu_end_date <= '" . $end_date . "'";
    }
    $row[] = array('data' => number_format(db_query($sql)->fetchField()), 'class' => 'apw-total-row-br apw-numeric'); //number of ratings
    $row[] = array('data' => ' ', 'class' => 'apw-total-row-br'); //average rating
    $row[] = array('data' => ' ', 'class' => 'apw-total-row-br'); //latest rating

    $country_grant_info[] = $row;

    //get the legend for the map
    $legend_data = array();
    $sql = "SELECT gf_rating, color FROM performance_rating";
    $result = db_query($sql);

    foreach ($result as $rating) {
      $legend_data[$rating->gf_rating] = $rating->color;
    }

    $legend_data['NR'] = '726E6D';

    // return array($country_grant_info, $map_data, $legend_data);
    return array("", $map_data, "");
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

    $links = array(
      'items' => array(),
      // Leave the title element empty to omit the title.
      'title' => '',
      'type' => 'ul',
      'attributes' => array(),
    );


    $links = array();
    
    $json = file_get_contents('http://data-service.theglobalfund.org/v1/feeds/views/VGrantAgreements');
    $obj = json_decode($json,true);
    $geographicAreaId='';
    //var_dump($obj);
    foreach($obj as $doc)
    {
      if($doc['geographicAreaCode_ISO3']==$country->iso_code_3){
        $geographicAreaId=$doc['geographicAreaId'];
        break;
      }
   }
    $links['items'][] = l(t('Grant documents'), 'http://www.theglobalfund.org/en/portfolio/country/?loc=' . $country->iso_code_3.'&k='.$geographicAreaId.'#disbursementsChartContainer', array('attributes' => array('target' => '_blank')));
    $links['items'][] = l(t('Contacts'), 'http://www.theglobalfund.org/en/portfolio/country/contacts/?loc=' . $country->iso_code_3.'&k='.$geographicAreaId, array('attributes' => array('target' => '_blank')));
    //$links['items'][] = l(t('Principal Recipient(s)'), 'http://portfolio.theglobalfund.org/en/Contacts/PrincipalRecipients/' . $country->iso_code_3, array('attributes' => array('target' => '_blank')));
    //$links['items'][] = l(t('LFA'), 'http://portfolio.theglobalfund.org/en/Contacts/LocalFundAgents/' . $country->iso_code_3, array('attributes' => array('target' => '_blank')));

    return $links;
  }

  function country_grant_by_disease($country_id, $start_date, $end_date) {
    //Approved funding for the countries per disease
    $sql = "SELECT dc.id, dc.name
FROM gf_grant gg
INNER JOIN disease_component dc ON gg.disease_component_id = dc.id
WHERE gg.country_id = :country_id
GROUP BY gg.disease_component_id";

    $result = db_query($sql, array(':country_id' => $country_id));

    $country_grant_info = array();

    $service = new CountryChartService();
    foreach ($result as $disease) {
      $row = array();
      $row[] = array('data' => $disease->name, 'class' => 'apw-name-right-br');

      //$row[] = array('data' => _format_money_amount($disease->approved_amount, 1), 'class' => 'apw-numeric-right-br');
      //get the agreement amount for the current country
      $sql = "SELECT SUM(ga.agreement_amount) agreement_amount
FROM gf_grant gg
INNER JOIN grant_agreement ga ON ga.gf_grant_id = gg.id
WHERE gg.country_id = " . $country_id . ' AND gg.disease_component_id = ' . $disease->id;

      $disbursed_result = db_query($sql);
      $data = $disbursed_result->fetchObject();
      $row[] = array('data' => _format_money_amount($data->agreement_amount, 1), 'class' => 'apw-numeric-right-br');

      //get the disbursement for the current country for the current disease
      $sql = "SELECT SUM(gdr.disbursed_amount) as disbursed_amount
FROM gf_grant gg 
INNER JOIN gf_disbursement_rating gdr ON gdr.gf_grant_id = gg.id
WHERE gg.country_id = " . $country_id . ' AND gg.disease_component_id = ' . $disease->id;

      $disbursed_result = db_query($sql);
      $data = $disbursed_result->fetchObject();
      $row[] = array('data' => _format_money_amount($data->disbursed_amount, 1), 'class' => 'apw-numeric-right-br');

      //get the performance rating for this country and also the data for the map

      $country_rating_info = $service->country_performance_rating($country_id, 0, $start_date, $end_date, $disease->id);
      $row[] = array('data' => $country_rating_info[2], 'class' => 'apw-numeric');
      $row[] = array('data' => $country_rating_info[0], 'class' => 'rating-' . $country_rating_info[1]); //rating
      $row[] = array('data' => $country_rating_info[3], 'class' => 'rating-' . $country_rating_info[4]);

      $country_grant_info[] = $row;
    }
    $country_rating_info = $service->country_performance_rating($country_id, 0, $start_date, $end_date);
    //get the totals for the approved amount, grant agreement and the disbursed amounts
    $row = array(
      'data' => array(
        array('data' => '<strong>Total</strong>', 'class' => 'apw-total-row-br'),
        //approved amount
        //$row[] = array('data' => _format_money_amount(_total_approved_amount($country_id), 1), 'class' => 'apw-total-row-br apw-numeric ');
//grant agreement
        array('data' => _format_money_amount(_total_agreement_amount($country_id), 1), 'class' => 'apw-total-row-br apw-numeric'),
//disbursements
        array('data' => _format_money_amount(_total_disbursed_amount($country_id), 1), 'class' => 'apw-total-row-br apw-numeric'),
        //get the total number of valid ratings, average rating and latest rating
        array('data' => $country_rating_info[2], 'class' => 'apw-total-row-br apw-numeric'), //number of ratings
        //get the average rating for this country
        array('data' => $country_rating_info[0], 'class' => 'apw-total-row-br rating-' . $country_rating_info[1]), //average rating
        array('data' => $country_rating_info[3], 'class' => 'apw-total-row-br rating-' . $country_rating_info[4]), //latest rating
      ), 'class' => array('tablesorter-no-sort'),
      'data-sorter' => array('false'),
    );


    $country_grant_info[] = $row;

    return $country_grant_info;
  }

}

function clean_statistic_result_values($string) {

  $string = strtok($string, ' '); 
  $string = str_replace(' ', '-', $string);  
  $string = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $string);
 
  if (floatval($string) == 0) {
    return 0;
  }
  else {
    return floatval($string);
  }
}

function is_array_empty($arr) {
  if (is_array($arr)) {
    foreach ($arr as $key => $value) {
      if (!empty($value) || $value != null || $value != "") {
        return true;
        break; //stop the process we have seen that at least 1 of the array has value so its not empty
      }
    }
    return false;
  }
}

?>
