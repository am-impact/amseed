<?php
namespace Craft;

/**
 * AmSeed - Settings controller
 */
class AmSeed_SettingsController extends BaseController
{
    /**
     * Make sure the current has access.
     */
    public function __construct()
    {
        $user = craft()->userSession->getUser();
        if (! $user->can('accessAmSeedSettings')) {
            throw new HttpException(403, Craft::t('This action may only be performed by users with the proper permissions.'));
        }
    }

    /**
     * Show General settings.
     */
    public function actionIndex()
    {
        $variables = array(
            'type'    => AmSeedModel::SettingGeneral,
            'general' => craft()->amSeed_settings->getAllSettingsByType(AmSeedModel::SettingGeneral)
        );
        $this->renderTemplate('amSeed/settings/index', $variables);
    }

    /**
     * Show Element Types settings.
     */
    public function actionElementTypes()
    {
        $variables = array(
            'type'                  => AmSeedModel::SettingElementTypes,
            'elementTypes'          => craft()->amSeed_settings->getAllSettingsByType(AmSeedModel::SettingElementTypes),
            'availableElementTypes' => craft()->amSeed_elements->getElementTypes()
        );

        // Install new settings for an element type if we don't have it yet
        foreach ($variables['availableElementTypes'] as $elementType => $elementTypeName) {
            if (! isset($variables['elementTypes'][$elementType . 'Service'])) {
                $newSettings = array(
                    array(
                        'name' => $elementType . ' Service',
                        'value' => '',
                    ),
                );
                craft()->amSeed_install->installSettings($newSettings, AmSeedModel::SettingElementTypes);
            }
            if (! isset($variables['elementTypes'][$elementType . 'Method'])) {
                $newSettings = array(
                    array(
                        'name' => $elementType . ' Method',
                        'value' => '',
                    ),
                );
                craft()->amSeed_install->installSettings($newSettings, AmSeedModel::SettingElementTypes);
            }
        }

        // Get the settings again after installing possible new settings
        $variables['elementTypes'] = craft()->amSeed_settings->getAllSettingsByType(AmSeedModel::SettingElementTypes);

        $this->renderTemplate('amSeed/settings/elementTypes', $variables);
    }

    /**
     * Saves settings.
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        // Settings type
        $settingsType = craft()->request->getPost('settingsType', false);

        // Save settings!
        if ($settingsType) {
            $this->_saveSettings($settingsType);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t find settings type.'));
        }

        $this->redirectToPostedUrl();
    }

    /**
     * Save the settings for a specific type.
     *
     * @param string $type
     */
    private function _saveSettings($type)
    {
        $success = true;

        // Get all available settings for this type
        $availableSettings = craft()->amSeed_settings->getAllSettingsByType($type);

        if ($availableSettings) {
            // Save each available setting
            foreach ($availableSettings as $setting) {
                // Find new settings
                $newSettings = craft()->request->getPost($setting->handle, false);

                if ($newSettings !== false) {
                    $setting->value = $newSettings;
                    if(! craft()->amSeed_settings->saveSettings($setting)) {
                        $success = false;
                    }
                }
            }
        }

        if ($success) {
            craft()->userSession->setNotice(Craft::t('Settings saved.'));
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save settings.'));
        }
    }
}
