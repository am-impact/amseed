<?php
namespace Craft;

/**
 * AmSeed - Dummy service
 */
class AmSeed_DummiesService extends BaseApplicationComponent
{
    private $_generator;
    private $_elementType;

    private $_service;
    private $_saveMethod;

    private $_counter = 0;
    private $_locales = array();
    private $_fields = array();
    private $_assets = array();
    private $_categories = array();
    private $_entries = array();
    private $_randomElements = array();
    private $_randomTexts = array();

    private $_log = array(
        'success' => 0,
        'errors'  => array()
    );

    /**
     * Send log.
     *
     * @return bool
     */
    public function sendLog()
    {
        // Get email address
        $recipients = craft()->amSeed_settings->getSettingsByHandleAndType('emailAddressForLogs', AmSeedModel::SettingGeneral);
        if ($recipients && $recipients->value) {
            $recipients = $recipients->value;
        }
        else {
            return false;
        }

        // Check for multiple recipients
        $recipients = ArrayHelper::stringToArray($recipients);
        $recipients = array_unique($recipients);
        if (! count($recipients)) {
            return false;
        }

        // Craft email settings
        $settings = craft()->email->getSettings();
        $systemEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
        $systemName =  !empty($settings['senderName']) ? $settings['senderName'] : '';

        // Set email settings
        $success = false;
        $email = new EmailModel();
        $email->htmlBody = '<pre>' . print_r($this->_log, true) . '</pre>';
        $email->fromEmail = $systemEmail;
        $email->fromName = $systemName;
        $email->subject = Craft::t('Dummy generation log');

        // Send emails
        foreach ($recipients as $recipient) {
            $email->toEmail = $recipient;

            if (filter_var($email->toEmail, FILTER_VALIDATE_EMAIL)) {
                // Add variable for email event
                if (craft()->email->sendEmail($email)) {
                    $success = true;
                }
            }
        }

        return $success;
    }

    /**
     * Create dummies.
     *
     * @param AmSeed_GeneratorModel $generator
     * @param int                   $totalDummies [Optional] Amount of dummies to generate.
     *
     * @return bool
     */
    public function createDummies(AmSeed_GeneratorModel $generator, $totalDummies = 100)
    {
        // Remember the generator
        $this->_generator = $generator;

        // Get the service and its save method
        $this->_service = craft()->amSeed_settings->getSettingsValueByHandleAndType(StringHelper::toCamelCase($generator->elementType . ' Service'), AmSeedModel::SettingElementTypes, false);
        $this->_saveMethod = craft()->amSeed_settings->getSettingsValueByHandleAndType(StringHelper::toCamelCase($generator->elementType . ' Method'), AmSeedModel::SettingElementTypes, false);
        if (! $this->_service || ! $this->_saveMethod) {
            return false;
        }

        // Get locales to add
        $this->_getLocales();

        // Get element criteria
        $criteria = craft()->elements->getCriteria($generator->elementType);

        // Get element type
        $this->_elementType = $criteria->getElementType();

        // Set element source
        if ($this->_getGeneratorSetting('source')) {
            $source = $this->_elementType->getSource($this->_getGeneratorSetting('source'));

            // Does the source specify any criteria attributes?
            if ($source && ! empty($source['criteria'])) {
                $criteria->setAttributes($source['criteria']);
            }
        }

        // Set locale
        $criteria->locale = $this->_locales[0];

        // Get element model
        $elementModel = $this->_elementType->populateElementModel((array) $criteria->getAttributes());
        if (! $elementModel) {
            return false;
        }

        // Get field layout
        $this->_getFieldLayoutFields($elementModel->getFieldLayout());

        // Generate dummies!
        $this->_randomTexts = array(); // Reset for new texts
        craft()->config->maxPowerCaptain();
        craft()->config->set('cacheElementQueries', false);
        for ($i = 0; $i < (int) $totalDummies; $i++) {
            // Update counter
            $this->_counter = ($i + 1);

            // Create new model
            $elementModel = $this->_elementType->populateElementModel((array) $criteria->getAttributes());

            // Create dummy!
            if ($this->_createDummy($elementModel)) {
                $this->_log['success'] ++;
            }
            else {
                $this->_log['errors'] = $elementModel->getErrors();
            }
        }

        return true;
    }

    /**
     * Get a generator setting value.
     *
     * @param string $name
     *
     * @return mixed
     */
    private function _getGeneratorSetting($name)
    {
        if (isset($this->_generator['settings']) && isset($this->_generator['settings'][$name])) {
            return $this->_generator['settings'][$name];
        }

        return null;
    }

    /**
     * Get locales.
     */
    private function _getLocales()
    {
        // Get locales from Craft's settings
        $siteLocales = array();
        $tempSiteLocales = craft()->i18n->getSiteLocales();
        if ($tempSiteLocales) {
            foreach ($tempSiteLocales as $tempSiteLocale) {
                $siteLocales[] = $tempSiteLocale->id;
            }
        }

        // Do we need to add multiple locales?
        if ($this->_getGeneratorSetting('locale')) {
            // Generator locales
            $locales = $this->_getGeneratorSetting('locale');

            if ($locales == '*') {
                $this->_locales = $siteLocales;
            }
            else {
                $this->_locales = $locales;
            }
        }
        else {
            $this->_locales = array($siteLocales[0]);
        }
    }

    /**
     * Get fields to fill.
     *
     * @param object $fieldLayouts
     */
    private function _getFieldLayoutFields($fieldLayout)
    {
        foreach ($fieldLayout->getTabs() as $tab) {
            // Tab fields
            $fields = $tab->getFields();
            foreach ($fields as $layoutField) {
                if ($layoutField->required) {
                    // Get actual field
                    $field = $layoutField->getField();

                    $this->_fields[] = $field;
                }
            }
        }
    }

    /**
     * Create a dummy.
     *
     * @param object $element
     *
     * @return bool
     */
    private function _createDummy($element)
    {
        // Set title?
        if ($this->_elementType->hasTitles()) {
            $element->getContent()->title = $this->_getTitleForElement($element);
        }

        // Set content
        $this->_getContentForElement($element);

        // Set attributes
        $this->_getAttributesForElement($element);

        // Generate random user?
        switch ($this->_generator->elementType) {
            case ElementType::User:
                $randomUser = $this->_getRandomUser();

                if ($randomUser) {
                    $element->firstName = ucfirst($randomUser['name']['first']);
                    $element->lastName = ucfirst($randomUser['name']['last']);
                    $element->username = $randomUser['username'];
                    $element->email = $randomUser['email'];
                }
                break;
        }

        // Save element!
        return craft()->{$this->_service}->{$this->_saveMethod}($element);
    }

    /**
     * Get title for element.
     *
     * @param object $element
     *
     * @return string
     */
    private function _getTitleForElement($element)
    {
        return $this->_getRandomText(4, 'words') . ' ' . $this->_counter;
    }

    /**
     * Get content for element.
     *
     * @param object $element
     */
    private function _getContentForElement($element)
    {
        foreach ($this->_fields as $field) {
            switch ($field->type) {
                case 'Assets':
                case 'Categories':
                case 'Entries':
                case 'Users':
                    if (! isset($field->settings['sources'])) {
                        continue;
                    }

                    switch ($field->type) {
                        case 'Assets':
                            $elementType = ElementType::Asset;
                            break;
                        case 'Categories':
                            $elementType = ElementType::Category;
                            break;
                        case 'Entries':
                            $elementType = ElementType::Entry;
                            break;
                        case 'Users':
                            $elementType = ElementType::User;
                            break;
                    }

                    $sourceKey = is_array($field->settings['sources']) ? implode('-', $field->settings['sources']) : $field->settings['sources'];
                    $element->getContent()->setAttribute($field->handle, array($this->_getRandomElement($elementType, $sourceKey)));
                    break;

                case 'Checkboxes':
                case 'Dropdown':
                case 'RadioButtons':
                    if (! isset($field->settings['options']) || ! count($field->settings['options'])) {
                        continue;
                    }

                    $randomOption = $field->settings['options'][ mt_rand(0, (count($field->settings['options']) - 1)) ]['value'];
                    if ($field->type == 'Checkboxes') {
                        $randomOption = array($randomOption);
                    }

                    $element->getContent()->setAttribute($field->handle, $randomOption);
                    break;

                case 'Date':
                    $element->getContent()->setAttribute($field->handle, new DateTime(null, new \DateTimeZone(craft()->getTimeZone())));
                    break;

                case 'Number':
                    $min = (isset($field->settings['min']) ? $field->settings['min'] : 0);
                    $max = (isset($field->settings['max']) ? $field->settings['max'] : 1000);
                    $element->getContent()->setAttribute($field->handle, mt_rand($min, $max));
                    break;

                case 'PlainText':
                    if (isset($field->settings['multiline']) && $field->settings['multiline']) {
                        $element->getContent()->setAttribute($field->handle, $this->_getRandomText());
                    }
                    else {
                        $element->getContent()->setAttribute($field->handle, $this->_getRandomText(1, 'sentences'));
                    }
                    break;

                default:
                    $element->getContent()->setAttribute($field->handle, $this->_getRandomText());
                    break;
            }
        }
    }

    /**
     * Get attributes for element.
     *
     * @param object $element
     */
    private function _getAttributesForElement($element)
    {
        if ($this->_getGeneratorSetting('attributes')) {
            foreach ($this->_getGeneratorSetting('attributes') as $attribute => $attributeSettings) {
                if ($attributeSettings['enabled']) {
                    switch ($attributeSettings['value']) {
                        case AmSeedModel::AttributeRandomAsset:
                            $element->setAttribute($attribute, $this->_getRandomElement(ElementType::Asset));
                            break;

                        case AmSeedModel::AttributeRandomCategory:
                            $element->setAttribute($attribute, $this->_getRandomElement(ElementType::Category));
                            break;

                        case AmSeedModel::AttributeRandomEntry:
                            $element->setAttribute($attribute, $this->_getRandomElement(ElementType::Entry));
                            break;

                        case AmSeedModel::AttributeRandomUser:
                            $element->setAttribute($attribute, $this->_getRandomElement(ElementType::User));
                            break;

                        case AmSeedModel::AttributeRandomEmail:
                            $element->setAttribute($attribute, $this->_getRandomEmail());
                            break;

                        case AmSeedModel::AttributeRandomTextParagraph:
                            $element->setAttribute($attribute, $this->_getRandomText());
                            break;

                        case AmSeedModel::AttributeRandomTextSentence:
                            $element->setAttribute($attribute, $this->_getRandomText(1, 'sentences'));
                            break;

                        case AmSeedModel::AttributeRandomTextWord:
                            $element->setAttribute($attribute, $this->_getRandomText(1, 'words'));
                            break;

                        case AmSeedModel::AttributeRandomTextWords:
                            $element->setAttribute($attribute, $this->_getRandomText(3, 'words'));
                            break;

                        case AmSeedModel::AttributeFixedValue:
                            $element->setAttribute($attribute, $attributeSettings['fixedValue']);
                            break;

                        default:
                            $element->setAttribute($attribute, $this->_getRandomText());
                            break;
                    }
                }
            }
        }
    }

    /**
     * Get random element based on an Element Type.
     *
     * @param string $sourceKey Remember the for which source we are getting it for.
     *
     * @return mixed
     */
    private function _getRandomElement($elementType, $sourceKey = '*')
    {
        if (! isset($this->_randomElements[$elementType][$sourceKey])) {
            $criteria = craft()->elements->getCriteria($elementType);
            $criteria->status = null;
            $this->_randomElements[$elementType][$sourceKey] = $criteria->ids();
        }
        if (count($this->_randomElements[$elementType][$sourceKey])) {
            return $this->_randomElements[$elementType][$sourceKey][ mt_rand(0, (count($this->_randomElements[$elementType][$sourceKey]) - 1)) ];
        }

        return null;
    }

    /**
     * Get random email address.
     *
     * @return string
     */
    private function _getRandomEmail()
    {
        $email = '';

        // Possible top-level domains
        $tlds = array('.com', '.net', '.gov', '.org', '.edu', '.biz', '.info');

        // Possible characters
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';

        // Generate random username
        for ($i = 1; $i <= mt_rand(7, 17); $i++) {
            $email .= substr($chars, mt_rand(0, strlen($chars)), 1);
        }

        $email .= '@example';

        return $email . $tlds[ mt_rand(0, (count($tlds) - 1)) ];
    }

    /**
     * Get random user.
     *
     * @return array
     */
    private function _getRandomUser()
    {
        // API URL
        $url = 'https://randomuser.me/api/';

        // cURL request
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        $randomUser = curl_exec($curlHandle);
        curl_close($curlHandle);

        if ($randomUser !== false) {
            $randomUser = json_decode($randomUser, true);

            if (isset($randomUser['results'][0]['user'])) {
                return $randomUser['results'][0]['user'];
            }
        }

        return false;
    }

    /**
     * Get random dummy text.
     *
     * @return string
     */
    private function _getRandomText($length = 1, $type = 'paras')
    {
        if (! isset($this->_randomTexts[$type][$length])) {
            // This API doesn't support words
            $getWords = false;
            if ($type == 'words') {
                $type = 'paras';
                $getWords = true;
            }

            // API URL
            $url = 'https://baconipsum.com/api/?type=meat-and-filler&' . $type . '=' . $length . '&format=json';

            // cURL request
            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            $loremText = json_decode(curl_exec($curlHandle), true);
            $loremText = implode(' ', $loremText);
            curl_close($curlHandle);

            // Get words instead?
            if ($getWords) {
                // Get random words
                $loremWords = array();
                $words = explode(' ', str_replace(array('. ', '.', ','), '', $loremText));
                for ($i = 0; $i < $length; $i++) {
                    $loremWords[] = strtolower($words[ mt_rand(0, (count($words) - 1))]);
                }
                $loremText = ucfirst(implode(' ', $loremWords));

                // Change type back to words for saving generated text
                $type = 'words';
            }

            // Remember generated text
            $this->_randomTexts[$type][$length] = $loremText;
        }

        return $this->_randomTexts[$type][$length];
    }
}
