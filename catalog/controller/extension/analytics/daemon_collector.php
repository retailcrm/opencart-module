<?php
class ControllerExtensionAnalyticsDaemonCollector extends Controller {
    public function index() {
        $siteCode = $this->config->get('retailcrm_collector_site_key');

        if ($this->customer->isLogged()) $customerId = $this->customer->getID();
        
        $customer = isset($customerId) ? "'customerId': '" . $customerId . "'" : "";

        $js = "<script type='text/javascript'>
            (function(_,r,e,t,a,i,l){_['retailCRMObject']=a;_[a]=_[a]||function(){(_[a].q=_[a].q||[]).push(arguments)};_[a].l=1*new Date();l=r.getElementsByTagName(e)[0];i=r.createElement(e);i.async=!0;i.src=t;l.parentNode.insertBefore(i,l)})(window,document,'script','https://collector.retailcrm.pro/w.js','_rc');

            _rc('create', '" . $siteCode . "', {
                " . $customer . "
            });

            _rc('send', 'pageView');
        </script>";

        return html_entity_decode($js, ENT_QUOTES, 'UTF-8');
    }
}
