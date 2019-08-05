<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GrantAnomalyDetector
 *
 * @author kinyua
 */
class GrantAnomalyDetector {

    private $no_of_problem_cases = 5;

    private function getMaxPointFromQuadrant($max_val, $problems_detected) {

        $quadrants = $max_val / $this->no_of_problem_cases;

        if (true === $problems_detected[3]) {
            $max_val += $quadrants * 4;
        } else if (true === $problems_detected[2]) {
            $max_val += $quadrants * 3;
        } else if (true === $problems_detected[1]) {
            $max_val += $quadrants * 2;
        } else if (true === $problems_detected[0]) {
            $max_val += $quadrants * 1;
        }

        return $max_val;
    }

    private function getMaxPoint($budget_data, $disbursement_data, $expenditure_data) {

        $max_val = 0;

        if (sizeof($budget_data) > 0) {
            $max_val = $budget_data[sizeof($budget_data) - 1];
            if(null === $max_val) {
                $arr_size = sizeof($budget_data) - 1;
                
                for($i = $arr_size; $i > 0; $i--) {
                    if(null !== $budget_data[$i]) {
                        $max_val = $budget_data[$i];
                        break;
                    }
                }
            }
        }

        if (sizeof($disbursement_data) > 0 && $disbursement_data[sizeof($disbursement_data) - 1] > $max_val) {
            $max_val = $disbursement_data[sizeof($disbursement_data) - 1];
        }

        if (sizeof($expenditure_data) > 0 && $expenditure_data[sizeof($expenditure_data) - 1] > $max_val) {
            $max_val = $expenditure_data[sizeof($expenditure_data) - 1];
        }

        $max_val = $max_val * 1.05;

        return $max_val;
    }

    public function expenditureBelowBudget($percentage, $budget_data, $expenditure_data, $disbursement_data) {

        $data = array();
        $problemDetected = false;

        $max_val = $this->getMaxPoint($budget_data, $disbursement_data, $expenditure_data);

        $percentage = $percentage / 100;

        $data = array_pad($data, sizeof($budget_data), null); //pad the array holder

        foreach ($budget_data as $index => $budget_value) {
            if (is_numeric($budget_value) && is_numeric($expenditure_data[$index]) && $budget_value > 0) {
                $ratio = $expenditure_data[$index] / $budget_value;

                if ($percentage > $ratio) {
                    $problemDetected = true;
                    $data[$index] = $max_val;
                }
            }
        }

        return array($problemDetected, $data);
    }

    public function disbursementDelay($check_period, $budget_data, $disbursement_data, $expenditure_data, $prev_problems_detected) {

        $data = array();
        $problemDetected = false;

        $max_val = $this->getMaxPoint($budget_data, $disbursement_data, $expenditure_data);

        $max_val = $this->getMaxPointFromQuadrant($max_val, $prev_problems_detected);

        if (sizeof($disbursement_data) > 0) {

            $data = array_pad($data, sizeof($disbursement_data), null); //pad the array holder

            $start_point = 1;
            $starting_amount = 0;

            foreach ($disbursement_data as $index => $disbursement_value) {
                $starting_amount = $disbursement_data[$start_point];

                if ($starting_amount != $disbursement_value) {
                    //looks like a disbursement change has been reached. lets check to see if it was after an year
                    $lapsed_period = $index - $start_point;

                    if ($lapsed_period > $check_period) {
                        for ($i = $start_point; $i < $index; $i++) {
                            $problemDetected = true;
                            $data[$i] = $max_val;
                        }
                    }

                    $start_point = $index;
                }
            }

            //check for the last disbursment value
            $disbursement_value = $disbursement_data[sizeof($disbursement_data) - 1];

            if ($starting_amount == $disbursement_value) {
                //looks like a disbursement change has been reached. lets check to see if it was after an year
                $lapsed_period = sizeof($disbursement_data) - $start_point;

                if ($lapsed_period > $check_period) {
                    for ($j = $start_point; $j < sizeof($disbursement_data); $j++) {
                        $problemDetected = true;
                        $data[$j] = $max_val;
                    }
                }
            }
        }

        return array($problemDetected, $data);
    }

    public function expGreaterThanDisb($budget_data, $disbursement_data, $expenditure_data, $prev_problems_detected) {

        $data = array();
        $problemDetected = false;

        $max_val = $this->getMaxPoint($budget_data, $disbursement_data, $expenditure_data);

        $max_val = $this->getMaxPointFromQuadrant($max_val, $prev_problems_detected);

        $data = array_pad($data, sizeof($expenditure_data), null); //pad the array holder

        foreach ($expenditure_data as $index => $expenditure_value) {
            if (is_numeric($expenditure_value) && is_numeric($disbursement_data[$index]) && $expenditure_value > 0 && $disbursement_data[$index] > 0) {

                if ($expenditure_value > $disbursement_data[$index]) {
                    $problemDetected = true;
                    $data[$index] = $max_val;
                }
            }
        }

        return array($problemDetected, $data);
    }

    public function expenditureDelay($check_period, $budget_data, $disbursement_data, $expenditure_data, $prev_problems_detected) {

        $data = array();
        $problemDetected = false;

        $max_val = $this->getMaxPoint($budget_data, $disbursement_data, $expenditure_data);

        $max_val = $this->getMaxPointFromQuadrant($max_val, $prev_problems_detected);
    
        if (sizeof($expenditure_data) > 0) {

            $data = array_pad($data, sizeof($expenditure_data), null); //pad the array holder

            $start_point = 1;
            $starting_amount = 0;

            foreach ($expenditure_data as $index => $expenditure_value) {
                $starting_amount = $expenditure_data[$start_point];

                if ($starting_amount != $expenditure_value && is_numeric($expenditure_value)) {
                    //print $index;die();
                    //looks like an expenditure change has been reached. lets check to see if it was after an year
                    $lapsed_period = $index - $start_point;

                    if ($lapsed_period > $check_period) {
                        for ($i = $start_point; $i < $index; $i++) {
                            $problemDetected = true;
                            $data[$i] = $max_val;
                        }
                    }

                    $start_point = $index;
                }
            }

            //check for the last expenditure value
            $expenditure_value = $expenditure_data[sizeof($expenditure_data) - 1];

            if ($starting_amount == $expenditure_value) {
                //looks like an expenditure change has been reached. lets check to see if it was after an year
                $lapsed_period = sizeof($expenditure_data) - $start_point;

                if ($lapsed_period > $check_period) {
                    for ($j = $start_point; $j < sizeof($expenditure_data); $j++) {
                        $problemDetected = true;
                        $data[$j] = $max_val;
                    }
                }
            }

            //lets see if the last date of the disbursement is greater than the expenditure and if it is more than check period
            if(sizeof($disbursement_data) > sizeof($expenditure_data)) {
                if((sizeof($disbursement_data) - sizeof($expenditure_data)) > $check_period) {
                    $problemDetected = true;
                    $data = array_pad($data, sizeof($disbursement_data), $max_val); //pad the array holder
                }
            }
        }

        return array($problemDetected, $data);
    }

    public function disbursementBelowBudget($percentage, $budget_data, $expenditure_data, $disbursement_data, $prev_problems_detected) {

        $data = array();
        $problemDetected = false;

        $max_val = $this->getMaxPoint($budget_data, $disbursement_data, $expenditure_data);

        $max_val = $this->getMaxPointFromQuadrant($max_val, $prev_problems_detected);

        $percentage = $percentage / 100;

        $data = array_pad($data, sizeof($budget_data), null); //pad the array holder

        foreach ($budget_data as $index => $budget_value) {
            if (is_numeric($budget_value) && is_numeric($disbursement_data[$index]) && $budget_value > 0) {
                $ratio = $disbursement_data[$index] / $budget_value;

                if ($percentage > $ratio) {
                    $problemDetected = true;
                    $data[$index] = $max_val;
                }
            }
        }

        return array($problemDetected, $data);
    }

}

?>