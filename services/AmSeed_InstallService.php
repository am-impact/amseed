<?php
namespace Craft;

/**
 * AmSeed - Install service
 */
class AmSeed_InstallService extends BaseApplicationComponent
{
    /**
     * Install essential information.
     */
    public function install()
    {
        $this->_installGeneral();
        $this->_installElementTypes();
    }

    /**
     * Create a set of settings.
     *
     * @param array  $settings
     * @param string $settingType
     */
    public function installSettings(array $settings, $settingType)
    {
        // Make sure we have proper settings
        if (! is_array($settings)) {
            return false;
        }

        // Add settings
        foreach ($settings as $setting) {
            // Only install if we have proper keys
            if (! isset($setting['name']) || ! isset($setting['value'])) {
                continue;
            }

            // Add new setting!
            $settingRecord = new AmSeed_SettingRecord();
            $settingRecord->type = $settingType;
            $settingRecord->name = $setting['name'];
            $settingRecord->handle = $this->_camelCase($setting['name']);
            $settingRecord->value = $setting['value'];
            $settingRecord->save();
        }
        return true;
    }

    /**
     * Remove a set of settings.
     *
     * @param array  $settings
     * @param string $settingType
     * @return bool
     */
    public function removeSettings(array $settings, $settingType)
    {
        // Make sure we have proper settings
        if (! is_array($settings)) {
            return false;
        }

        // Remove settings
        foreach ($settings as $settingName) {
            $setting = craft()->amSeed_settings->getSettingsByHandleAndType($this->_camelCase($settingName), $settingType);
            if ($setting) {
                craft()->amSeed_settings->deleteSettingById($setting->id);
            }
        }
        return true;
    }

    /**
     * Install General settings.
     */
    private function _installGeneral()
    {
        $settings = craft()->config->get('general', 'amseed');
        $this->installSettings($settings, AmSeedModel::SettingGeneral);
    }

    /**
     * Install Element Types settings.
     */
    private function _installElementTypes()
    {
        $settings = craft()->config->get('elementTypes', 'amseed');
        $this->installSettings($settings, AmSeedModel::SettingElementTypes);
    }

    /**
     * Camel case a string.
     *
     * @param string $str
     *
     * @return string
     */
    private function _camelCase($str)
    {
        // Non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9]+/i', ' ', $str);

        // Camel case!
        return str_replace(' ', '', lcfirst(ucwords(strtolower(trim($str)))));
    }
}
