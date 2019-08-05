<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DonorUtilService {

    public function donor_score_listing($target_year_id = 11) {
        $rows = array();

        //get all the donor countries
        $donor_result = db_query("SELECT id, name, flag_path FROM donor WHERE donor_type = 'country' ORDER BY name");

        foreach ($donor_result as $donor) {
            $holder = $this->calculate_donor_score($donor->id, $target_year_id, true);

            if ($holder && ('H' == $holder[0]['data'] || 'UM' == $holder[0]['data'])) {
                $row = array();

                $row[] = array('data' => '<img width="40" src=' . base_path() . path_to_theme() . '/images/flags/' . $donor->flag_path . '>');
                $row[] = array('data' => l($donor->name, 'donor/' . md5($donor->id) . '/d'));

                $ec = 'High';
                if ('UM' == $holder[0]['data']) {
                    $ec = 'Upper Middle';
                }
                $holder[0]['data'] = $ec;

                $row = array_merge($row, $holder);

                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function calculate_donor_score($donor_id, $target_year_id, $add_padding = true) {
        $holder = array();

        $contribution_result = db_query("SELECT paid_in FROM contribution WHERE donor_id = :donor_id AND target_year_id = :yr_id", array(':donor_id' => $donor_id, ':yr_id' => $target_year_id));
        $contribution_amount = $contribution_result->fetchField();

        $economic_info = db_query("SELECT gni_in_usd, economic_classification FROM donor_economic_meta_data WHERE donor_id = :donor_id AND target_year_id = :yr_id AND economic_classification IN ('H', 'UM')", array(':donor_id' => $donor_id, ':yr_id' => $target_year_id))->fetchObject();

        if ($economic_info) {
            $holder[] = array('data' => $economic_info->economic_classification, 'class' => 'apw-center-text');
//calculate the percentage of contribution to GNI
            if ($contribution_amount && $economic_info->gni_in_usd) {

                if (true == $add_padding) {
                    $holder[] = array('data' => number_format($contribution_amount), 'class' => 'apw-numeric');
                }

                $holder[] = array('data' => number_format($economic_info->gni_in_usd), 'class' => 'apw-numeric');

                $holder[] = array('data' => number_format(($contribution_amount / $economic_info->gni_in_usd) * 100, 4), 'class' => 'apw-numeric');

                $holder[] = array('data' => $this->decode_score(($contribution_amount / $economic_info->gni_in_usd) * 100), 'class' => 'apw-center-text');
            }
        }

        return $holder;
    }

    private function decode_score($score) {
//Global Fund donor score "A": % is greater than 0.010%
// Global Fund donor score "B": % is from 0.007% to 0.010%
// Global Fund donor score "C": % is from 0.004% to 0.007%
//Global Fund donor score "D": % is from 0.001% to 0.004%
//Global Fund donor score "E": % is below 0.001%
//Global Fund donor score "F": % is zero
        if ($score >= 0.010) {
            return 'A';
        } else if ($score >= 0.007 && $score < 0.010) {
            return 'B';
        } else if ($score >= 0.004 && $score < 0.007) {
            return 'C';
        } else if ($score >= 0.001 && $score < 0.004) {
            return 'D';
        } else if ($score < 0.001) {
            return 'E';
        }
    }

    public function donor_pledge_contribution($donor_id, $category = 'normal') {

        $rows = array();

        $pledges = db_query("SELECT ty.year, SUM(p.amount_in_usd) amount_in_usd, p.category, d.donor_type, ty.id target_year_id
FROM pledge p
INNER JOIN donor d ON p.donor_id = d.id
INNER JOIN target_year ty ON p.target_year_id = ty.id
WHERE d.id = " . $donor_id . "  AND p.category IN ('" . $category . "')
    GROUP BY ty.year
ORDER BY ty.sort_order ASC");

        foreach ($pledges as $pledge) {

            //get the contribution for this donor for this year
            $contribution_result = db_query("SELECT SUM(paid_in)
            FROM contribution c
            INNER JOIN donor d ON c.donor_id = d.id
            INNER JOIN target_year ty ON c.target_year_id = ty.id
            WHERE d.id = " . $donor_id . " 
                AND ty.year = '" . $pledge->year . "'
                AND c.category IN ('" . $category . "')
                GROUP BY ty.year");
            $contribution = $contribution_result->fetchField();

            if ($pledge->amount_in_usd > 0 || $contribution > 0) {
                $row = array();

                $row[] = $pledge->year;
                $row[] = $pledge->amount_in_usd;
                $row[] = array('data' => 'n/a', 'class' => 'apw-numeric'); //N/B is the default change percent change
                if (sizeof($rows) > 0) {
                    $previous_pledge = $rows[sizeof($rows) - 1][1];

                    $percent_change = (($pledge->amount_in_usd - $previous_pledge) / $previous_pledge);
                    $row[2] = array('data' => number_format($percent_change * 100, 1) . '%', 'class' => 'apw-numeric');
                }
                $row[] = $contribution;
                //calculate the % of contribution to the pledge
                $row[] = array('data' => number_format(($contribution / $pledge->amount_in_usd) * 100, 1) . ' %', 'class' => 'apw-numeric');

                //if it is a country and showing the normal category, also get its GNI
                if ('normal' == $category && 'country' == $pledge->donor_type) {
                    $holder = $this->calculate_donor_score($donor_id, $pledge->target_year_id, false);

                    if ($holder && ('H' == $holder[0]['data'] || 'UM' == $holder[0]['data'])) {

                        $ec = 'High';
                        if ('UM' == $holder[0]['data']) {
                            $ec = 'Upper Middle';
                        }
                        $holder[0]['data'] = $ec;
                    }

                    $row = array_merge($row, $holder);
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function pledge_contribution_chart_data($donor_id) {
        $rows = array();

        //get all the years
        $years_result = db_query("SELECT ty.id, ty.year FROM target_year ty ORDER BY ty.sort_order ASC");

        foreach ($years_result as $year) {
            $row = array();

            $row[] = $year->year;

            $pledge_result = db_query("SELECT SUM(p.amount_in_usd) amount FROM pledge p WHERE p.donor_id = :donor_id AND p.target_year_id = :yr_id", array(':donor_id' => $donor_id, ':yr_id' => $year->id));
            $row[] = intval($pledge_result->fetchField());

            $contribution_result = db_query("SELECT SUM(c.paid_in) amount FROM contribution c WHERE c.donor_id = :donor_id AND c.target_year_id = :yr_id", array(':donor_id' => $donor_id, ':yr_id' => $year->id));
            $row[] = intval($contribution_result->fetchField());

            if (13 == $year->id) {
                if (!empty($row[1])) {
                    $rows[] = $row;
                }
            } else {
                $rows[] = $row;
            }
        }

        return $rows;
    }

}

?>