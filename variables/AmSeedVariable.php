<?php
namespace Craft;

class AmSeedVariable
{
    /**
     * Get the Plugin's name.
     *
     * @example {{ craft.amSeed.name }}
     * @return string
     */
    public function getName()
    {
        $plugin = craft()->plugins->getPlugin('amseed');
        return $plugin->getName();
    }

    /**
     * Get a setting value by their handle and type.
     *
     * @param string $handle
     * @param string $type
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getSettingsValueByHandleAndType($handle, $type, $defaultValue = null)
    {
        return craft()->amSeed_settings->getSettingsValueByHandleAndType($handle, $type, $defaultValue);
    }
}
