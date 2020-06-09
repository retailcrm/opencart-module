<?php

namespace retailcrm;

/**
 * Class Base
 *
 * @property \Loader load
 * @property \DB db
 * @property \Config config
 * @property \Language language
 */
abstract class Base {
    protected $registry;

    public function __construct(\Registry $registry) {
        $this->registry = $registry;
    }

    public function __get($name) {
        return $this->registry->get($name);
    }
}
