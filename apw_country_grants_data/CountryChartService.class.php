<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CountryChartService
 *
 * @author kinyua
 */
class CountryChartService {

    public function country_comp_stats_chart_data($country_id, $rating_threshold = 3, $start_date = null, $end_date = null) {

        $country_components_stats_chats_data = array();
        $country_components_stats_chats_data_add = array(); 
        //'#a479e2', '#7798BF', '#D81D16', '#CC9900', '#DDDF0D', '#5efb6e'
        $colors = array('#a479e2', '#D81D16', '#7798BF', '#CC9900', '#DDDF0D', '#5efb6e'); 
        $diseases = db_query("SELECT * FROM disease_component ORDER BY name DESC");
        $count = 0;
        $color = 0;

        foreach ($diseases as $disease) {
            $sql = 'SELECT COUNT(*)
FROM gf_grant gg
INNER JOIN disease_component dc ON gg.disease_component_id = dc.id
INNER JOIN country c ON gg.country_id = c.id 
WHERE c.id = :country_id AND dc.name =:disease_name';

            $args = array(
                ':country_id' => $country_id,
                ':disease_name' => $disease->name
            );

            $grants_count = db_query($sql, $args)->fetchField();

            if ($grants_count > 0) {
                $country_components_stats_chats_data[] = preg_replace('/[^A-Za-z0-9\. -]/', ' ', $disease->name);
                $d = new stdClass();
                $d->y = (int) $grants_count;
                $d->color = $colors[$color];

                $drilldown = new stdClass();
                $drilldown->name = preg_replace('/[^A-Za-z0-9\. -]/', '', $disease->name);
                $drilldown->color = $colors[$color];
                //get all sets of grant categories 
                // SELECT * FROM apw2_production.grant_status order by s;
                $statuses_name = array();
                $statuses_count = array();

                $statuses = db_query("SELECT id FROM grant_status");

                foreach ($statuses as $status) {
                    $status_count = 1;
                    $sql = 'SELECT  gs.name as name,gs.id as grant_status_id
                    FROM gf_grant gg
                    INNER JOIN disease_component dc ON gg.disease_component_id = dc.id
                    INNER JOIN country c ON gg.country_id = c.id
                    LEFT JOIN grant_status gs on grant_status_id = gs.id
                    WHERE c.id = :country_id AND dc.name =:disease_name';
                    $args = array(
                        ':country_id' => $country_id,
                        ':disease_name' => $disease->name
                    );

                    $grants_status = db_query($sql, $args);
                    $current_grant_status = '';

                    foreach ($grants_status as $grant_status) {
                        if ($status->id == $grant_status->grant_status_id) {
                            if ($current_grant_status != $grant_status->name) {
                                $statuses_name[] = $grant_status->name;
                                $current_grant_status = $grant_status->name;
                            }
                            $statuses_count[$grant_status->name] = $status_count;
                            $status_count = $status_count + 1;
                        }
                    }
                }
                $status_no = array();
                foreach ($statuses_count as $status_count) {
                    $status_no[] = $status_count;
                }

                if ($grants_count > $grants_count - array_sum($status_no) && ($grants_count - array_sum($status_no)) != 0) {
                    $statuses_name[] = 'N/A';
                    $status_no[] = $grants_count - array_sum($status_no);
                }

                $statuses_name = array_values(array_unique($statuses_name));

                //var_dump($statuses_name,$status_no);

                $drilldown->categories = $statuses_name;
                $drilldown->data = $status_no;

                $d->drilldown = $drilldown;
                $country_components_stats_chats_data_add[] = $d;
                //var_dump($country_components_stats_chats_data_add);
            }


            $count = $count + 1;
            $color = $color + 1;
        }

        return array($country_components_stats_chats_data, $country_components_stats_chats_data_add);
    }

    public function prep_rating_data($country_id, $rating_threshold = 3, $start_date = null, $end_date = null) {

        $color_holder = array('#a479e2', '#7798BF', '#D81D16', '#CC9900', '#DDDF0D', '#5efb6e');

        $rating_config = array();
        $weight_holder = array();
        $weights = db_query("SELECT * FROM performance_rating WHERE gf_rating != 'A' ORDER BY rating_weight DESC");
        foreach ($weights as $record) {
            $rating_config[$record->gf_rating] = $record->rating_weight;
            $weight_holder[] = $record->lower_bound;
        }


        //fetch all the grants for this country
        $sql = "SELECT gg.id, gg.grant_number, dc.name disease_name
FROM gf_grant gg
INNER JOIN disease_component dc ON gg.disease_component_id = dc.id
INNER JOIN country c ON gg.country_id = c.id
WHERE c.id = :country_id ORDER BY gg.disease_component_id, gg.grant_start_date  DESC";

        $result = array();
        $grants_data = db_query($sql, array(':country_id' => $country_id));

        $current_component_name = '';
        $previous_component_name = '';
        $count = 0;
        $data = array();
        $coun = array();
        $grant_name = array();
        $grant_name2 = array();

        foreach ($grants_data as $record) {
            $data2 = array();
            $d = new stdClass();
            $grant_rating_info = $this->assign_rating_weight($record->id, $rating_config, $weight_holder, $start_date, $end_date);
            if ($grant_rating_info[1] >= $rating_threshold) {
                $d = $grant_rating_info[0];
                $data[] = $d;
                $data2[] = $d;
                $current_component_name = $record->disease_name;
                if ($previous_component_name != $current_component_name) {

                    $coun[] = $count;
                    $previous_component_name = $record->disease_name;
                }
                $yseries = new stdClass();
                $yseries->y = $d;
                $series = new stdClass();
                //$series->name = $record->disease_name; //$disease_name;
                $series->name = $record->grant_number;
                $grant_name2[] = $record->grant_number;
                $sql = "SELECT id FROM apw2_production.disease_component where name=:dc_name order by name desc";
                $color_id = (int) db_query($sql, array(':dc_name' => $record->disease_name))->fetchField();

                if ($color_id == 2) {
                    $series->color = $color_holder[0];
                    $yseries->color = $color_holder[0];
                } elseif ($color_id == 3) {
                    $series->color = $color_holder[1];
                    $yseries->color = $color_holder[1];
                } elseif ($color_id == 4) {
                    $series->color = $color_holder[2];
                    $yseries->color = $color_holder[2];
                } elseif ($color_id == 1) {
                    $series->color = $color_holder[3];
                    $yseries->color = $color_holder[3];
                } elseif ($color_id == 6) {
                    $series->color = $color_holder[4];
                    $yseries->color = $color_holder[4];
                }
                $series->data = $data2;

                $result[] = $series;
                $grant_name[] = $yseries;
                $count = $count + 1;
            }
        }





        //now get the total count of the grants for this country... we are paranoid with data intergrity
        $sql = "SELECT count(distinct gg.id) count
FROM progress_update pu 
INNER JOIN gf_grant gg ON pu.gf_grant_id = gg.id
INNER JOIN disease_component dc ON gg.disease_component_id = dc.id
INNER JOIN country c ON gg.country_id = c.id
WHERE c.id = :country_id AND rating is not null AND rating != '' AND rating != 'x' AND rating != 'NR' AND rating != 'Non' AND rating != 'N/A'";
        if (null != $start_date) {
            $sql .= " AND pu_end_date >= '" . $start_date . "'";
        }
        if (null != $end_date) {
            $sql .= " AND pu_end_date <= '" . $end_date . "'";
        }

        $grant_count = db_query($sql, array(':country_id' => $country_id))->fetchField();
        /**
          $result2 = array();
          $dc_results=array();
          $loopcounter=0;
          $previous_index=0;
          // $grant_name_res= array();
          foreach ($coun as $value) {

          if ($value != 0 && $loopcounter==0) {
          $result2 = $result[$value-1]->data;
          $previous_index=count($result[$value-1]->data);
          $result[$value-1]->data=  $result2;
          $dc_results[]=$result[$value-1];
          // $grant_name_res[]=$grant_name[$value-1];
          // $grant_name[]=$result[$value-1]->grant;
          // $result[$value-1]->data[]=$result2[$loopcounter];
          // $result2[]=  $result[$value-1];
          }
          if($value != 0 && $loopcounter>0){
          $result2=array_slice($result[$value-1]->data, $previous_index, count($result[$value-1]->data));
          $previous_index=count($result[$value-1]->data);
          $result[$value-1]->data=  $result2;
          $dc_results[]=$result[$value-1];
          //$grant_name_res[]=$grant_name[$value-1];
          // $result[$value-1]->data[]=$result2[$loopcounter];
          //  $result2[]=  $result[$value-1];
          }
          $loopcounter=$loopcounter+1;

          }
         * 
         */
        // $result2=array_slice($result[((int) $grant_count)-1]->data, $previous_index, count($result[((int) $grant_count)-1]->data));
        // $result[((int) $grant_count)-1]->data=  $result2;
        // $dc_results[]= $result[((int) $grant_count)-1];
        //  $grant_name_res[]=$grant_name[((int) $grant_count)-1];

        return array($grant_name, $grant_name2, $grant_count, db_query("SELECT name FROM country WHERE id = :country_id", array(':country_id' => $country_id))->fetchField());
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
                FROM progress_update
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
                } else {
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

    private function assign_rating_weight($grant_id, $rating_config, $weight_holder, $start_date = null, $end_date = null) {

        #fetch all the dibursements for this grant, from the rating data
        $sql = "SELECT id, rating
                FROM progress_update
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
                } else {
                    $upper_bound = $weight_holder[$i - 1];
                    $lower_bound = $weight_holder[$i];
                }

                if ($average_rating >= $lower_bound && $average_rating < $upper_bound) {
                    $converted_weight = ($lower_bound + $upper_bound) / 2;

                    foreach ($rating_config as $gf_rating => $weight) {
                        if ($weight == $converted_weight) {
                            $grant_rating = (int) $weight;
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
                } else {
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

    public function country_lates_avg_performance_rating($country_id, $rating_threshold = 3, $start_date = null, $end_date = null, $disease_id = null, $count = null) {

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
        $sql .= "group by pu_end_date ORDER BY pu_end_date DESC";


        $sql .= " LIMIT " . $count;

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
                } else {
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
                            break;
                        }
                    }
                }
            }
        }

        return $country_rating_info; // . ' (' . number_format($average_rating, 0) . ')';
    }

}

?>