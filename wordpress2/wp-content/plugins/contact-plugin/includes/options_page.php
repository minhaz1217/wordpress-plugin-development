<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('after_setup_theme', 'load_carbon_fields');
add_action('carbon_fields_register_fields', 'create_options_page');

function load_carbon_fields()
{
    \Carbon_Fields\Carbon_Fields::boot();
}

function create_options_page()
{
    $plugin_prefix = "contact_plugin_";
    Container::make('theme_options', __('Contact Form'))
        ->set_icon("dashicons-carrot")
        ->add_fields(array(
            Field::make("checkbox", $plugin_prefix . "active", __("Active")),

            Field::make('text', $plugin_prefix . "recipients", __('Recipient Email'))
                ->set_attribute("placeholder", "eg: your@mail.com")
                ->set_help_text("The email that the form is submitted to"),

            Field::make('textarea', $plugin_prefix . "message", __('Confirmation Message'))
                ->set_attribute("placeholder", "Enter confirmation message")
                ->set_help_text("Type the message you want the submitter to receiveasdf"),
        ));
}
