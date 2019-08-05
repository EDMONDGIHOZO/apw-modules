<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PerformanceAnalyzerImpl
 *
 * @author kinyua
 */
class PerformanceAnalyzerImpl {

    //put your code here

    private $secs_in_a_day = 86400; //(24 * 60 * 60);$period_start

    private function grant_rating_threshold($grants_selections, $period_start, $rating_counter) {

        $all_grants = array();

        foreach ($grants_selections as $grant_selection) {
            $sql = "
            SELECT gf_grant_id, COUNT(id)
FROM progress_update
WHERE pu_start_date IS NOT NULL AND pu_end_date IS NOT NULL AND
gf_grant_id IN (" . implode(", ", $grant_selection) . ") AND
pu_start_date >= :yr_st
AND rating is NOT NULL AND rating != '' AND rating != 'x' AND rating != 'NR'
GROUP BY gf_grant_id
HAVING COUNT(id) >= :count_num";

            $rating_result = db_query($sql, array(':yr_st' => $period_start . '-01-01', ':count_num' => $rating_counter));
            $filtered_grants = array();

            foreach ($rating_result as $data) {
                $filtered_grants[] = $data->gf_grant_id;
            }

            if ($filtered_grants) {
                $all_grants[] = $filtered_grants;
            }
        }

        return $all_grants;
    }

    public function analyze_selection($grants_selections, $period_start, $rating_counter) {

        if (empty($period_start)) {
            $period_start = '2003';
        }
        if (empty($rating_counter)) {
            $rating_counter = 1;
        }

        //get the grants whose valid ratings are equal or more than the minimum rating specified by the user
        $grants_selections = $this->grant_rating_threshold($grants_selections, $period_start, $rating_counter);
        //first get the earliest and latest valid timeline for all ratings in the grants specified
        $all_grants = array();
        foreach ($grants_selections as $grant_selection) {
            $all_grants = array_merge($all_grants, $grant_selection);
        }

        $sql = "SELECT MIN(pu_start_date) pu_start_date, MAX(pu_end_date) pu_end_date,
            MIN(UNIX_TIMESTAMP(pu_start_date)) earliest_unix_start_date, MAX(UNIX_TIMESTAMP(pu_end_date)) latest_unix_end_date
FROM progress_update
WHERE pu_start_date IS NOT NULL AND pu_end_date IS NOT NULL AND
gf_grant_id IN (" . implode(", ", $all_grants) . ") AND
pu_start_date >= :yr_st
AND rating is NOT NULL AND rating != '' AND rating != 'x' AND rating != 'NR'";

        $time_limit_result = db_query($sql, array(':yr_st' => $period_start . '-01-01'))->fetchObject();
        $time_limit = array();

        if (!(empty($time_limit_result->earliest_unix_start_date))) {
            $current_unix_date = $time_limit_result->earliest_unix_start_date;

            while ($current_unix_date <= $time_limit_result->latest_unix_end_date) {
                $time_limit[] = format_date($current_unix_date, 'custom', 'M Y', variable_get('date_default_timezone', 0));

                $current_unix_date += $this->secs_in_a_day;
            }
        }
        //upto here we have the requisite info about the earliest and latest dates and also have built a daily sequence holder

        $all_rating_catalog = array(); //will store all the ratings information
        $grant_counter_catalog = array(); //this will store the grant counter information

        foreach ($grants_selections as $grant_selection) {
            $criteria_rating_catalog = array();

            foreach ($grant_selection as $grant_id) {
                //now fetch the ratings for a particular grant
                $sql = "SELECT UNIX_TIMESTAMP(pu_start_date) unix_start_date, UNIX_TIMESTAMP(pu_end_date) unix_end_date,
CASE rating
	WHEN 'A1' THEN 4
	WHEN 'A' THEN 3.5
	WHEN 'A2' THEN 3
	WHEN 'B1' THEN 2
	WHEN 'B2' THEN 1
ELSE 0
END rating_weight
FROM progress_update
WHERE pu_start_date IS NOT NULL AND pu_end_date IS NOT NULL AND
gf_grant_id = :grant_id AND
pu_start_date >= :pu_st
AND rating is NOT NULL AND rating != '' AND rating != 'x' AND rating != 'NR'
ORDER BY pu_start_date ASC";

                $rating_result = db_query($sql, array(':grant_id' => $grant_id, ':pu_st' => $time_limit_result->pu_start_date));
                $grant_rating = array();

                foreach($rating_result as $data) {

                    $rating_info['start_date'] = $data->unix_start_date;
                    $rating_info['end_date'] = $data->unix_end_date;
                    $rating_info['rating_weight'] = $data->rating_weight;

                    $grant_rating[] = $rating_info;
                }

                $grant_daily_rating_weight = array();
                if ((!(empty($grant_rating)))) {
                    //means this grant has some rating, so lets go ahead and prepare an array that has the respective weight for all the days
                    //pad the ratings upto today, if necessary, starting from the earliest valid date
                    $current_unix_date = $time_limit_result->earliest_unix_start_date;

                    while ($current_unix_date < $grant_rating[0]['start_date']) {
                        $grant_daily_rating_weight[] = -1; //means there was no rating here
                        $current_unix_date += $this->secs_in_a_day;
                    }
                    $current_unix_date = null; //reset this
                    //go ahead and use the ratings for the grant
                    foreach ($grant_rating as $period_rating) {
                        if (!empty($current_unix_date)) {

                            while ($current_unix_date < $period_rating['start_date']) {
                                $grant_daily_rating_weight[] = -1; //means there was no rating here
                                $current_unix_date += $this->secs_in_a_day;
                            }
                        }

                        $current_unix_date = $period_rating['start_date'];

                        while ($current_unix_date <= $period_rating['end_date']) {
                            $grant_daily_rating_weight[] = $period_rating['rating_weight']; //means there was no rating here
                            $current_unix_date += $this->secs_in_a_day;
                        }
                    }
                    //pad towards the end
                    $current_unix_date = $grant_rating[sizeof($grant_rating) - 1]['end_date'];

                    while ($current_unix_date < $time_limit_result->latest_unix_end_date) {
                        $grant_daily_rating_weight[] = -1; //means there was no rating here
                        $current_unix_date += $this->secs_in_a_day;
                    }

                    $criteria_rating_catalog[] = $grant_daily_rating_weight;
                }
            } //end foreach ($grant_selection as $grant_id)
            //go ahead and get the average rating weights for the grants
            $processed_result = $this->calculate_average_weight($time_limit, $criteria_rating_catalog);

            $all_rating_catalog[] = $processed_result[0];
            $grant_counter_catalog[] = $processed_result[1];
        } //foreach ($grants_selections as $grant_selection)
        //return $all_rating_catalog;
        //optimize the data
        //return $this->optimizeRatingChartData($time_limit, $all_rating_catalog);
        return array($time_limit, $all_rating_catalog, $grant_counter_catalog); //$time_limit;//$grant_daily_rating_weight;
    }

    private function optimizeRatingChartData($time_limit, $all_rating_catalog) {

        $scaled_time_limit = array();

        $scaled_rating_catalog = array();

        $quotient = floor(sizeof($time_limit) / 2000);

        if (0 == $quotient) {
            $quotient = 1;
        }

        for ($i = 0; $i < sizeof($time_limit); $i += $quotient) {
            $scaled_time_limit[] = $time_limit[$i];
        }

        foreach ($all_rating_catalog as $rating_catalog) {

            $tmp_arr = array();

            for ($i = 0; $i < sizeof($time_limit); $i += $quotient) {
                $tmp_arr[] = $rating_catalog[$i];
            }

            $scaled_rating_catalog[] = $tmp_arr;
        }

        return array($scaled_time_limit, $scaled_rating_catalog);
    }

    private function calculate_average_weight($day_timeline, $rating_catalog) {

        $weighted_rating = array();
        $grant_counter = array();

        foreach ($day_timeline as $key => $day) {
            $cumulative_weight = 0;
            $valid_rating_counter = 0;

            foreach ($rating_catalog as $catalog) {
                $rating_weight = $catalog[$key];

                if (-1 != $rating_weight) {
                    $cumulative_weight += $rating_weight;
                    $valid_rating_counter += 1;
                }
            }

            $average_weight = null;
            if (0 != $valid_rating_counter) {
                $average_weight = ($cumulative_weight / $valid_rating_counter);
            }

            $weighted_rating[] = $average_weight; //array(date('Y-m-d', $day), $average_weight);
            $grant_counter[] = $valid_rating_counter;
        }

        return array($weighted_rating, $grant_counter);
    }

}

?>
