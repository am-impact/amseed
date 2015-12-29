<?php
namespace Craft;

/**
 * AmSeed - Elements service
 */
class AmSeed_ElementsService extends BaseApplicationComponent
{
    private $_allElementTypes = null;
    private $_ignoreElementTypes = array(
        ElementType::Asset,
        ElementType::GlobalSet,
        ElementType::MatrixBlock,
        ElementType::Tag
    );

    public function __construct()
    {
        // Get available element types
        $this->_allElementTypes = craft()->elements->getAllElementTypes();
    }

    /**
     * Get available element types.
     *
     * @return array
     */
    public function getElementTypes()
    {
        $elementTypes = array();

        foreach ($this->_allElementTypes as $type => $elementType) {
            // Ignore some
            if (in_array($type, $this->_ignoreElementTypes)) {
                continue;
            }

            $elementTypes[$type] = $elementType->name;
        }

        return $elementTypes;
    }

    /**
     * Get available sources per element type.
     *
     * @return array
     */
    public function getElementTypeSources()
    {
        $sources = array();

        foreach ($this->_allElementTypes as $type => $elementType) {
            // Ignore some
            if (in_array($type, $this->_ignoreElementTypes)) {
                continue;
            }

            $sources[$type] = array();

            foreach ($elementType->getSources() as $key => $source) {
                $skip = false;

                if (! isset($source['heading'])) {
                    switch ($type) {
                        case ElementType::Entry:
                            if ($key == '*' || $key == 'singles') {
                                $skip = true;
                            }
                            break;

                    }

                    if (! $skip) {
                        $sources[$type][] = array(
                            'label' => $source['label'],
                            'value' => $key,
                        );
                    }
                }
            }
        }

        return $sources;
    }

    /**
     * Get locales per element type.
     *
     * @return array
     */
    public function getElementTypeLocales()
    {
        $locales = array();

        $siteLocales = array();
        $tempSiteLocales = craft()->i18n->getSiteLocales();
        if ($tempSiteLocales) {
            foreach ($tempSiteLocales as $tempSiteLocale) {
                $siteLocales[$tempSiteLocale->id] = $tempSiteLocale->getNativeName();
            }
        }

        if (count($siteLocales)) {
            foreach ($this->_allElementTypes as $type => $elementType) {
                // Ignore some
                if (in_array($type, $this->_ignoreElementTypes)) {
                    continue;
                }

                if ($elementType->isLocalized()) {
                    $locales[$type] = $siteLocales;
                }
            }
        }

        return $locales;
    }

    /**
     * Get attributes per element type.
     *
     * @return array
     */
    public function getElementTypeAttributes()
    {
        $elementTypeAttributes = array();
        $deleteAttributes = array(
            'id',
            'enabled',
            'archived',
            'locale',
            'localeEnabled',
            'slug',
            'uri',
            'dateCreated',
            'dateUpdated',
            'root',
            'lft',
            'rgt',
            'level',
            'searchScore',
            'sectionId',
            'typeId',
            'authorId',
            'postDate',
            'expiryDate',
            'revisionNotes',
        );

        foreach ($this->_allElementTypes as $type => $elementType) {
            // Ignore some
            if (in_array($type, $this->_ignoreElementTypes)) {
                continue;
            }

            $elementTypeAttributes[$type] = array();

            $elementModel = $elementType->populateElementModel(array());
            $attributes = $elementModel->getAttributes();
            if (count($attributes)) {
                foreach ($attributes as $attribute => $value) {
                    if (in_array($attribute, $deleteAttributes)) {
                        continue;
                    }

                    $elementTypeAttributes[$type][] = $attribute;
                }
            }
        }

        return $elementTypeAttributes;
    }

    /**
     * Get attribute value options.
     *
     * @return array
     */
    public function getAttributeValueOptions()
    {
        return array(
            array(
                'label' => Craft::t('Random asset'),
                'value' => AmSeedModel::AttributeRandomAsset
            ),
            array(
                'label' => Craft::t('Random category'),
                'value' => AmSeedModel::AttributeRandomCategory
            ),
            array(
                'label' => Craft::t('Random entry'),
                'value' => AmSeedModel::AttributeRandomEntry
            ),
            array(
                'label' => Craft::t('Random user'),
                'value' => AmSeedModel::AttributeRandomUser
            ),
            array(
                'label' => Craft::t('Random email address'),
                'value' => AmSeedModel::AttributeRandomEmail
            ),
            array(
                'label' => Craft::t('Random text - {count} {type}', array(
                    'count' => 1,
                    'type' => Craft::t('paragraph')
                )),
                'value' => AmSeedModel::AttributeRandomTextParagraph
            ),
            array(
                'label' => Craft::t('Random text - {count} {type}', array(
                    'count' => 1,
                    'type' => Craft::t('sentence')
                )),
                'value' => AmSeedModel::AttributeRandomTextSentence
            ),
            array(
                'label' => Craft::t('Random text - {count} {type}', array(
                    'count' => 1,
                    'type'  => Craft::t('word')
                )),
                'value' => AmSeedModel::AttributeRandomTextWord
            ),
            array(
                'label' => Craft::t('Random text - {count} {type}', array(
                    'count' => 3,
                    'type'  => Craft::t('words')
                )),
                'value' => AmSeedModel::AttributeRandomTextWords
            ),
            array(
                'label' => Craft::t('Fixed value'),
                'value' => AmSeedModel::AttributeFixedValue
            )
        );
    }
}
