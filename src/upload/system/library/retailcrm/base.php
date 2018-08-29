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
        if (!isset($this->data[$field])) {
            return false;
        }

        $this->data[$field] = $value;

        return true;
    }

    /**
     * @param $fields
     */
    public function setFields($fields) {
        foreach ($fields as $field) {
            $this->setField($field['field'], $field['value']);
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
     * @param $data
     *
     * @return void
     */
    abstract function prepare($data);

    /**
     * Send to crm
     *
     * @param $retailcrm_api_client
     *
     * @return void
     */
    abstract function create($retailcrm_api_client);

    /**
     * Edit in crm
     *
     * @param $retailcrm_api_client
     *
     * @return void
     */
    abstract function edit($retailcrm_api_client);
}
