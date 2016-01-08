<?php
namespace Craft;

/**
 * AmSeed - Generators controller
 */
class AmSeed_GeneratorsController extends BaseController
{
    /**
     * Make sure the current has access.
     */
    public function __construct()
    {
        $user = craft()->userSession->getUser();
        if (! $user->can('accessAmSeedGenerators')) {
            throw new HttpException(403, Craft::t('This action may only be performed by users with the proper permissions.'));
        }
    }

    /**
     * Show generators.
     */
    public function actionIndex()
    {
        $variables = array(
            'generators' => craft()->amSeed_generators->getAllGenerators()
        );
        $this->renderTemplate('amSeed/generators/index', $variables);
    }

    /**
     * Create or edit a generator.
     *
     * @param array $variables
     */
    public function actionEditGenerator(array $variables = array())
    {
        // Do we have a generator model?
        if (! isset($variables['generator'])) {
            // Get generator if available
            if (! empty($variables['generatorId'])) {
                $variables['generator'] = craft()->amSeed_generators->getGeneratorById($variables['generatorId']);

                if (! $variables['generator']) {
                    throw new Exception(Craft::t('No dummy generator exists with the ID “{id}”.', array('id' => $variables['generatorId'])));
                }
            }
            else {
                $variables['generator'] = new AmSeed_GeneratorModel();
            }
        }

        // Get available element types and filterable fields
        $variables['elementTypes'] = craft()->amSeed_elements->getElementTypes(true);
        $variables['elementTypeSources'] = craft()->amSeed_elements->getElementTypeSources();
        $variables['elementTypeLocales'] = craft()->amSeed_elements->getElementTypeLocales();
        $variables['elementTypeAttributes'] = craft()->amSeed_elements->getElementTypeAttributes();
        $variables['attributeValueOptions'] = craft()->amSeed_elements->getAttributeValueOptions();

        $this->renderTemplate('amseed/generators/_edit', $variables);
    }

    /**
     * Save a generator.
     */
    public function actionSaveGenerator()
    {
        $this->requirePostRequest();

        // Get generator if available
        $generatorId = craft()->request->getPost('generatorId');
        if ($generatorId) {
            $generator = craft()->amSeed_generators->getGeneratorById($generatorId);

            if (! $generator) {
                throw new Exception(Craft::t('No dummy generator exists with the ID “{id}”.', array('id' => $generatorId)));
            }
        }
        else {
            $generator = new AmSeed_GeneratorModel();
        }

        // Generator attributes
        $generator->name        = craft()->request->getPost('name');
        $generator->total       = craft()->request->getPost('total');
        $generator->elementType = craft()->request->getPost('elementType');

        // Get settings
        $settings = craft()->request->getPost('settings');
        if (isset($settings[ $generator->elementType ])) {
            $generator->settings = $settings[ $generator->elementType ];
        }

        // Save generator
        if (craft()->amSeed_generators->saveGenerator($generator)) {
            craft()->userSession->setNotice(Craft::t('Dummy generator saved.'));

            $this->redirectToPostedUrl($generator);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save dummy generator.'));

            // Send the generator back to the template
            craft()->urlManager->setRouteVariables(array(
                'generator' => $generator
            ));
        }
    }

    /**
     * Delete a generator.
     */
    public function actionDeleteGenerator()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        $result = craft()->amSeed_generators->deleteGeneratorById($id);
        $this->returnJson(array('success' => $result));
    }

    /**
     * Restart a generator.
     */
    public function actionRestartGenerator()
    {
        // Find generator ID
        $generatorId = craft()->request->getParam('id');
        if (! $generatorId) {
            $this->redirect('amseed/generators');
        }

        // Get the generator
        $generator = craft()->amSeed_generators->getGeneratorById($generatorId);
        if (! $generator) {
            throw new Exception(Craft::t('No dummy generator exists with the ID “{id}”.', array('id' => $generatorId)));
        }

        // Restart and save generator
        $generator->finished = false;
        if (craft()->amSeed_generators->saveGenerator($generator)) {
            // Start task
            $params = array(
                'generatorId' => $generator->id,
                'batchSize'   => craft()->amSeed_settings->getSettingsValueByHandleAndType('generateDummiesPerSet', AmSeedModel::SettingGeneral, 100)
            );
            craft()->tasks->createTask('AmSeed_GenerateDummies', Craft::t('Generate dummies'), $params);

            // Notify user
            craft()->userSession->setNotice(Craft::t('Generating dummies.'));
        }

        // Redirect
        $this->redirect('amseed/generators');
    }
}
