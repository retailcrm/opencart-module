<?php

namespace retailcrm;

class Utils {
    public static function filterRecursive($haystack) {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = self::filterRecursive($haystack[$key]);
            }

            if ($haystack[$key] === null
                || $haystack[$key] === ''
                || (is_array($haystack[$key]) && empty($haystack[$key]))
            ) {
                unset($haystack[$key]);
            } elseif (!is_array($value)) {
                $haystack[$key] = trim($value);
            }
        }

        return $haystack;
    }

    public static function addressEquals($address1, $address2) {
        foreach ($address1 as $field => $value) {
            if (isset($address2[$field]) && $value !== $address2[$field]) {
                return false;
            }
        }

        return true;
    }
}
