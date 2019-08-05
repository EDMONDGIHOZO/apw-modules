<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GrantDetail
 *
 * @author kinyua
 */
class GrantDetail {

    public function retrieve_grant_details($grant_id, $grant_number, $start_date, $end_date) {
        $sql = "SELECT gg.id grant_id, gg.grant_number, gg.grant_title, UNIX_TIMESTAMP(gg.grant_end_date) grant_end_date,
c.id country_id, c.name country_name, c.iso_code_3 iso_code_3,
d.name disease, pr.name principal_recipient, prt.name pr_type,
UNIX_TIMESTAMP(gg.grant_start_date) grant_start_date,
gg.fpm_name, gg.fpm_email,
SUM(ga.agreement_amount) agreement_amount,
SUM(ga.committed_amount) committed_amount
FROM gf_grant gg
INNER JOIN grant_agreement ga ON ga.gf_grant_id = gg.id
LEFT JOIN principal_recipient pr ON gg.principal_recipient_id = pr.id
LEFT JOIN principal_recipient_type prt ON pr.principal_recipient_type_id = prt.id
INNER JOIN disease_component d ON gg.disease_component_id = d.id
INNER JOIN country c ON gg.country_id = c.id ";

        if (null != $grant_id) {
            $sql .= " WHERE gg.id = :grant_id";

            $result = db_query($sql, array(':grant_id' => $grant_id));
        } else {
            $sql .= " WHERE gg.grant_number = :grant_number";

            $result = db_query($sql, array(':grant_number' => $grant_number));
        }

        $grant_info = array();
        $data = $result->fetchObject();

        if ($data->grant_number) {
            $row = array();
            $row[] = t('Grant Number');
            $row[] = $data->grant_number;
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Country');
            //$row[] = l(t($data->country_name), 'country/' . $data->country_id);
            $row[] = $data->country_name;
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Disease');
            $row[] = t($data->disease);
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Principal Recipient');
            #$row[] = l($data->principal_recipient, 'http://portfolio.theglobalfund.org/en/Contacts/PrincipalRecipients/' . $data->grant_number, array('attributes' => array('target' => '_blank')));
            $row[] = t($data->principal_recipient);
            $grant_info[] = $row;

            $row = array();
            $row[] = t('PR Type');
            $row[] = t($data->pr_type);
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Official Start Date');
            $row[] = format_date($data->grant_start_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0));
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Total Agreement Amount');
            $row[] = '$' . number_format($data->agreement_amount);
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Total Committed Amount');
            $row[] = '$' . number_format($data->committed_amount);
            $grant_info[] = $row;

            //get the disbursement amount
            $row = array();
            $row[] = t('Disbursed thus far');
            $sql = 'SELECT SUM(disbursed_amount) disbursed_amount FROM gf_disbursement_rating WHERE gf_grant_id = :grant_id';
            $dis = db_query($sql, array(':grant_id' => $data->grant_id))->fetchField();
            $row[] = '$' . number_format($dis);
            $grant_info[] = $row;

            $dis_per = 0;
            if ($data->agreement_amount > 0) {
                $dis_per = ($dis / $data->agreement_amount) * 100;
            }
            $row = array();
            $row[] = t('Disbursed as % of Agmt Amt');
            $row[] = round($dis_per, 0) . '%';
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Latest disbursement');
            $sql = 'SELECT disbursed_amount, UNIX_TIMESTAMP(disbursement_date) disbursement_date FROM gf_disbursement_rating WHERE gf_grant_id = :grant_id AND disbursed_amount != 0 ORDER BY disbursement_date DESC LIMIT 1';
            $dis = db_query($sql, array(':grant_id' => $data->grant_id))->fetchObject();
            $row[] = '$' . number_format($dis->disbursed_amount) . ' (' . ( $dis->disbursement_date ? (format_date($dis->disbursement_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0))) : 'n/a' ) . ')';
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Grant Agreement End Date');
            $row[] = format_date($data->grant_end_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0));
            $grant_info[] = $row;

            //get average rating for this grant
            $service = new CountryChartService();
            $rating_info = $service->retrieve_grant_rating($data->grant_id, 1, $start_date, $end_date);
            $row = array();
            $row[] = t('Ratings since January 2010');
            $row[] = 'Number: ' . $rating_info[2] . ' Average: ' . $rating_info[0] . ' Latest: ' . $rating_info[3];
            $grant_info[] = $row;
            
                $json = file_get_contents('http://data-service.theglobalfund.org/v1/feeds/views/VGrantAgreements');
    $obj = json_decode($json,true);
    $geographicAreaId='';
    $grantAgreementId='';

    foreach($obj as $doc1)
    {
        if($doc1['grantAgreementNumber']==$data->grant_number){
        $grantAgreementId=$doc1['grantAgreementId'];
        
      }
   }

            $row = array();
            $row[] = t('Global Fund links');
            $gf_link =
                l('GF page', 'http://www.theglobalfund.org/en/portfolio/country/grant/?k='.$grantAgreementId.'&grant='.$data->grant_number, array('attributes' => array('target' => '_blank'))) 
                . ' | ' .
                l('Grant performance report', 'http://www.theglobalfund.org/ProgramDocuments/' . substr($data->grant_number, 0, 3) . '/' . $data->grant_number . '/' . $data->grant_number . '_GPR_0_en', array('attributes' => array('target' => '_blank')));
           /* if ($data->iso_code_3) {
                $gf_link .= ' | ' 
                 . l(t('LFA'), 'http://portfolio.theglobalfund.org/en/Contacts/LocalFundAgents/' . $data->iso_code_3, array('attributes' => array('target' => '_blank'))) .
                        ' | ' . l(t('CCM'), 'http://portfolio.theglobalfund.org/en/Contacts/CountryCoordinatingMechanisms/' . $data->iso_code_3, array('attributes' => array('target' => '_blank')));
            }
            
          **/
               foreach($obj as $doc)
    {
                
      if($doc['grantAgreementNumber']==$data->grant_number){
            
        $geographicAreaId=$doc['geographicAreaId'];
         
  
      }
   }
             if ($data->iso_code_3) {
                $gf_link .= ' | ' 
                 .l(t('Contacts'), 'http://www.theglobalfund.org/en/portfolio/country/contacts/?loc=' . $data->iso_code_3.'&k='.$geographicAreaId, array('attributes' => array('target' => '_blank'))) ;
                             }
            $row[] = $gf_link;
            $grant_info[] = $row;

            $row = array();
            $row[] = t('Fund Portfolio Manager');
            $row[] = $data->fpm_name . ($data->fpm_email != null ? ' (' . $data->fpm_email . ')' : '');
            $grant_info[] = $row;
        }

        return array($grant_info, $data->grant_id, $data->country_id);
    }

    public function retrieve_grant_disbursements($grant_id) {
        $disbursements = array();
        $total_disbursed_amount = 0;

        $sql = "SELECT d.id, d.disbursement_number, UNIX_TIMESTAMP(d.disbursement_date) disbursement_date, d.disbursed_amount
FROM gf_disbursement_rating d
INNER JOIN gf_grant gg ON d.gf_grant_id = gg.id
WHERE
gg.id = :grant_id AND disbursed_amount != 0
ORDER BY d.disbursement_date ASC";
        
        $result = db_query($sql, array(':grant_id' => $grant_id));

        foreach($result as $data) {
            $total_disbursed_amount += $data->disbursed_amount;

            $row = array();

            //$row[] = array('data' => $data->disbursement_number, 'class' => 'apw-name-right-br');
            $row[] = array('data' => format_date($data->disbursement_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => '$' . number_format($data->disbursed_amount), 'class' => 'apw-numeric-right-br');

            $disbursements[] = $row;
        }
        //totals
        $sql = "SELECT
SUM(agreement_amount) agreement_amount
FROM grant_agreement
WHERE gf_grant_id = :grant_id";

        $agreement_amount = db_query($sql, array(':grant_id' => $grant_id))->fetchField();

        $row = array();

        //$row[] = array('data' => '<strong>TOTAL</strong>', 'class' => 'apw-total-row-br');
        $row[] = array('data' => ' ', 'class' => 'apw-total-row-br');
        $row[] = array('data' => '<strong>$' . number_format($total_disbursed_amount) . '</strong><br/>' . ($agreement_amount > 0 ? round($total_disbursed_amount / $agreement_amount * 100, 0) : 0) . '% of agreement amount', 'class' => 'apw-total-row-br apw-numeric');

        $disbursements[] = $row;

        return $disbursements;
    }

    public function retrieve_grant_reporting_periods($grant_id, $start_date, $end_date) {

        $periods = array();
         

        $sql = "SELECT d.id, d.pu_number, UNIX_TIMESTAMP(d.pu_start_date) pu_start_date, UNIX_TIMESTAMP(d.pu_end_date) pu_end_date, d.rating
FROM progress_update d
INNER JOIN gf_grant gg ON d.gf_grant_id = gg.id
WHERE
gg.id = :grant_id AND pu_start_date is not null and pu_end_date is not null
GROUP BY d.pu_number, d.pu_start_date, d.pu_end_date
ORDER BY d.pu_start_date ASC";

        $result = db_query($sql, array(':grant_id' => $grant_id));

        $buffer = array();
        $secs_in_a_day = 86400; //= (24 * 60 * 60);

        foreach($result as $data) {
            $row = array();

            $row[] = array('data' => $data->pu_number, 'class' => 'apw-name-right-br');
            $row[] = array('data' => format_date($data->pu_start_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => format_date($data->pu_end_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => (($data->rating == '' || $data->rating == 'x' || $data->rating == null) ? 'n/a' : $data->rating), 'class' => 'apw-numeric-right-br');

            $buffer_size = sizeof($buffer);

            if ($buffer_size > 0) {
                $previous_period_end_date = $buffer[$buffer_size - 1][0];

                if (($data->pu_start_date - $previous_period_end_date) > $secs_in_a_day) {
                    $extra_row = array();

                    $extra_row[] = array('data' => '', 'class' => 'apw-name-right-br');
                    $extra_row[] = array('data' => format_date(($previous_period_end_date + $secs_in_a_day), 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
                    $extra_row[] = array('data' => format_date(($data->pu_start_date - $secs_in_a_day), 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
                    $extra_row[] = array('data' => 'n/a', 'class' => 'apw-numeric-right-br');

                    $periods[] = $extra_row;
                }
            }

            $buffer[] = array($data->pu_end_date);

            $periods[] = $row;
        }
        
         $row = array();
            $row[] = array('data' => '', 'class' => 'apw-name-right-br');
            $row[] = array('data' => '', 'class' => 'apw-name-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br'); 
          $periods[] = $row;  
          
        return $periods;
    }
    
    public function retrieve_grant_performance_indicators($grant_id) {
        $gr_perf_indicators = array(); 

        $sql = " SELECT indicator.name as indicator_name ,sda.name as service_area_name ,UNIX_TIMESTAMP(gr_in_r.period_start) as start_period,
UNIX_TIMESTAMP(gr_in_r.period_end) as end_period ,gr_in_r.target as target ,gr_in_r.actual as result from apw2_production.grant_indicator_result gr_in_r 
 join indicator on gr_in_r.indicator_id=indicator.id
 join service_delivery_area sda on (sda.id =gr_in_r.service_delivery_area_id ) where gr_in_r.gf_grant_id=:grant_id";
        
        $result = db_query($sql, array(':grant_id' => $grant_id));
  

        foreach($result as $data) {
            $row = array();

            $row[] = array('data' => $data->indicator_name, 'class' => 'apw-name-right-br');
            $row[] = array('data' => $data->service_area_name, 'class' => 'apw-name-right-br');
            $row[] = array('data' => format_date($data->start_period, 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => format_date($data->end_period, 'custom', 'j F Y', variable_get('date_default_timezone', 0)), 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => ( $data->target), 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => ( $data->result), 'class' => 'apw-numeric-right-br');
 
            $gr_perf_indicators[] = $row;
        }
        
         $row = array();
   $row[] = array('data' => '', 'class' => 'apw-name-right-br');
            $row[] = array('data' => '', 'class' => 'apw-name-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' =>'', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
 
       $gr_perf_indicators[] = $row;

        return $gr_perf_indicators;
    }
          
    public function retrieve_grant_conditions($grant_id) {
        $gr_conditions = array(); 

        $sql = "SELECT grant_condition.name as condition_name ,condition_status.name as condition_status_name 
,condition_type.name as condition_type_name,
condition_tied_to_type,
condition_tied_to_comment,condition_comment
from apw2_production.grant_condition  
 left join condition_status on condition_status.id=condition_status_id
 left join condition_type  on (condition_type.id = condition_type_id )
 where grant_condition.gf_grant_id=:grant_id";
        
        $result = db_query($sql, array(':grant_id' => $grant_id));
  

        foreach($result as $data) {
            $row = array();

            $row[] = array('data' => $data->condition_name, 'class' => 'apw-name-right-br');
            $row[] = array('data' => $data->condition_status_name, 'class' => 'apw-name-right-br');
            $row[] = array('data' => $data->condition_type_name,  'class' => 'apw-name-right-br'); 
            $row[] = array('data' => $data->condition_tied_to_type, 'class' => 'apw-name-right-br');
           //$row[] = array('data' => $data->condition_tied_to_comment, 'class' => 'apw-name-right-br');
            $row[] = array('data' => $data->condition_comment,  'class' => 'apw-name-right-br');  
 
            $gr_conditions[] = $row;
        }
        
         $row = array();
   $row[] = array('data' => '', 'class' => 'apw-name-right-br');
            $row[] = array('data' => '', 'class' => 'apw-name-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' =>'', 'class' => 'apw-numeric-right-br');
            $row[] = array('data' => '', 'class' => 'apw-numeric-right-br');
 
       $gr_conditions[] = $row;

        return $gr_conditions;
    }
}

?>
