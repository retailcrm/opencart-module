<?php

namespace Retailcrm;

abstract class Base
{
    protected $registry;
    protected $data = array();

    /**
     * Base constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name) {
        return $this->registry->get($name);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return bool
     */
    public function setField($field, $value) {
        if (!array_key_exists($field, $this->data)) {
            return false;
        }

        $this->data[$field] = $value;

        return true;
    }

    /**
     * @param $fields
     */
    public function setFields($fields) {
        foreach ($fields as $field => $value) {
            $this->setField($field, $value);
        }
    }

    /**
     * @param $data
     * @param $element
     */
    public function setDataArray($data, $element) {
        if (is_array($data)) {
            $this->setField($element, $data);
        }
    }

    /**
     * @param array $custom_fields
     * @param array $setting
     * @param string $prefix
     *
     * @return array
     */
    protected function prepareCustomFields($custom_fields, $setting, $prefix) {
        $result = array();

        if (!$custom_fields || empty($custom_fields)) {
            return $result;
        }

        foreach ($custom_fields as $loc => $custom_field) {
            if (\is_int($loc)) {
                $result = $this->getCustomFields($custom_field, $setting, $prefix);
            } elseif (\is_string($loc)) {
                foreach ($custom_field as $field) {
                    if (!$field) {
                        continue;
                    }

                    $result = $this->getCustomFields($field, $setting, $prefix);
                }
            }
        }

        return $result;
    }

    /**
     * @param array $custom_fields
     * @param array $setting
     * @param string $prefix
     *
     * @return array $result
     */
    private function getCustomFields($custom_fields, $setting, $prefix) {
        $result = array();

        foreach ($custom_fields as $key => $field) {
            if (isset($setting[\Retailcrm\Retailcrm::MODULE. '_custom_field'][$prefix . $key])) {
                $result[$setting[\Retailcrm\Retailcrm::MODULE. '_custom_field'][$prefix . $key]] = $field;
            }
        }

        return $result;
    }

    /**
     * Prepare data array
     *
     * @param array $data
     *
     * @return void
     */
    public function prepare($data)
    {
        unset($data);
        Retailcrm::filterRecursive($this->data);
    }

    /**
     * Send to crm
     *
     * @param $retailcrm_api_client
     *
     * @return mixed
     */
    abstract public function create($retailcrm_api_client);

    /**
     * Edit in crm
     *
     * @param $retailcrm_api_client
     *
     * @return mixed
     */
    abstract public function edit($retailcrm_api_client);

    /**
     * Upload to CRM
     *
     * @param $retailcrm_api_client
     * @param array $data
     * @param string $method
     *
     * @return boolean
     */
    public function upload($retailcrm_api_client, $data = array(), $method = 'orders')
    {
        if (!$data) {
            return false;
        }

        $upload = array();
        $countOrders = count($data);
        $countIterations = (int) ($countOrders / 50);

        foreach ($data as $key => $entity) {
            $this->prepare($entity);
            $upload[] = $this->data;
            $this->resetData();

            if ($countIterations > 0) {
                unset($data[$key]);
            }

            if (($countIterations == 0 && count($data) == count($upload))
                || count($upload) == 50
            ) {
                /** @var \RetailcrmApiClient5 $retailcrm_api_client */
                $retailcrm_api_client->{$method . 'Upload'}($upload);
                $upload = array();
                $countIterations--;
            }
        }

        return true;
    }

    /**
     * Reset data on default
     */
    private function resetData()
    {
        $numericValues = array(
            'externalId',
            'discountManualAmount'
        );

        foreach ($this->data as $field => $value) {
            if (in_array($field, $numericValues)) {
                $this->data[$field] = 0;
            } elseif (is_array($value)) {
                $this->data[$field] = array();
            } else {
                $this->data[$field] = null;
            }
        }
    }
}
