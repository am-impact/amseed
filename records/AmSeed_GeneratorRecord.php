<?php
namespace Craft;

class AmSeed_GeneratorRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'amseed_generators';
    }

    protected function defineAttributes()
    {
        return array(
            'name'        => array(AttributeType::String, 'required' => true),
            'total'       => array(AttributeType::Number, 'required' => true),
            'finished'    => array(AttributeType::Bool, 'default' => false),
            'elementType' => array(AttributeType::String, 'required' => true),
            'settings'    => array(AttributeType::Mixed),
        );
    }

    /**
     * Define validation rules
     *
     * @return array
     */
    public function rules()
    {
        return array(
            array(
                'name',
                'required'
            )
        );
    }

    /**
     * @return array
     */
    public function scopes()
    {
        return array(
            'ordered' => array(
                'order' => 'name'
            )
        );
    }
}
