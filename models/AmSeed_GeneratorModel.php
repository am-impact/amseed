<?php
namespace Craft;

class AmSeed_GeneratorModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'id'          => AttributeType::Number,
            'name'        => AttributeType::String,
            'total'       => array(AttributeType::Number, 'default' => 100),
            'finished'    => AttributeType::Bool,
            'elementType' => AttributeType::String,
            'settings'    => AttributeType::Mixed,
        );
    }
}
