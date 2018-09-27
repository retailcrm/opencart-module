<?php

class ControllerCronIcml extends Controller {
    public function index($cron_id, $code, $cycle, $date_added, $date_modified) {
        $this->load->controller('extension/module/retailcrm/icml');
    }
}
