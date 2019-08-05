<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ProfileManagement
 *
 * @author kinyua
 */
class GfoSubscription {

    public function membership_titles() {
        $sql = "SELECT id, name FROM {membership}";

        $result = db_query($sql);

        $titles = array();
        while ($data = db_fetch_object($result)) {
            $titles [$data->id] = t($data->name);
        }

        return $titles;
    }

    public function get_user_membership() {
        global $user;

        $sql = "SELECT membership_id FROM {user_membership} WHERE user_id = %d";

        $result = db_query($sql, $user->uid);

        $membership_ids = array();
        while ($data = db_fetch_object($result)) {
            $membership_ids[] = t($data->membership_id);
        }

        return $membership_ids;
    }

    public function profession_titles() {
        $sql = "SELECT id, name FROM {profession}";

        $result = db_query($sql);

        $titles = array();
        while ($data = db_fetch_object($result)) {
            $titles [$data->id] = t($data->name);
        }

        return $titles;
    }

    public function get_user_profession() {
        global $user;

        $sql = "SELECT profession_id FROM {user_profession} WHERE user_id = %d";

        $result = db_query($sql, $user->uid);

        $profession_ids = array();
        while ($data = db_fetch_object($result)) {
            $profession_ids[] = t($data->profession_id);
        }

        return $profession_ids;
    }

    public function gf_work_titles() {
        $sql = "SELECT id, name FROM {gf_work}";

        $result = db_query($sql);

        $titles = array();
        while ($data = db_fetch_object($result)) {
            $titles [$data->id] = t($data->name);
        }

        return $titles;
    }

    public function get_user_gf_work() {
        global $user;

        $sql = "SELECT gf_work_id FROM {user_gf_work} WHERE user_id = %d";

        $result = db_query($sql, $user->uid);

        $gf_work_ids = array();
        while ($data = db_fetch_object($result)) {
            $gf_work_ids[] = t($data->gf_work_id);
        }

        return $gf_work_ids;
    }
}

?>
