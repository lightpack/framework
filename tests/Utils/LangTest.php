<?php

declare(strict_types=1);

use Lightpack\Lang\Lang;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/lightpack-lang-test-' . uniqid();
        @mkdir($this->tempDir . '/en', 0777, true);
        @mkdir($this->tempDir . '/hi', 0777, true);

        file_put_contents($this->tempDir . '/en/messages.php', '<?php return [
            "hello" => "Hello",
            "welcome" => "Welcome, :name!",
            "items" => ":count item|:count items",
        ];');

        file_put_contents($this->tempDir . '/hi/messages.php', '<?php return [
            "hello" => "नमस्ते",
            "welcome" => "स्वागत है, :name!",
        ];');

        file_put_contents($this->tempDir . '/en/validation.php', '<?php return [
            "required" => "The :field field is required.",
        ];');

        file_put_contents($this->tempDir . '/en/forms.php', '<?php return [
            "signup" => [
                "title" => "Sign Up",
                "submit" => "Create Account",
            ],
        ];');
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tempDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    $path = $dir . '/' . $object;
                    if (is_dir($path)) {
                        $this->recursiveDelete($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function testGetLocale()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('en', $lang->getLocale());
    }

    public function testSetLocale()
    {
        $lang = new Lang('en', $this->tempDir);
        $lang->setLocale('hi');
        $this->assertEquals('hi', $lang->getLocale());
    }

    public function testGetTranslation()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('Hello', $lang->get('messages.hello'));
    }

    public function testGetTranslationWithPlaceholders()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('Welcome, John!', $lang->get('messages.welcome', ['name' => 'John']));
    }

    public function testGetTranslationFallback()
    {
        $lang = new Lang('hi', $this->tempDir);
        // "hello" key exists in hi/messages.php
        $this->assertEquals('नमस्ते', $lang->get('messages.hello'));
        // "items" key does not exist in hi/messages.php, should fallback to en
        $this->assertEquals(':count item|:count items', $lang->get('messages.items'));
    }

    public function testGetTranslationReturnsKeyIfNotFound()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('messages.nonexistent', $lang->get('messages.nonexistent'));
    }

    public function testChoiceSingular()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('1 item', $lang->choice('messages.items', 1, ['count' => 1]));
    }

    public function testChoicePlural()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('5 items', $lang->choice('messages.items', 5, ['count' => 5]));
    }

    public function testChoiceFallback()
    {
        $lang = new Lang('hi', $this->tempDir);
        // "items" doesn't exist in hi, fallback to en
        $this->assertEquals('1 item', $lang->choice('messages.items', 1, ['count' => 1]));
    }

    public function testHasReturnsTrueForExistingKey()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertTrue($lang->has('messages.hello'));
    }

    public function testHasReturnsFalseForMissingKey()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertFalse($lang->has('messages.nonexistent'));
    }

    public function testHasChecksFallbackLocale()
    {
        $lang = new Lang('hi', $this->tempDir);
        // "items" exists in fallback (en) but not in hi
        $this->assertTrue($lang->has('messages.items'));
    }

    public function testDefaultFileWhenNoDotInKey()
    {
        $lang = new Lang('en', $this->tempDir);
        // Without dot notation, should default to 'messages' file
        $this->assertEquals('Hello', $lang->get('hello'));
    }

    public function testDifferentFile()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('The email field is required.', $lang->get('validation.required', ['field' => 'email']));
    }

    public function testLocaleOverride()
    {
        $lang = new Lang('en', $this->tempDir);
        // Override to hi locale
        $this->assertEquals('नमस्ते', $lang->get('messages.hello', [], 'hi'));
    }

    public function testNestedTranslation()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertEquals('Sign Up', $lang->get('forms.signup.title'));
        $this->assertEquals('Create Account', $lang->get('forms.signup.submit'));
    }

    public function testNestedHas()
    {
        $lang = new Lang('en', $this->tempDir);
        $this->assertTrue($lang->has('forms.signup.title'));
        $this->assertFalse($lang->has('forms.signup.nonexistent'));
    }

    public function testNestedFallbackToMissingKey()
    {
        $lang = new Lang('en', $this->tempDir);
        // Missing nested key returns the full dot-notation key
        $this->assertEquals('forms.signup.nonexistent', $lang->get('forms.signup.nonexistent'));
    }
}
