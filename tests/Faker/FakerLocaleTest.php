<?php
use Lightpack\Faker\Faker;
use PHPUnit\Framework\TestCase;

class FakerLocaleTest extends TestCase
{
    public function testCustomLocaleFile()
    {
        $customFile = sys_get_temp_dir() . '/custom-locale.php';
        file_put_contents($customFile, "<?php return [
            'firstNames' => ['Jean'],
            'lastNames' => ['Dupont'],
            'domains' => ['monsite.fr'],
            'words' => ['bonjour'],
            'cities' => ['Paris'],
            'states' => ['Île-de-France'],
            'countries' => ['France'],
            'companies' => ['Entreprise Géniale'],
            'jobTitles' => ['Ingénieur'],
            'productNames' => ['ProduitX'],
            'streets' => ['Rue de Paris'],
            'phonePrefixes' => ['+33-'],
        ];");

        $faker = new Faker('custom', $customFile);
        $this->assertSame('Jean Dupont', $faker->name());
        $this->assertSame('Paris', $faker->city());
        $this->assertStringEndsWith('@monsite.fr', $faker->email());
        unlink($customFile);
    }

    public function testCustomLocaleArray()
    {
        $faker = new Faker('custom');
        $faker->setLocaleData([
            'firstNames' => ['Anna'],
            'lastNames' => ['Smith'],
            'domains' => ['example.test'],
            'words' => ['foo'],
            'cities' => ['Metropolis'],
            'states' => ['StateX'],
            'countries' => ['CountryY'],
            'companies' => ['TestCorp'],
            'jobTitles' => ['Tester'],
            'productNames' => ['TestProduct'],
            'streets' => ['Test Street'],
            'phonePrefixes' => ['+99-'],
        ]);
        $this->assertSame('Anna Smith', $faker->name());
        $this->assertSame('Metropolis', $faker->city());
        $this->assertStringEndsWith('@example.test', $faker->email());
    }
}
