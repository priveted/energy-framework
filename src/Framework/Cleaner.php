<?php

namespace Energy;

class Cleaner
{

    
    /**
     * Clean up unnecessary or outdated data
     */

    public static function clean(): array
    {
        $data = array(
            'count' => 0,
            'list' => array()
        );

        Hooks::apply('Cleaner::clean', $data);

        return $data;
    }
}
