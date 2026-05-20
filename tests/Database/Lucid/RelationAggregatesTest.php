<?php

require_once 'Project.php';

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;

class RelationAggregatesTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $this->db->query($sql)->closeCursor();

        $container = Container::getInstance();
        $container->register('db', fn() => $this->db);
    }

    protected function tearDown(): void
    {
        $this->db->query("DELETE FROM tasks");
        $this->db->query("DELETE FROM projects");
        $this->db = null;
    }

    // ── withCount (GROUP BY path, no orderBy) ─────────────────────────────

    public function testWithCountLoadsHasManyCount()
    {
        $this->db->table('projects')->insert(['name' => 'Alpha']);
        $p1 = $this->db->lastInsertId();
        $this->db->table('projects')->insert(['name' => 'Beta']);
        $p2 = $this->db->lastInsertId();

        $this->db->table('tasks')->insert([
            ['name' => 'T1', 'project_id' => $p1, 'hours' => 1, 'cost' => 10],
            ['name' => 'T2', 'project_id' => $p1, 'hours' => 2, 'cost' => 20],
            ['name' => 'T3', 'project_id' => $p2, 'hours' => 3, 'cost' => 30],
        ]);

        $projects = Project::query()->withCount('tasks')->all();

        foreach ($projects as $project) {
            if ($project->name === 'Alpha') {
                $this->assertEquals(2, $project->tasks_count);
            } elseif ($project->name === 'Beta') {
                $this->assertEquals(1, $project->tasks_count);
            }
        }
    }

    public function testWithCountDefaultsToZeroWhenNoRelated()
    {
        $this->db->table('projects')->insert(['name' => 'Empty']);

        $projects = Project::query()->withCount('tasks')->all();

        $this->assertEquals(0, $projects[0]->tasks_count);
    }

    // ── withCount + orderBy (correlated subquery path) ────────────────────

    public function testWithCountAndOrderByInjectsCorrelatedSubquery()
    {
        $this->db->table('projects')->insert(['name' => 'Few']);
        $p1 = $this->db->lastInsertId();
        $this->db->table('projects')->insert(['name' => 'Many']);
        $p2 = $this->db->lastInsertId();

        $this->db->table('tasks')->insert([
            ['name' => 'T1', 'project_id' => $p1, 'hours' => 1, 'cost' => 5],
            ['name' => 'T2', 'project_id' => $p2, 'hours' => 2, 'cost' => 10],
            ['name' => 'T3', 'project_id' => $p2, 'hours' => 3, 'cost' => 15],
            ['name' => 'T4', 'project_id' => $p2, 'hours' => 4, 'cost' => 20],
        ]);

        $projects = Project::query()
            ->withCount('tasks')
            ->orderBy('tasks_count', 'desc')
            ->all();

        $this->assertEquals('Many', $projects[0]->name);
        $this->assertEquals(3, $projects[0]->tasks_count);
        $this->assertEquals('Few', $projects[1]->name);
        $this->assertEquals(1, $projects[1]->tasks_count);
    }

    // ── withSum (GROUP BY path, no orderBy) ───────────────────────────────

    public function testWithSumLoadsHasManySum()
    {
        $this->db->table('projects')->insert(['name' => 'Proj']);
        $p1 = $this->db->lastInsertId();

        $this->db->table('tasks')->insert([
            ['name' => 'T1', 'project_id' => $p1, 'hours' => 3, 'cost' => 100],
            ['name' => 'T2', 'project_id' => $p1, 'hours' => 5, 'cost' => 200],
        ]);

        $projects = Project::query()->withSum('tasks', 'cost')->all();

        $this->assertEquals(300, $projects[0]->tasks_sum_cost);
    }

    public function testWithSumDefaultsToNullWhenNoRelated()
    {
        $this->db->table('projects')->insert(['name' => 'Empty']);

        $projects = Project::query()->withSum('tasks', 'cost')->all();

        $this->assertNull($projects[0]->tasks_sum_cost);
    }

    // ── withSum + orderBy (correlated subquery path) ──────────────────────

    public function testWithSumAndOrderByInjectsCorrelatedSubquery()
    {
        $this->db->table('projects')->insert(['name' => 'Cheap']);
        $p1 = $this->db->lastInsertId();
        $this->db->table('projects')->insert(['name' => 'Expensive']);
        $p2 = $this->db->lastInsertId();

        $this->db->table('tasks')->insert([
            ['name' => 'T1', 'project_id' => $p1, 'hours' => 1, 'cost' => 50],
            ['name' => 'T2', 'project_id' => $p2, 'hours' => 2, 'cost' => 400],
            ['name' => 'T3', 'project_id' => $p2, 'hours' => 3, 'cost' => 600],
        ]);

        $projects = Project::query()
            ->withSum('tasks', 'cost')
            ->orderBy('tasks_sum_cost', 'desc')
            ->all();

        $this->assertEquals('Expensive', $projects[0]->name);
        $this->assertEquals(1000, $projects[0]->tasks_sum_cost);
        $this->assertEquals('Cheap', $projects[1]->name);
        $this->assertEquals(50, $projects[1]->tasks_sum_cost);
    }
}
