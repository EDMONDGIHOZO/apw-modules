<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GrantChartService
 *
 * @author kinyua
 */
class GrantChartService {

    private $secs_in_a_day = 86400; //= (24 * 60 * 60);

    public function prep_budget_data($grant_ids, $grant_start_date, $grant_end_date) {

        //$x_axis = array();
        $budget_values = array();
        $date_line = array();

        //there can be weird situations where the earliest disbursement date is earlier than the earliest budget start date
        $sql = "SELECT UNIX_TIMESTAMP(MIN(CONVERT_TZ(start_date, '+00:00', 'SYSTEM'))) budget_start_date, 
            MAX(CONVERT_TZ(end_date, '+00:00', 'SYSTEM')) budget_end_date
FROM budget
WHERE gf_grant_id IN (" . implode(',', $grant_ids) . ")";

        $budget_record = db_query($sql)->fetchObject();
        if (null != $budget_record->budget_start_date && $budget_record->budget_start_date < $grant_start_date) {
            $grant_start_date = $budget_record->budget_start_date - $this->secs_in_a_day;
        }

        //get the earliest time; could either be the approval date or the first disbursement date
        $sql = "SELECT UNIX_TIMESTAMP(MIN(CONVERT_TZ(approval_date, '+00:00', 'SYSTEM'))) approval_date
FROM grant_agreement
WHERE agreement_amount > 0 AND gf_grant_id IN (" . implode(',', $grant_ids) . ")";

        $data = db_query($sql)->fetchObject();
        if (null != $data->approval_date && $data->approval_date < $grant_start_date) {
            $grant_start_date = $data->approval_date - $this->secs_in_a_day;
        }

        $sql = "SELECT UNIX_TIMESTAMP(MIN(CONVERT_TZ(pu_start_date, '+00:00', 'SYSTEM'))) min_date,
            UNIX_TIMESTAMP(MAX(CONVERT_TZ(pu_end_date, '+00:00', 'SYSTEM'))) max_date
FROM progress_update
WHERE gf_grant_id IN (" . implode(',', $grant_ids) . ")";

        $data = db_query($sql)->fetchObject();

        //anomaly to be detected: there could be instances where there is a disbursement but no budget
        if (null != $data->min_date && $data->min_date < $grant_start_date) {
            $grant_start_date = $data->min_date - $this->secs_in_a_day;
        }

        $sql = "SELECT UNIX_TIMESTAMP(MIN(CONVERT_TZ(disbursement_date, '+00:00', 'SYSTEM'))) min_date
FROM gf_disbursement_rating
WHERE gf_grant_id IN (" . implode(',', $grant_ids) . ")";

        $data = db_query($sql)->fetchObject();

        //anomaly to be detected: there could be instances where there is a disbursement but no budget
        if (null != $data->min_date && $data->min_date < $grant_start_date) {
            $grant_start_date = $data->min_date - $this->secs_in_a_day;
        }

        while ($grant_start_date <= $grant_end_date) {
            //$x_axis[] = date('M Y', $grant_start_date);

            $date_line[] = intval($grant_start_date); //to be used by subsequent data processing methods
            $grant_start_date += $this->secs_in_a_day;
        }

        //if we have budget records, lets pad the budget values
        if ($budget_record->budget_start_date) {
            $budget_values = array_pad($budget_values, sizeof($date_line), null); //pad the budget values just incase the disbursement date is before the ealiest budget entyr
        }

        $sql = "SELECT UNIX_TIMESTAMP(CONVERT_TZ(start_date, '+00:00', 'SYSTEM')) date_from, 
            UNIX_TIMESTAMP(CONVERT_TZ(end_date, '+00:00', 'SYSTEM')) date_to,
            start_date, end_date, final_amount
FROM budget
WHERE start_date < :start_dt AND gf_grant_id IN (" . implode(',', $grant_ids) . " )
    GROUP BY start_date, end_date, gf_grant_id, final_amount
 ORDER BY start_date ASC";

        $budget_result = db_query($sql, array(':start_dt' => format_date($grant_end_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0))));
        $cumulative_budget = 0;

        foreach($budget_result as $data) {
            $extra_days = 0;

//first get the x axis values
            $number_of_days = ($data->date_to - $data->date_from) / $this->secs_in_a_day;
            $number_of_days += 1; //we need to add 1 so as to take care of the fact  that a day ends at 23:59
            //before we continue just check to ensure that the last date is atleast as close as the current date that is to be processed
            //reason being, we need to make sure that the timeline is a day by day sequence with no gaps in it
            //even budget date lines have gaps
            if ($last_date && ($data->date_from - $last_date) > $this->secs_in_a_day) {
                $extra_days = ($data->date_from - $last_date) / $this->secs_in_a_day;

                while ($last_date <= $data->date_from) {
                    foreach ($date_line as $index => $time_line) {
                        if ($last_date == $time_line) {
                            $budget_values[$index] = $cumulative_budget;

                            for ($i = ($index + 1); $i < $extra_days; $i++) {
                                $budget_values[$i] = $cumulative_budget;
                            }

                            break;
                        }
                    }

                    $last_date += $this->secs_in_a_day;
                }
            }

            $daily_budget = $data->final_amount / $number_of_days;

            while ($data->date_from <= $data->date_to) {

                $cumulative_budget += $daily_budget;

                foreach ($date_line as $index => $time_line) {

                    if ($data->date_from == $time_line) {
                        $budget_values[$index] = $cumulative_budget;

                        for ($i = ($index + 1); $i < $number_of_days; $i++) {
                            $budget_values[$i] = $cumulative_budget;
                        }

                        break;
                    }
                }

                $data->date_from += $this->secs_in_a_day;
            }

            $last_date = $data->date_to;
        }

        return array($date_line, $budget_values);
    }

    public function prep_disbursement_data($grant_ids, $date_line) {

        $disbursement_values = array();
        $disbursement_amount = 0;
        $disbursement_values[] = 0;

        //come up with the array holder for the disbursement values
        foreach ($date_line as $index => $time_line) {
            if ($time_line < time()) {
                $disbursement_values[] = 0;
            }
        }

        $sql = "SELECT UNIX_TIMESTAMP(CONVERT_TZ(disbursement_date, '+00:00', 'SYSTEM')) given_on, 
            disbursement_date, disbursed_amount
FROM gf_disbursement_rating
WHERE gf_grant_id IN (" . implode(',', $grant_ids) . " )
AND disbursed_amount != 0
GROUP BY disbursement_date, gf_grant_id, disbursed_amount
ORDER BY disbursement_date ASC";

        $disbursement_result = db_query($sql);

        foreach($disbursement_result as $data) {

            foreach ($date_line as $index => $time_line) {

                if ($data->given_on == $time_line) {
                    $disbursement_amount += $data->disbursed_amount;

                    $disbursement_values[$index] = $disbursement_amount;

                    for ($i = $index; $i < sizeof($disbursement_values); $i++) {
                        $disbursement_values[$i] = $disbursement_amount;
                    }
                }
            }
        }
        if (sizeof($disbursement_values) > 0) {
            $disbursement_values[0] = 0; //to ensure that the initial step is drawn
        }

        return $disbursement_values;
    }

    public function prep_expenditure_data($grant_ids, $date_line, $grant_end_date) {

        $expenditure_values = array();
        $cumulative_expenditure = 0;

        //come up with the array holder for the expenditure values
        //first get the max date for the expenditure to optimize on coming up with the array holder
        $sql = "SELECT UNIX_TIMESTAMP(MAX(CONVERT_TZ(end_date, '+00:00', 'SYSTEM'))) expenditure_end_date,
            SUM(total_expenditure) total_expenditure
FROM expenditure
WHERE gf_grant_id IN (" . implode(',', $grant_ids) . " )";
        $expenditure_record = db_query($sql)->fetchObject();

        if ($expenditure_record->expenditure_end_date && $expenditure_record->total_expenditure > 0) {
            for ($i = 0; $i < sizeof($date_line); $i++) {

                if ($date_line[$i] == $expenditure_record->expenditure_end_date) {
                    break;
                }

                $expenditure_values[] = null;
            }
        }

        //$expenditure_values = array_pad($expenditure_values, sizeof($date_line) + $number_of_days, 0);

        $sql = "SELECT UNIX_TIMESTAMP(CONVERT_TZ(start_date, '+00:00', 'SYSTEM')) date_from, 
            UNIX_TIMESTAMP(CONVERT_TZ(end_date, '+00:00', 'SYSTEM')) date_to,
(UNIX_TIMESTAMP(CONVERT_TZ(end_date, '+00:00', 'SYSTEM')) - UNIX_TIMESTAMP(CONVERT_TZ(start_date, '+00:00', 'SYSTEM')))/(24*60*60) number_of_days,
            start_date, end_date, total_expenditure
FROM expenditure
WHERE start_date < :start_dt AND gf_grant_id IN (" . implode(',', $grant_ids) . " ) 
AND total_expenditure > 0
GROUP BY start_date, end_date, gf_grant_id, total_expenditure
ORDER BY start_date ASC";

        $expenditure_result = db_query($sql, array(':start_dt' => format_date($grant_end_date, 'custom', 'j F Y', variable_get('date_default_timezone', 0))));
        
        foreach($expenditure_result as $data) {
            $extra_days = 0;

            $number_of_days = ($data->date_to - $data->date_from) / $this->secs_in_a_day;
            $number_of_days += 1; //we need to add 1 so as to take care of the fact  that a day ends at 23:59

            if ($last_date && ($data->date_from - $last_date) > $this->secs_in_a_day) {
                $extra_days = ($data->date_from - $last_date) / $this->secs_in_a_day;

                while ($last_date <= $data->date_from) {
                    foreach ($date_line as $index => $time_line) {
                        if ($last_date == $time_line) {
                            $expenditure_values[$index] = $cumulative_expenditure;

                            for ($i = ($index + 1); $i < $extra_days; $i++) {
                                $expenditure_values[$i] = $cumulative_expenditure;
                            }

                            break;
                        }
                    }

                    $last_date += $this->secs_in_a_day;
                }
            }

            $daily_expenditure = $data->total_expenditure / $number_of_days;

            while ($data->date_from <= $data->date_to) {

                $cumulative_expenditure += $daily_expenditure;

                foreach ($date_line as $index => $time_line) {

                    if ($data->date_from == $time_line) {
                        $expenditure_values[$index] = $cumulative_expenditure;

                        for ($i = ($index + 1); $i < $number_of_days; $i++) {
                            $expenditure_values[$i] = $cumulative_expenditure;
                        }

                        break;
                    }
                }

                $data->date_from += $this->secs_in_a_day;
            }

            $last_date = $data->date_to;
        }

        return $expenditure_values;
    }

    public function retrieve_grant_dates($grant_ids) {

        $grant_dates = array();

        /* start.... */
        $sql = "SELECT UNIX_TIMESTAMP(MIN(CONVERT_TZ(grant_start_date, '+00:00', 'SYSTEM'))) grant_start_date, 
            UNIX_TIMESTAMP(MAX(CONVERT_TZ(grant_end_date, '+00:00', 'SYSTEM'))) grant_end_date
FROM gf_grant
WHERE id IN (" . implode(',', $grant_ids) . ")";

        $record = db_query($sql)->fetchObject();
        if ($record->grant_start_date) {
            $grant_dates['grant_start_date'] = $record->grant_start_date;
        }
        if ($record->grant_end_date) {
            $grant_dates['grant_end_date'] = $record->grant_end_date;
        }
        /* end.... */

        return $grant_dates;
    }

    public function prep_date_data($date_line, $grant_dates) {

        //$2_years =
        $date_data = array();

        $date_data['grant_end_date'] = $grant_dates['grant_end_date'];
        $date_data['today'] = null;
        //lets see if we need to specify today property
        $last_date = $date_line[sizeof($date_line) - 1];
        if ($last_date > time()) {
            $date_data['today'] = time();
        }

        return $date_data;
    }

    public function prep_rating_data($grant_ids, $date_line) {

        $ratings_data = array();
        $last_date = null;

        $sql = "SELECT gf_grant_id, rating, pu_start_date, pu_end_date,
            UNIX_TIMESTAMP(CONVERT_TZ(pu_start_date, '+00:00', 'SYSTEM')) unix_start_date, 
            UNIX_TIMESTAMP(CONVERT_TZ(pu_end_date, '+00:00', 'SYSTEM')) unix_end_date
FROM progress_update
WHERE pu_start_date IS NOT NULL AND pu_end_date IS NOT NULL AND gf_grant_id IN (" . implode(',', $grant_ids) . ") 
AND rating is not null AND rating != '' AND rating != 'x' AND rating != 'NR'
ORDER BY pu_start_date ASC";

        $rating_result = db_query($sql);

        foreach($rating_result as $data) {

            //before we continue just check to ensure that the last date is atleast as close as the current date that is to be processed
            //reason being, we need to mark dates not covered as N/R periods
            if ($last_date && ($data->unix_start_date - $last_date) > $this->secs_in_a_day) {
                $rating = array();
                $rating[] = '';
                $rating[] = $ratings_data[sizeof($ratings_data) - 1][2] + 1;

                $number_of_days = ($data->unix_start_date - $last_date) / $this->secs_in_a_day;
                $rating[] = $ratings_data[sizeof($ratings_data) - 1][2] + round($number_of_days - 1, 0);

                $ratings_data[] = $rating;
            }

            $rating = array();
            $rating[] = $data->rating;

            for ($i = 0; $i < sizeof($date_line); $i++) {

                if ($date_line[$i] == $data->unix_start_date) {
                    $rating[] = $i;
                    break;
                }
            }

            for ($j = 0; $j < sizeof($date_line); $j++) {

                if ($date_line[$j] == $data->unix_end_date) {
                    $rating[] = $j;
                    $last_date = $data->unix_end_date;
                    break;
                }
            }

            $ratings_data[] = $rating;
        }

        //lets see if the grant end date for this grant is later or earlier than today
        $x_last_date = $date_line[sizeof($date_line) - 1];
        if ($x_last_date > time()) {
            $x_last_date = time();
        }
        
        //pad the ratings upto today, if necessary
        if ($last_date < $x_last_date && sizeof($ratings_data) > 0) {
            $rating = array();
            $rating[] = '';
            $rating[] = $ratings_data[sizeof($ratings_data) - 1][2] + 1;

            $number_of_days = ($x_last_date - $last_date) / $this->secs_in_a_day;
            $rating[] = $ratings_data[sizeof($ratings_data) - 1][2] + round($number_of_days, 0);

            $ratings_data[] = $rating;
        }

        return $ratings_data;
    }

}

?>