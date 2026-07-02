<?php

/**
 * Validation language Keys
 *
 * Copy this file to: lang/{locale}/validation.php
 *
 * Each key below corresponds to a built-in validation rule.
 * Placeholders (prefixed with :) are replaced at runtime.
 * Override only the keys you need — missing keys fall back to
 * the English default hardcoded in each rule.
 */

return [
    'required'      => 'This field is required',
    'email'         => 'Must be a valid email address',
    'url'           => 'Must be a valid URL',
    'slug'          => 'Must be a valid slug',
    'alpha'         => 'Must contain only letters',
    'alpha_num'     => 'Must contain only letters and numbers',
    'numeric'       => 'Must be a number',
    'string'        => 'Must be a string',
    'int'           => 'Must be an integer',
    'float'         => 'Must be a decimal number',
    'bool'          => 'Must be true or false',
    'min'           => 'Must not be less than :min',
    'max'           => 'Must not be greater than :max',
    'between'       => 'Must be between :min and :max',
    'length'        => 'Must be exactly :length characters',
    'date'          => 'Must be a valid date',
    'date_format'   => 'Must match the format :format',
    'after'         => 'Must be a date after :date',
    'after_format'  => 'Must be after :date (:format)',
    'before'        => 'Must be a date before :date',
    'before_format' => 'Must be before :date (:format)',
    'ip'            => 'Must be a valid IP address',
    'ip_v4'         => 'Must be a valid IPv4 address',
    'ip_v6'         => 'Must be a valid IPv6 address',
    'regex'         => 'Format is invalid',
    'in'            => 'Must be one of: :values',
    'not_in'        => 'Must not be one of: :values',
    'same'          => 'Must match :field',
    'different'     => 'Must be different from :field',
    'array'         => 'Must be an array',
    'array_min'     => 'Must have at least :min items',
    'array_max'     => 'Must have at most :max items',
    'array_between' => 'Must have between :min and :max items',
    'unique'        => 'Duplicate values are not allowed',
    'has_number'    => 'Must include at least one number',
    'has_symbol'    => 'Must include at least one symbol',
    'has_lowercase' => 'Must include at least one lowercase letter',
    'has_uppercase' => 'Must include at least one uppercase letter',
    'required_if'      => 'Required when :field is :value',
    'required_unless'  => 'Required unless :field is :value',
    'required_with'    => 'Required when :fields is present',
    'required_without' => 'Required when :fields is absent',
    'file_size'      => 'File size must not exceed :size',
    'file_type'      => 'Allowed file types: :types',
    'file_extension' => 'Allowed extensions: :extensions',
    'exists'         => 'Selected value does not exist',
    'image'            => 'Must be a valid image',
    'image_dimensions' => 'Image dimensions are invalid',
    'image_min_width'  => 'Image width must be at least :min_width px',
    'image_max_width'  => 'Image width must not exceed :max_width px',
    'image_min_height' => 'Image height must be at least :min_height px',
    'image_max_height' => 'Image height must not exceed :max_height px',
    'multiple_file'         => 'Invalid number of files',
    'multiple_file_min'     => 'Upload at least :min files',
    'multiple_file_max'     => 'Upload no more than :max files',
    'multiple_file_between' => 'Upload between :min and :max files',
    'db_unique'           => ':column is already taken',
    'db_unique_composite' => 'The combination of :fields already exists',
];
