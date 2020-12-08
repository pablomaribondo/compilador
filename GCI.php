<?php

namespace compiler;

class GCI
{
    public static $label = 0;
    public static $temp  = 0;

    public static function getNewLabel()
    {
        return 'label'.self::$label++;
    }

    public static function getRollbackLabel($value)
    {
        return 'label'.(self::$label-$value);
    }

    public static function getNewTemp()
    {
        return 'temp'.self::$temp++;
    }

    public static function getRollbackTemp($value)
    {
        return 'temp'.(self::$temp-$value);
    }
}
