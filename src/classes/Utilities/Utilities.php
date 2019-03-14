<?php
namespace KanbanBoard\Utilities;

use KanbanBoard\Exceptions\EnvironmentException;

class Utilities
{
    public function __construct() {}

	public function env($name, $default = NULL)
    {
        $value = false;
        if (is_string($name)) {
            $value = getenv($name);
        }
        if(!$value && $default !== NULL) {
            $value = $default;
        }
        if (!$value) {
            throw new EnvironmentException(sprintf('Environment variable %s not found or has no value', $name));
        }
        return $value;
	}

	public static function hasValue($array, $key)
    {
        return \is_array($array) && isset($array[$key]);
	}

	public static function dump($data)
    {
		echo '<pre>';
		var_dump($data);
		echo '</pre>';
	}
}