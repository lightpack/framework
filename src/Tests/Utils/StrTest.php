<?php

declare(strict_types=1);

use Lightpack\Utils\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testSingularize()
    {
        $this->assertEquals('quiz', (new Str)->singularize('quizzes'));
        $this->assertEquals('matrix', (new Str)->singularize('matrices'));
        $this->assertEquals('vertex', (new Str)->singularize('vertices'));
        $this->assertEquals('ox', (new Str)->singularize('oxen'));
        $this->assertEquals('alias', (new Str)->singularize('aliases'));
        $this->assertEquals('status', (new Str)->singularize('statuses'));
        $this->assertEquals('octopus', (new Str)->singularize('octopuses'));
        $this->assertEquals('crisis', (new Str)->singularize('crises'));
        $this->assertEquals('shoe', (new Str)->singularize('shoes'));
        $this->assertEquals('bus', (new Str)->singularize('buses'));
        $this->assertEquals('mouse', (new Str)->singularize('mice'));
        $this->assertEquals('chase', (new Str)->singularize('chases'));
        $this->assertEquals('phase', (new Str)->singularize('phases'));
        $this->assertEquals('sheep', (new Str)->singularize('sheep'));
        $this->assertEquals('movie', (new Str)->singularize('movies'));
        $this->assertEquals('series', (new Str)->singularize('series'));
        $this->assertEquals('child', (new Str)->singularize('children'));
        $this->assertEquals('tooth', (new Str)->singularize('teeth'));
        $this->assertEquals('foot', (new Str)->singularize('feet'));
        $this->assertEquals('zoo', (new Str)->singularize('zoos'));
        $this->assertEquals('database', (new Str)->singularize('database'));
        $this->assertEquals('fox', (new Str)->singularize('foxes'));
        $this->assertEquals('library', (new Str)->singularize('libraries'));
        $this->assertEquals('diagnosis', (new Str)->singularize('diagnoses'));
        $this->assertEquals('baby', (new Str)->singularize('babies'));
        $this->assertEquals('tomato', (new Str)->singularize('tomatoes'));
        $this->assertEquals('potato', (new Str)->singularize('potatoes'));
        $this->assertEquals('cactus', (new Str)->singularize('cactuses'));
    }

    public function testPluralize()
    {
        $this->assertEquals('quizzes', (new Str)->pluralize('quiz'));
        $this->assertEquals('matrices', (new Str)->pluralize('matrix'));
        $this->assertEquals('vertices', (new Str)->pluralize('vertex'));
        $this->assertEquals('oxen', (new Str)->pluralize('ox'));
        $this->assertEquals('aliases', (new Str)->pluralize('alias'));
        $this->assertEquals('statuses', (new Str)->pluralize('status'));
        $this->assertEquals('octopuses', (new Str)->pluralize('octopus'));
        $this->assertEquals('crises', (new Str)->pluralize('crisis'));
        $this->assertEquals('shoes', (new Str)->pluralize('shoe'));
        $this->assertEquals('buses', (new Str)->pluralize('bus'));
        $this->assertEquals('mice', (new Str)->pluralize('mouse'));
        $this->assertEquals('chases', (new Str)->pluralize('chase'));
        $this->assertEquals('phases', (new Str)->pluralize('phase'));
        $this->assertEquals('sheep', (new Str)->pluralize('sheep'));
        $this->assertEquals('movies', (new Str)->pluralize('movie'));
        $this->assertEquals('series', (new Str)->pluralize('series'));
        $this->assertEquals('children', (new Str)->pluralize('child'));
        $this->assertEquals('teeth', (new Str)->pluralize('tooth'));
        $this->assertEquals('feet', (new Str)->pluralize('foot'));
        $this->assertEquals('zoos', (new Str)->pluralize('zoo'));
        $this->assertEquals('databases', (new Str)->pluralize('database'));
        $this->assertEquals('foxes', (new Str)->pluralize('fox'));
        $this->assertEquals('libraries', (new Str)->pluralize('library'));
        $this->assertEquals('diagnoses', (new Str)->pluralize('diagnose'));
        $this->assertEquals('diagnoses', (new Str)->pluralize('diagnosis'));
        $this->assertEquals('babies', (new Str)->pluralize('baby'));
        $this->assertEquals('tomatoes', (new Str)->pluralize('tomato'));
        $this->assertEquals('potatoes', (new Str)->pluralize('potato'));
        $this->assertEquals('cactuses', (new Str)->pluralize('cactus'));
        $this->assertEquals('men', (new Str)->pluralize('man'));
    }

    public function testPluralizeIf()
    {
        $this->assertEquals('role', (new Str)->pluralizeIf(1, 'role'));
        $this->assertEquals('roles', (new Str)->pluralizeIf(3, 'role'));
    }

    public function testCamelize()
    {
        $this->assertEquals('ParentClass', (new Str)->camelize('parent_class'));
        $this->assertEquals('ParentClass', (new Str)->camelize('parent class'));
        $this->assertEquals('LazyBrownFox', (new Str)->camelize('lazy Brown fox'));
    }

    public function testVariable()
    {
        $this->assertEquals('parentClass', (new Str)->variable('ParentClass'));
        $this->assertEquals('lazyBrownFox', (new Str)->variable('Lazy Brown Fox'));
    }

    public function testUnderscore()
    {
        $this->assertEquals('parent_class', (new Str)->underscore('ParentClass'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent Class'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent-Class'));
        $this->assertEquals('parent_class', (new Str)->underscore('Parent-Class'));
    }

    public function testDasherize()
    {
        $this->assertEquals('parent-class', (new Str)->dasherize('parent_class'));
        $this->assertEquals('parent-class', (new Str)->dasherize('parent class'));
        $this->assertEquals('parent-class', (new Str)->dasherize('Parent Class'));
        $this->assertEquals('lazy-brown-fox', (new Str)->dasherize('lazy Brown fox'));
    }

    public function testHumanize()
    {
        $this->assertEquals('Parent Class', (new Str)->humanize('parent_class'));
        $this->assertEquals('Parent Class', (new Str)->humanize('parent class'));
        $this->assertEquals('Lazy Brown Fox', (new Str)->humanize('lazy_brown_fox'));
    }

    public function testTableize()
    {
        $this->assertEquals('users', (new Str)->tableize('User'));
        $this->assertEquals('users', (new Str)->tableize('User'));
        $this->assertEquals('users', (new Str)->tableize('user'));
        $this->assertEquals('users', (new Str)->tableize('users'));
        $this->assertEquals('user_groups', (new Str)->tableize('UserGroup'));
        $this->assertEquals('user_groups', (new Str)->tableize('User Group'));
        $this->assertEquals('user_groups', (new Str)->tableize('user_group'));
        $this->assertEquals('user_groups', (new Str)->tableize('user_groups'));
    }

    public function testClassify()
    {
        $this->assertEquals('User', (new Str)->classify('user'));
        $this->assertEquals('User', (new Str)->classify('users'));
        $this->assertEquals('User', (new Str)->classify('user'));
        $this->assertEquals('UserGroup', (new Str)->classify('user_group'));
        $this->assertEquals('UserGroup', (new Str)->classify('user_groups'));
    }

    public function testForeignKey()
    {
        $this->assertEquals('user_id', (new Str)->foreignKey('user'));
        $this->assertEquals('user_id', (new Str)->foreignKey('users'));
        $this->assertEquals('user_id', (new Str)->foreignKey('user'));
        $this->assertEquals('user_group_id', (new Str)->foreignKey('user_group'));
        $this->assertEquals('user_group_id', (new Str)->foreignKey('user_groups'));
    }

    public function testOrdinalize()
    {
        $this->assertEquals('1st', (new Str)->ordinalize(1));
        $this->assertEquals('2nd', (new Str)->ordinalize(2));
        $this->assertEquals('3rd', (new Str)->ordinalize(3));
        $this->assertEquals('4th', (new Str)->ordinalize(4));
        $this->assertEquals('5th', (new Str)->ordinalize(5));
        $this->assertEquals('11th', (new Str)->ordinalize(11));
        $this->assertEquals('12th', (new Str)->ordinalize(12));
        $this->assertEquals('13th', (new Str)->ordinalize(13));
        $this->assertEquals('21st', (new Str)->ordinalize(21));
        $this->assertEquals('22nd', (new Str)->ordinalize(22));
        $this->assertEquals('23rd', (new Str)->ordinalize(23));
        $this->assertEquals('24th', (new Str)->ordinalize(24));
    }

    public function testSlugify()
    {
        $this->assertEquals('simple-blog', (new Str)->slugify('simple blog'));
        $this->assertEquals('this-is-blog-id-123', (new Str)->slugify('This is blog_id 123'));
        $this->assertEquals('what-is-seo', (new Str)->slugify('What is SEO?'));
    }

    public function testStartsWith()
    {
        $this->assertTrue((new Str)->startsWith('Hello World', 'Hello'));
        $this->assertTrue((new Str)->startsWith('/admin/products/123', '/admin'));
    }

    public function testEndsWith()
    {
        $this->assertTrue((new Str)->endsWith('Hello World', 'World'));
        $this->assertTrue((new Str)->endsWith('/admin/products/123', '123'));
    }

    public function testContains()
    {
        $this->assertTrue((new Str)->contains('Hello World', 'World'));
        $this->assertTrue((new Str)->contains('/admin/products/123', '/products/'));
    }

    public function testRandom()
    {
        $this->assertEquals(16, strlen((new Str)->random(16)));
        $this->assertEquals(0, strlen((new Str)->random(1)));
    }

    public function testMask()
    {
        $this->assertEquals('******', (new Str)->mask('secret'));
        $this->assertEquals('******', (new Str)->mask('secret', '*'));
        $this->assertEquals('se****', (new Str)->mask('secret', '*', 2));
    }
}
