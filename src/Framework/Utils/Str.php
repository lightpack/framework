<?php

namespace Lightpack\Utils;

class Str
{
    /**
     * This method will return the camel-cased version of the passed string.
     * 
     * For example: camelize('parent class') returns 'ParentClass'.
     */
    public function camelize(string $subject): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $subject)));
    }

    /**
     * This method will return the equivalent variable name version of 
     * the passed string. This is useful for converting a camel-cased 
     * string to a variable name.
     * 
     * For example: variable('ParentClass') returns 'parentClass'.
     */
    public function variable(string $subject): string
    {
        return lcfirst(static::camelize($subject));
    }

    /**
     * This method will return the lowercased underscored version of the 
     * passed string.
     * 
     * For example: underscore('ParentClass') returns 'parent_class'.
     */
    public function underscore(string $subject): string
    {
        $subject = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $subject));

        return str_replace([' ', '-'], '_', $subject);
    }

    /**
     * This method will return the lowercased dashed (hyphenated) version of 
     * the passed string.
     * 
     * For example: dasherize('Parent Class') returns 'parent-class'.
     */
    public function dasherize(string $subject): string
    {
        $subject = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $subject));

        return str_replace([' ', '_'], '-', $subject);
    }

    /**
     * This method will return the human readable version of the passed 
     * string with the first word capitalized.
     * 
     * For example: humanize('lazy brown fox') returns 'Lazy Brown Fox'.
     */
    public function humanize(string $subject): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $subject));
    }

    /**
     * This method will capitalize the the first character of each word. 
     * This is specially useful for headlines and titles.
     * 
     * For example: headline('lazy brown fox') returns 'Lazy brown fox'.
     */
    public function headline(string $subject): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $subject));
    }

    /**
     * This method will return the plural form of the passed string making 
     * it suitable for use as a table name in databases.
     * 
     * For example: tableize('User') returns 'users'.
     */
    public function tableize(string $subject): string
    {
        return (new Inflector)->pluralize($this->underscore($subject));
    }

    /**
     * This method will return the singular form of the passed string making 
     * it suitable for use as a class name in PHP.
     * 
     * For example: classify('users') returns 'User'.
     */
    public function classify(string $subject): string
    {
        $inflector = new Inflector;

        return $this->camelize($inflector->singularize($subject));
    }

    /**
     * This method will return the plural form of the passed string making 
     * it suitable for use as a foreign key in databases.
     * 
     * For example: foreignKey('User') returns 'user_id'.
     */
    public function foreignKey(string $subject): string
    {
        $inflector = new Inflector;

        return $this->underscore($inflector->singularize($subject)) . '_id';
    }

    /**
     * This method will return the ordinal form of the passed string.
     * 
     * For example: ordinalize(1) returns '1st'.
     */
    public function ordinalize(int $number): string
    {
        if (in_array(($number % 100), range(11, 13))) {
            return $number . 'th';
        }

        switch (($number % 10)) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
        }
    }

    /**
     * This method will return the slug form of the passed string useful
     * for human friendly URLs.
     * 
     * For example: slugify('Lazy Brown Fox') returns 'lazy-brown-fox'.
     */
    public function slugify(string $subject, string $separator = '-'): string
    {
        $subject = preg_replace('/[^a-zA-Z0-9]/', ' ', $subject);
        $subject = preg_replace('/\s+/', ' ', $subject);
        $subject = trim($subject);
        $subject = str_replace(' ', $separator, $subject);

        return strtolower($subject);
    }

    /**
     * This method will check if the passed subject string starts with 
     * the passed prefix.
     * 
     * For example: startsWith('/admin/products/23', '/admin') returns true.
     */
    public function startsWith(string $subject, string $prefix): bool
    {
        return substr($subject, 0, strlen($prefix)) == $prefix;
    }

    /**
     * This method will check if the passed subject string ends with 
     * the passed suffix.
     * 
     * For example: endsWith('/admin/products/23', '23') returns true.
     */
    public function endsWith(string $subject, string $suffix): bool
    {
        return substr($subject, -strlen($suffix)) == $suffix;
    }

    /**
     * This method will check if the passed subject constains the passed
     * substring anywhere in it.
     * 
     * For example: contains('/admin/products/23', 'products') returns true.
     */
    public function contains(string $subject, string $needle): bool
    {
        return strpos($subject, $needle) !== false;
    }

    /**
     * This method will generate a random string of the passed length.
     */
    public function random(int $length = 16): string
    {
        if ($length < 2) {
            return '';
        }

        return bin2hex(random_bytes($length / 2));
    }

    /**
     * This method will mask the passed subject string with the passed 
     * mask. This is specially useful for obfuscatng sensitive data such
     * as credit card numbers, emails, phones, etc.
     * 
     * For example: mask('MYSECRET', '*', 2) returns 'MY****'.
     */
    public function mask(string $subject, string $mask = '*', int $start = 0): string
    {
        $length = strlen($subject);
        $masked = substr($subject, 0, $start);

        for ($i = $start; $i < $length; $i++) {
            $masked .= $mask;
        }

        return $masked;
    }
}
