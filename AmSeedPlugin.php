<?php
/**
 * Seeding for Craft.
 *
 * @package   Am Seed
 * @author    Hubert Prein
 */
namespace Craft;

class AmSeedPlugin extends BasePlugin
{
    /**
     * @return null|string
     */
    public function getName()
    {
        if (craft()->plugins->getPlugin('amseed')) {
            $pluginName = craft()->amSeed_settings->getSettingsByHandleAndType('pluginName', AmSeedModel::SettingGeneral);
            if ($pluginName && $pluginName->value) {
                return $pluginName->value;
            }
        }
        return Craft::t('a&m seed');
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * @return string
     */
    public function getDeveloper()
    {
        return 'a&m impact';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    /**
     * @return string
     */
    public function getSettingsUrl()
    {
        return 'amseed/settings';
    }

    /**
     * Plugin has control panel section.
     *
     * @return boolean
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * Plugin has Control Panel routes.
     *
     * @return array
     */
    public function registerCpRoutes()
    {
        return array(
            'amseed/generators' => array(
                'action' => 'amSeed/generators/index'
            ),
            'amseed/generators/new' => array(
                'action' => 'amSeed/generators/editGenerator'
            ),
            'amseed/generators/edit/(?P<generatorId>\d+)' => array(
                'action' => 'amSeed/generators/editGenerator'
            ),

            'amseed/settings' => array(
                'action' => 'amSeed/settings/index'
            ),
            'amseed/settings/elementtypes' => array(
                'action' => 'amSeed/settings/elementTypes'
            ),
        );
    }

    /**
     * Plugin has user permissions.
     *
     * @return array
     */
    public function registerUserPermissions()
    {
        return array(
            'accessAmSeedGenerators' => array(
                'label' => Craft::t('Access to dummy generators')
            ),
            'accessAmSeedSettings' => array(
                'label' => Craft::t('Access to settings')
            )
        );
    }

    /**
     * Install essential information after installing the plugin.
     */
    public function onAfterInstall()
    {
        craft()->amSeed_install->install();
    }
}
