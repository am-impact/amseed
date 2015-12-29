<?php
namespace Craft;

/**
 * AmSeed - Generate Dummies task
 */
class AmSeed_GenerateDummiesTask extends BaseTask
{
    private $_generator;
    private $_totalSteps;

    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'generatorId' => AttributeType::Number,
            'batchSize'   => AttributeType::Number
        );
    }

    /**
     * Returns the default description for this task.
     *
     * @return string
     */
    public function getDescription()
    {
        return Craft::t('Generate dummies');
    }

    /**
     * Gets the total number of steps for this task.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        if (! isset($this->_totalSteps)) {
            // Default
            $this->_totalSteps = 0;

            // Get generator
            $generator = craft()->amSeed_generators->getGeneratorById($this->getSettings()->generatorId);
            if ($generator) {
                $this->_generator = $generator;
                $this->_totalSteps = ceil($generator->total / $this->getSettings()->batchSize);

                // No records, so it's already finished
                if ($this->_totalSteps == 0) {
                    $this->_generator->finished = true;
                    craft()->amSeed_generators->saveGenerator($this->_generator);
                }
            }
        }

        return $this->_totalSteps;
    }

    /**
     * Runs a task step.
     *
     * Note: first step is 0!
     *
     * @param int $step
     *
     * @return bool
     */
    public function runStep($step)
    {
        // Generator settings
        if ($this->_totalSteps == 1) {
            $totalDummies = $this->_generator->total;
        }
        elseif (($step + 1) == $this->_totalSteps) {
            $totalDummies = $this->_generator->total - ($this->getSettings()->batchSize * $step);
        }
        else {
            $totalDummies = $this->getSettings()->batchSize;
        }

        // Start generating dummies
        $result = craft()->amSeed_dummies->createDummies($this->_generator, $totalDummies);

        // Is the generator finished?
        if (($step + 1) == $this->getTotalSteps()) {
            $this->_generator->finished = true;
            craft()->amSeed_generators->saveGenerator($this->_generator);
        }

        return $result;
    }
}
