<?php

namespace Retailcrm\Custom;

interface Base
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function prepare(array $data);
}