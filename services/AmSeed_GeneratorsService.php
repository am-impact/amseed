<?php
namespace Craft;

/**
 * AmSeed - Generators service
 */
class AmSeed_GeneratorsService extends BaseApplicationComponent
{
    /**
     * Get generator by its ID.
     *
     * @param int $id
     *
     * @return AmSeed_GeneratorModel|null
     */
    public function getGeneratorById($id)
    {
        $generatorRecord = AmSeed_GeneratorRecord::model()->findById($id);
        if ($generatorRecord) {
            return AmSeed_GeneratorModel::populateModel($generatorRecord);
        }
        return null;
    }

    /**
     * Get all generators.
     *
     * @param string $indexBy      [Optional] Return the generators indexed by an attribute.
     * @param bool   $indexAllData [Optional] Whether to return all the data or just the navigation name.
     *
     * @return array
     */
    public function getAllGenerators($indexBy = null, $indexAllData = false)
    {
        $generatorRecords = AmSeed_GeneratorRecord::model()->ordered()->findAll();
        $generators = AmSeed_GeneratorModel::populateModels($generatorRecords);
        if ($indexBy !== null) {
            $indexedGenerators = array();
            foreach ($generators as $generator) {
                $indexedGenerators[$generator->$indexBy] = $indexAllData ? $generator : $generator->name;
            }
            return $indexedGenerators;
        }
        return $generators;
    }

    /**
     * Save a generator.
     *
     * @param AmSeed_GeneratorModel $generator
     *
     * @throws Exception
     * @return bool
     */
    public function saveGenerator(AmSeed_GeneratorModel $generator)
    {
        $isNewGenerator = ! $generator->id;

        // Get the generator record
        if ($generator->id) {
            $generatorRecord = AmSeed_GeneratorRecord::model()->findById($generator->id);

            if (! $generatorRecord) {
                throw new Exception(Craft::t('No generator exists with the ID “{id}”.', array('id' => $generator->id)));
            }
        }
        else {
            $generatorRecord = new AmSeed_GeneratorRecord();
        }

        // Generator attributes
        $generatorRecord->setAttributes($generator->getAttributes(), false);

        // Validate the attributes
        $generatorRecord->validate();
        $generator->addErrors($generatorRecord->getErrors());

        if (! $generator->hasErrors()) {
            // Save the generator!
            $result = $generatorRecord->save(false); // Skip validation now

            if ($result && $isNewGenerator) {
                // Start task
                $params = array(
                    'generatorId' => $generatorRecord->id,
                    'batchSize'   => craft()->amSeed_settings->getSettingsValueByHandleAndType('generateDummiesPerSet', AmSeedModel::SettingGeneral, 100)
                );
                craft()->tasks->createTask('AmSeed_GenerateDummies', Craft::t('Generate dummies'), $params);
            }

            return $result;
        }

        return false;
    }

    /**
     * Delete an generator.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteGeneratorById($id)
    {
        return craft()->db->createCommand()->delete('amseed_generators', array('id' => $id));
    }
}
