<?php

declare(strict_types=1);

use Lightpack\Utils\Inflector;
use PHPUnit\Framework\TestCase;

final class InflectorTest extends TestCase
{
    public function testSingularize()
    {
        $this->assertEquals('quiz', (new Inflector)->singularize('quizzes'));
        $this->assertEquals('matrix', (new Inflector)->singularize('matrices'));
        $this->assertEquals('vertex', (new Inflector)->singularize('vertices'));
        $this->assertEquals('ox', (new Inflector)->singularize('oxen'));
        $this->assertEquals('alias', (new Inflector)->singularize('aliases'));
        $this->assertEquals('status', (new Inflector)->singularize('statuses'));
        $this->assertEquals('octopus', (new Inflector)->singularize('octopuses'));
        $this->assertEquals('crisis', (new Inflector)->singularize('crises'));
        $this->assertEquals('shoe', (new Inflector)->singularize('shoes'));
        $this->assertEquals('bus', (new Inflector)->singularize('buses'));
        $this->assertEquals('mouse', (new Inflector)->singularize('mice'));
        $this->assertEquals('chase', (new Inflector)->singularize('chases'));
        $this->assertEquals('phase', (new Inflector)->singularize('phases'));
        $this->assertEquals('sheep', (new Inflector)->singularize('sheep'));
        $this->assertEquals('movie', (new Inflector)->singularize('movies'));
        $this->assertEquals('series', (new Inflector)->singularize('series'));
        $this->assertEquals('child', (new Inflector)->singularize('children'));
        $this->assertEquals('tooth', (new Inflector)->singularize('teeth'));
        $this->assertEquals('foot', (new Inflector)->singularize('feet'));
        $this->assertEquals('zoo', (new Inflector)->singularize('zoos'));
        $this->assertEquals('database', (new Inflector)->singularize('database'));
        $this->assertEquals('fox', (new Inflector)->singularize('foxes'));
        $this->assertEquals('library', (new Inflector)->singularize('libraries'));
        $this->assertEquals('diagnosis', (new Inflector)->singularize('diagnoses'));
        $this->assertEquals('baby', (new Inflector)->singularize('babies'));
        $this->assertEquals('tomato', (new Inflector)->singularize('tomatoes'));
        $this->assertEquals('potato', (new Inflector)->singularize('potatoes'));
        $this->assertEquals('cactus', (new Inflector)->singularize('cactuses'));
    }

    public function testPluralize()
    {
        $this->assertEquals('quizzes', (new Inflector)->pluralize('quiz'));
        $this->assertEquals('matrices', (new Inflector)->pluralize('matrix'));
        $this->assertEquals('vertices', (new Inflector)->pluralize('vertex'));
        $this->assertEquals('oxen', (new Inflector)->pluralize('ox'));
        $this->assertEquals('aliases', (new Inflector)->pluralize('alias'));
        $this->assertEquals('statuses', (new Inflector)->pluralize('status'));
        $this->assertEquals('octopuses', (new Inflector)->pluralize('octopus'));
        $this->assertEquals('crises', (new Inflector)->pluralize('crisis'));
        $this->assertEquals('shoes', (new Inflector)->pluralize('shoe'));
        $this->assertEquals('buses', (new Inflector)->pluralize('bus'));
        $this->assertEquals('mice', (new Inflector)->pluralize('mouse'));
        $this->assertEquals('chases', (new Inflector)->pluralize('chase'));
        $this->assertEquals('phases', (new Inflector)->pluralize('phase'));
        $this->assertEquals('sheep', (new Inflector)->pluralize('sheep'));
        $this->assertEquals('movies', (new Inflector)->pluralize('movie'));
        $this->assertEquals('series', (new Inflector)->pluralize('series'));
        $this->assertEquals('children', (new Inflector)->pluralize('child'));
        $this->assertEquals('teeth', (new Inflector)->pluralize('tooth'));
        $this->assertEquals('feet', (new Inflector)->pluralize('foot'));
        $this->assertEquals('zoos', (new Inflector)->pluralize('zoo'));
        $this->assertEquals('databases', (new Inflector)->pluralize('database'));
        $this->assertEquals('foxes', (new Inflector)->pluralize('fox'));
        $this->assertEquals('libraries', (new Inflector)->pluralize('library'));
        $this->assertEquals('diagnoses', (new Inflector)->pluralize('diagnose'));
        $this->assertEquals('diagnoses', (new Inflector)->pluralize('diagnosis'));
        $this->assertEquals('babies', (new Inflector)->pluralize('baby'));
        $this->assertEquals('tomatoes', (new Inflector)->pluralize('tomato'));
        $this->assertEquals('potatoes', (new Inflector)->pluralize('potato'));
        $this->assertEquals('cactuses', (new Inflector)->pluralize('cactus'));
        $this->assertEquals('men', (new Inflector)->pluralize('man'));
    }

    public function testPluralizeIf()
    {
        $this->assertEquals('role', (new Inflector)->pluralizeIf(1, 'role'));
        $this->assertEquals('roles', (new Inflector)->pluralizeIf(3, 'role'));
    }
}
