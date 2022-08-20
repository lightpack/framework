<?php

declare(strict_types=1);

use Lightpack\Utils\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testSingularize()
    {
        $this->assertEquals('quiz', Str::singularize('quizzes'));
        $this->assertEquals('matrix', Str::singularize('matrices'));
        $this->assertEquals('vertex', Str::singularize('vertices'));
        $this->assertEquals('ox', Str::singularize('oxen'));
        $this->assertEquals('alias', Str::singularize('aliases'));
        $this->assertEquals('status', Str::singularize('statuses'));
        $this->assertEquals('octopus', Str::singularize('octopuses'));
        $this->assertEquals('crisis', Str::singularize('crises'));
        $this->assertEquals('shoe', Str::singularize('shoes'));
        $this->assertEquals('bus', Str::singularize('buses'));
        $this->assertEquals('mouse', Str::singularize('mice'));
        $this->assertEquals('chase', Str::singularize('chases'));
        $this->assertEquals('phase', Str::singularize('phases'));
        $this->assertEquals('sheep', Str::singularize('sheep'));
        $this->assertEquals('movie', Str::singularize('movies'));
        $this->assertEquals('series', Str::singularize('series'));
        $this->assertEquals('child', Str::singularize('children'));
        $this->assertEquals('tooth', Str::singularize('teeth'));
        $this->assertEquals('foot', Str::singularize('feet'));
        $this->assertEquals('zoo', Str::singularize('zoos'));
        $this->assertEquals('database', Str::singularize('database'));
        $this->assertEquals('fox', Str::singularize('foxes'));
        $this->assertEquals('library', Str::singularize('libraries'));
        $this->assertEquals('diagnosis', Str::singularize('diagnoses'));
        $this->assertEquals('baby', Str::singularize('babies'));
        $this->assertEquals('tomato', Str::singularize('tomatoes'));
        $this->assertEquals('potato', Str::singularize('potatoes'));
        $this->assertEquals('cactus', Str::singularize('cactuses'));
    }

    public function testPluralize()
    {
        $this->assertEquals('quizzes', Str::pluralize('quiz'));
        $this->assertEquals('matrices', Str::pluralize('matrix'));
        $this->assertEquals('vertices', Str::pluralize('vertex'));
        $this->assertEquals('oxen', Str::pluralize('ox'));
        $this->assertEquals('aliases', Str::pluralize('alias'));
        $this->assertEquals('statuses', Str::pluralize('status'));
        $this->assertEquals('octopuses', Str::pluralize('octopus'));
        $this->assertEquals('crises', Str::pluralize('crisis'));
        $this->assertEquals('shoes', Str::pluralize('shoe'));
        $this->assertEquals('buses', Str::pluralize('bus'));
        $this->assertEquals('mice', Str::pluralize('mouse'));
        $this->assertEquals('chases', Str::pluralize('chase'));
        $this->assertEquals('phases', Str::pluralize('phase'));
        $this->assertEquals('sheep', Str::pluralize('sheep'));
        $this->assertEquals('movies', Str::pluralize('movie'));
        $this->assertEquals('series', Str::pluralize('series'));
        $this->assertEquals('children', Str::pluralize('child'));
        $this->assertEquals('teeth', Str::pluralize('tooth'));
        $this->assertEquals('feet', Str::pluralize('foot'));
        $this->assertEquals('zoos', Str::pluralize('zoo'));
        $this->assertEquals('databases', Str::pluralize('database'));
        $this->assertEquals('foxes', Str::pluralize('fox'));
        $this->assertEquals('libraries', Str::pluralize('library'));
        $this->assertEquals('diagnoses', Str::pluralize('diagnose'));
        $this->assertEquals('diagnoses', Str::pluralize('diagnosis'));
        $this->assertEquals('babies', Str::pluralize('baby'));
        $this->assertEquals('tomatoes', Str::pluralize('tomato'));
        $this->assertEquals('potatoes', Str::pluralize('potato'));
        $this->assertEquals('cactuses', Str::pluralize('cactus'));
        $this->assertEquals('men', Str::pluralize('man'));
    }

    public function testPluralizeIf()
    {
        $this->assertEquals('role', Str::pluralizeIf(1, 'role'));
        $this->assertEquals('roles', Str::pluralizeIf(3, 'role'));
    }

    public function testCamelize()
    {
        $this->assertEquals('ParentClass', Str::camelize('parent_class'));
        $this->assertEquals('ParentClass', Str::camelize('parent class'));
        $this->assertEquals('LazyBrownFox', Str::camelize('lazy Brown fox'));
    }

    public function testVariable()
    {
        $this->assertEquals('parentClass', Str::variable('ParentClass'));
        $this->assertEquals('lazyBrownFox', Str::variable('Lazy Brown Fox'));
    }

    public function testUnderscore()
    {
        $this->assertEquals('parent_class', Str::underscore('ParentClass'));
        $this->assertEquals('parent_class', Str::underscore('Parent Class'));
        $this->assertEquals('parent_class', Str::underscore('Parent-Class'));
        $this->assertEquals('parent_class', Str::underscore('Parent-Class'));
    }

    public function testDasherize()
    {
        $this->assertEquals('parent-class', Str::dasherize('parent_class'));
        $this->assertEquals('parent-class', Str::dasherize('parent class'));
        $this->assertEquals('parent-class', Str::dasherize('Parent Class'));
        $this->assertEquals('lazy-brown-fox', Str::dasherize('lazy Brown fox'));
    }

    public function testHumanize()
    {
        $this->assertEquals('Parent Class', Str::humanize('parent_class'));
        $this->assertEquals('Parent Class', Str::humanize('parent class'));
        $this->assertEquals('Lazy Brown Fox', Str::humanize('lazy_brown_fox'));
    }

    public function testTableize()
    {
        $this->assertEquals('users', Str::tableize('User'));
        $this->assertEquals('users', Str::tableize('User'));
        $this->assertEquals('users', Str::tableize('user'));
        $this->assertEquals('users', Str::tableize('users'));
        $this->assertEquals('user_groups', Str::tableize('UserGroup'));
        $this->assertEquals('user_groups', Str::tableize('User Group'));
        $this->assertEquals('user_groups', Str::tableize('user_group'));
        $this->assertEquals('user_groups', Str::tableize('user_groups'));
    }

    public function testClassify()
    {
        $this->assertEquals('User', Str::classify('user'));
        $this->assertEquals('User', Str::classify('users'));
        $this->assertEquals('User', Str::classify('user'));
        $this->assertEquals('UserGroup', Str::classify('user_group'));
        $this->assertEquals('UserGroup', Str::classify('user_groups'));
    }

    public function testForeignKey()
    {
        $this->assertEquals('user_id', Str::foreignKey('user'));
        $this->assertEquals('user_id', Str::foreignKey('users'));
        $this->assertEquals('user_id', Str::foreignKey('user'));
        $this->assertEquals('user_group_id', Str::foreignKey('user_group'));
        $this->assertEquals('user_group_id', Str::foreignKey('user_groups'));
    }

    public function testOrdinalize()
    {
        $this->assertEquals('1st', Str::ordinalize(1));
        $this->assertEquals('2nd', Str::ordinalize(2));
        $this->assertEquals('3rd', Str::ordinalize(3));
        $this->assertEquals('4th', Str::ordinalize(4));
        $this->assertEquals('5th', Str::ordinalize(5));
        $this->assertEquals('11th', Str::ordinalize(11));
        $this->assertEquals('12th', Str::ordinalize(12));
        $this->assertEquals('13th', Str::ordinalize(13));
        $this->assertEquals('21st', Str::ordinalize(21));
        $this->assertEquals('22nd', Str::ordinalize(22));
        $this->assertEquals('23rd', Str::ordinalize(23));
        $this->assertEquals('24th', Str::ordinalize(24));
    }

    public function testSlugify()
    {
        $this->assertEquals('simple-blog', Str::slugify('simple blog'));
        $this->assertEquals('this-is-blog-id-123', Str::slugify('This is blog_id 123'));
        $this->assertEquals('what-is-seo', Str::slugify('What is SEO?'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('Hello World', 'Hello'));
        $this->assertTrue(Str::startsWith('/admin/products/123', '/admin'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('Hello World', 'World'));
        $this->assertTrue(Str::endsWith('/admin/products/123', '123'));
    }

    public function testContains()
    {
        $this->assertTrue(Str::contains('Hello World', 'World'));
        $this->assertTrue(Str::contains('/admin/products/123', '/products/'));
    }

    public function testRandom()
    {
        $this->assertEquals(16, strlen(Str::random(16)));
        $this->assertEquals(0, strlen(Str::random(1)));
    }

    public function testMask()
    {
        $this->assertEquals('******', Str::mask('secret'));
        $this->assertEquals('******', Str::mask('secret', '*'));
        $this->assertEquals('se****', Str::mask('secret', '*', 2));
    }
}
