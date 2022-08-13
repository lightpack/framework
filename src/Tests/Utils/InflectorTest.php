<?php

declare(strict_types=1);

use Lightpack\Utils\Inflector;
use PHPUnit\Framework\TestCase;

final class InflectorTest extends TestCase
{
    public function testPluralize()
    {
        $this->assertEquals('quizzes', Inflector::pluralize('quiz'));
        $this->assertEquals('matrices', Inflector::pluralize('matrix'));
        $this->assertEquals('vertices', Inflector::pluralize('vertex'));
        $this->assertEquals('oxen', Inflector::pluralize('ox'));
        $this->assertEquals('aliases', Inflector::pluralize('alias'));
        $this->assertEquals('statuses', Inflector::pluralize('status'));
        $this->assertEquals('octopuses', Inflector::pluralize('octopus'));
        $this->assertEquals('crises', Inflector::pluralize('crisis'));
        $this->assertEquals('shoes', Inflector::pluralize('shoe'));
        $this->assertEquals('buses', Inflector::pluralize('bus'));
        $this->assertEquals('mice', Inflector::pluralize('mouse'));
        $this->assertEquals('chases', Inflector::pluralize('chase'));
        $this->assertEquals('phases', Inflector::pluralize('phase'));
        $this->assertEquals('sheep', Inflector::pluralize('sheep'));
        $this->assertEquals('movies', Inflector::pluralize('movie'));
        $this->assertEquals('series', Inflector::pluralize('series'));
        $this->assertEquals('children', Inflector::pluralize('child'));
        $this->assertEquals('teeth', Inflector::pluralize('tooth'));
        $this->assertEquals('feet', Inflector::pluralize('foot'));
        $this->assertEquals('zoos', Inflector::pluralize('zoo'));
        $this->assertEquals('databases', Inflector::pluralize('database'));
        $this->assertEquals('foxes', Inflector::pluralize('fox'));
        $this->assertEquals('libraries', Inflector::pluralize('library'));
        $this->assertEquals('diagnoses', Inflector::pluralize('diagnose'));
        $this->assertEquals('diagnoses', Inflector::pluralize('diagnosis'));
        $this->assertEquals('babies', Inflector::pluralize('baby'));
        $this->assertEquals('tomatoes', Inflector::pluralize('tomato'));
        $this->assertEquals('potatoes', Inflector::pluralize('potato'));
        $this->assertEquals('cactuses', Inflector::pluralize('cactus'));
        $this->assertEquals('men', Inflector::pluralize('man'));
    }

    public function testSingularize()
    {
        $this->assertEquals('quiz', Inflector::singularize('quizzes'));
        $this->assertEquals('matrix', Inflector::singularize('matrices'));
        $this->assertEquals('vertex', Inflector::singularize('vertices'));
        $this->assertEquals('ox', Inflector::singularize('oxen'));
        $this->assertEquals('alias', Inflector::singularize('aliases'));
        $this->assertEquals('status', Inflector::singularize('statuses'));
        $this->assertEquals('octopus', Inflector::singularize('octopuses'));
        $this->assertEquals('crisis', Inflector::singularize('crises'));
        $this->assertEquals('shoe', Inflector::singularize('shoes'));
        $this->assertEquals('bus', Inflector::singularize('buses'));
        $this->assertEquals('mouse', Inflector::singularize('mice'));
        $this->assertEquals('chase', Inflector::singularize('chases'));
        $this->assertEquals('phase', Inflector::singularize('phases'));
        $this->assertEquals('sheep', Inflector::singularize('sheep'));
        $this->assertEquals('movie', Inflector::singularize('movies'));
        $this->assertEquals('series', Inflector::singularize('series'));
        $this->assertEquals('child', Inflector::singularize('children'));
        $this->assertEquals('tooth', Inflector::singularize('teeth'));
        $this->assertEquals('foot', Inflector::singularize('feet'));
        $this->assertEquals('zoo', Inflector::singularize('zoos'));
        $this->assertEquals('database', Inflector::singularize('database'));
        $this->assertEquals('fox', Inflector::singularize('foxes'));
        $this->assertEquals('library', Inflector::singularize('libraries'));
        $this->assertEquals('diagnosis', Inflector::singularize('diagnoses'));
        $this->assertEquals('baby', Inflector::singularize('babies'));
        $this->assertEquals('tomato', Inflector::singularize('tomatoes'));
        $this->assertEquals('potato', Inflector::singularize('potatoes'));
        $this->assertEquals('cactus', Inflector::singularize('cactuses'));
    }

    public function testCamelize()
    {
        $this->assertEquals('ParentClass', Inflector::camelize('parent_class'));
        $this->assertEquals('ParentClass', Inflector::camelize('parent class'));
        $this->assertEquals('LazyBrownFox', Inflector::camelize('lazy Brown fox'));
    }

    public function testVariable()
    {
        $this->assertEquals('parentClass', Inflector::variable('ParentClass'));
        $this->assertEquals('lazyBrownFox', Inflector::variable('Lazy Brown Fox'));
    }

    public function testUnderscore()
    {
        $this->assertEquals('parent_class', Inflector::underscore('ParentClass'));
        $this->assertEquals('parent_class', Inflector::underscore('Parent Class'));
        $this->assertEquals('parent_class', Inflector::underscore('Parent-Class'));
    }

    public function testDasherize()
    {
        $this->assertEquals('parent-class', Inflector::dasherize('parent_class'));
        $this->assertEquals('parent-class', Inflector::dasherize('parent class'));
        $this->assertEquals('lazy-brown-fox', Inflector::dasherize('lazy Brown fox'));
    }

    public function testHumanize()
    {
        $this->assertEquals('Parent Class', Inflector::humanize('parent_class'));
        $this->assertEquals('Parent Class', Inflector::humanize('parent class'));
        $this->assertEquals('Lazy Brown Fox', Inflector::humanize('lazy_brown_fox'));
    }

    public function testTableize()
    {
        $this->assertEquals('users', Inflector::tableize('User'));
        $this->assertEquals('users', Inflector::tableize('User'));
        $this->assertEquals('users', Inflector::tableize('user'));
        $this->assertEquals('users', Inflector::tableize('users'));
        $this->assertEquals('user_groups', Inflector::tableize('UserGroup'));
        $this->assertEquals('user_groups', Inflector::tableize('User Group'));
        $this->assertEquals('user_groups', Inflector::tableize('user_group'));
        $this->assertEquals('user_groups', Inflector::tableize('user_groups'));
    }

    public function testClassify()
    {
        $this->assertEquals('User', Inflector::classify('user'));
        $this->assertEquals('User', Inflector::classify('users'));
        $this->assertEquals('User', Inflector::classify('user'));
        $this->assertEquals('UserGroup', Inflector::classify('user_group'));
        $this->assertEquals('UserGroup', Inflector::classify('user_groups'));
    }

    public function testOrdinalize()
    {
        $this->assertEquals('1st', Inflector::ordinalize(1));
        $this->assertEquals('2nd', Inflector::ordinalize(2));
        $this->assertEquals('3rd', Inflector::ordinalize(3));
        $this->assertEquals('4th', Inflector::ordinalize(4));
        $this->assertEquals('5th', Inflector::ordinalize(5));
        $this->assertEquals('11th', Inflector::ordinalize(11));
        $this->assertEquals('12th', Inflector::ordinalize(12));
        $this->assertEquals('13th', Inflector::ordinalize(13));
        $this->assertEquals('21st', Inflector::ordinalize(21));
        $this->assertEquals('22nd', Inflector::ordinalize(22));
        $this->assertEquals('23rd', Inflector::ordinalize(23));
        $this->assertEquals('24th', Inflector::ordinalize(24));
    }

    public function testSlugify()
    {
        $this->assertEquals('simple-blog', Inflector::slugify('simple blog'));
        $this->assertEquals('this-is-blog-id-123', Inflector::slugify('This is blog_id 123'));
        $this->assertEquals('what-is-seo', Inflector::slugify('What is SEO?'));
    }
}