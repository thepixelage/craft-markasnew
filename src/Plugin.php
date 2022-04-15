<?php

namespace thepixelage\markasnew;

/**
 * Class Plugin
 *
 * @package thepixelage\markasnew
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public function init()
    {
        parent::init();

        self::$plugin = $this;
    }
}
