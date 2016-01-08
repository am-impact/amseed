<?php
namespace Craft;

/**
 * AmSeed Default Configuration.
 */
return array(
    'general' => array(
        array(
            'name' => 'Plugin name',
            'value' => '',
        ),
        array(
            'name' => 'Generate dummies per set',
            'value' => 100,
        ),
        array(
            'name' => 'Email address for logs',
            'value' => '',
        ),
    ),
    'elementTypes' => array(
        array(
            'name' => ElementType::Category . ' Service',
            'value' => 'categories',
        ),
        array(
            'name' => ElementType::Category . ' Method',
            'value' => 'saveCategory',
        ),
        array(
            'name' => ElementType::Entry . ' Service',
            'value' => 'entries',
        ),
        array(
            'name' => ElementType::Entry . ' Method',
            'value' => 'saveEntry',
        ),
        array(
            'name' => ElementType::User . ' Service',
            'value' => 'users',
        ),
        array(
            'name' => ElementType::User . ' Method',
            'value' => 'saveUser',
        ),
    )
);
