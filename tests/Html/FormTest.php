<?php

declare(strict_types=1);

namespace Tests\Html;

use Lightpack\Config\Config;
use Lightpack\Container\Container;
use Lightpack\Html\Form;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Session;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private Form $form;

    protected function setUp(): void
    {
        $this->form = new Form;

        // Setup session with old input and validation errors.
        // Note: old() and error() cache data in static variables
        // on first call, so we flash data here before any test uses them.
        $driver = new ArrayDriver;
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(
            fn (string $key, $default = null) => match ($key) {
                'session.name' => 'test_session',
                default => $default,
            }
        );

        $session = new Session($driver, $config);
        $session->flash('_old_input', [
            'name' => 'John',
            'email' => 'john@example.com',
            'user' => ['name' => 'Jane', 'role' => 'editor', 'settings' => ['theme' => 'dark']],
            'agree' => '1',
            'role' => 'admin',
            'gender' => 'female',
            'newsletter' => '',
            'interests' => ['coding', 'art'],
            'region' => 'de',
            'level' => 'mid',
            'color' => '#ff0000',
        ]);
        $session->flash('_validation_errors', [
            'name' => 'Name is required.',
            'email' => 'Invalid email.',
            'user.name' => 'User name is required.',
            'xss' => '<script>alert(1)</script>',
        ]);

        Container::getInstance()->instance('session', $session);
    }

    // ============================
    // input()
    // ============================

    public function testInputGeneratesBasicTextField()
    {
        $html = $this->form->input('name');

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="name"', $html);
    }

    public function testInputUsesPassedValueWhenNoOldData()
    {
        $html = $this->form->input('city', ['value' => 'NYC']);

        $this->assertStringContainsString('value="NYC"', $html);
    }

    public function testInputUsesOldValueOverPassedValue()
    {
        $html = $this->form->input('name', ['value' => 'Override']);

        $this->assertStringContainsString('value="John"', $html);
        $this->assertStringNotContainsString('value="Override"', $html);
    }

    public function testInputSupportsCustomType()
    {
        $html = $this->form->input('email', ['type' => 'email']);

        $this->assertStringContainsString('type="email"', $html);
    }

    public function testInputSupportsCustomAttributes()
    {
        $html = $this->form->input('name', ['class' => 'form-control', 'placeholder' => 'Your name']);

        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('placeholder="Your name"', $html);
    }

    public function testInputEscapesValue()
    {
        $html = $this->form->input('x', ['value' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testInputEscapesAttributeValues()
    {
        $html = $this->form->input('x', ['data-foo' => '" onclick="evil()']);

        $this->assertStringNotContainsString('" onclick="evil()', $html);
        $this->assertStringContainsString('&quot; onclick=&quot;evil()', $html);
    }

    public function testInputHandlesBooleanAttributes()
    {
        $html = $this->form->input('x', ['required' => true, 'disabled' => false, 'readonly' => null]);

        $this->assertStringContainsString(' required', $html);
        $this->assertStringNotContainsString('disabled', $html);
        $this->assertStringNotContainsString('readonly', $html);
    }

    public function testInputUsesEmptyOldValueOverPassedValue()
    {
        // old('newsletter') = '' from setUp, so passed value should be ignored
        $html = $this->form->input('newsletter', ['value' => 'default']);

        $this->assertStringContainsString('value=""', $html);
        $this->assertStringNotContainsString('value="default"', $html);
    }

    // ============================
    // textarea()
    // ============================

    public function testTextareaGeneratesBasicTag()
    {
        $html = $this->form->textarea('bio');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="bio"', $html);
        $this->assertStringContainsString('</textarea>', $html);
    }

    public function testTextareaUsesPassedValue()
    {
        $html = $this->form->textarea('bio', ['value' => 'Hello world']);

        $this->assertStringContainsString('>Hello world</textarea>', $html);
    }

    public function testTextareaUsesOldValueOverPassedValue()
    {
        $html = $this->form->textarea('name', ['value' => 'Override']);

        $this->assertStringContainsString('>John</textarea>', $html);
    }

    public function testTextareaEscapesContent()
    {
        $html = $this->form->textarea('x', ['value' => '<script>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testTextareaSupportsRowsAndCols()
    {
        $html = $this->form->textarea('bio', ['rows' => 5, 'cols' => 40]);

        $this->assertStringContainsString('rows="5"', $html);
        $this->assertStringContainsString('cols="40"', $html);
    }

    public function testTextareaUsesEmptyOldValueOverPassedValue()
    {
        $html = $this->form->textarea('newsletter', ['value' => 'default']);

        $this->assertStringContainsString('></textarea>', $html);
        $this->assertStringNotContainsString('>default</textarea>', $html);
    }

    public function testTextareaResolvesArrayNamesWithOldData()
    {
        $html = $this->form->textarea('user[name]');

        $this->assertStringContainsString('>Jane</textarea>', $html);
    }

    // ============================
    // select()
    // ============================

    public function testSelectGeneratesBasicTag()
    {
        $html = $this->form->select('role', ['admin' => 'Admin', 'user' => 'User']);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="role"', $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function testSelectRendersOptions()
    {
        $html = $this->form->select('status', ['active' => 'Active', 'inactive' => 'Inactive']);

        $this->assertStringContainsString('<option value="active">Active</option>', $html);
        $this->assertStringContainsString('<option value="inactive">Inactive</option>', $html);
    }

    public function testSelectMarksPassedOptionAsSelected()
    {
        $html = $this->form->select('role', ['admin' => 'Admin', 'user' => 'User'], ['selected' => 'admin']);

        $this->assertStringContainsString('<option value="admin" selected>Admin</option>', $html);
        $this->assertStringContainsString('<option value="user">User</option>', $html);
    }

    public function testSelectMarksOldValueAsSelected()
    {
        $html = $this->form->select('role', ['admin' => 'Admin', 'user' => 'User']);

        $this->assertStringContainsString('<option value="admin" selected>Admin</option>', $html);
    }

    public function testSelectOldValueOverridesPassedSelected()
    {
        $html = $this->form->select('role', ['admin' => 'Admin', 'user' => 'User'], ['selected' => 'user']);

        // old('role') = 'admin', so admin should be selected
        $this->assertStringContainsString('<option value="admin" selected>Admin</option>', $html);
        $this->assertStringNotContainsString('<option value="user" selected>', $html);
    }

    public function testSelectEscapesOptionValuesAndLabels()
    {
        $html = $this->form->select('x', ['<b>' => '<script>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('value="&lt;b&gt;"', $html);
    }

    public function testSelectMarksEmptyStringOldValueAsSelected()
    {
        // old('newsletter') = ''
        $html = $this->form->select('newsletter', ['' => 'None', '1' => 'Yes']);

        $this->assertStringContainsString('<option value="" selected>None</option>', $html);
        $this->assertStringContainsString('<option value="1">Yes</option>', $html);
    }

    // ============================
    // checkbox()
    // ============================

    public function testCheckboxGeneratesHiddenAndCheckbox()
    {
        $html = $this->form->checkbox('newsletter', 1);

        $this->assertStringContainsString('<input type="hidden" name="newsletter" value="">', $html);
        $this->assertStringContainsString('<input type="checkbox" name="newsletter" value="1">', $html);
    }

    public function testCheckboxCheckedFromOldValue()
    {
        $html = $this->form->checkbox('agree', 1);

        $this->assertStringContainsString('checked', $html);
    }

    public function testCheckboxUncheckedWhenOldValueMismatch()
    {
        $html = $this->form->checkbox('agree', 2);

        // old('agree') = '1', value = 2, so not checked
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testCheckboxUncheckedWhenOldValueIsEmptyString()
    {
        $html = $this->form->checkbox('newsletter', 1);

        // old('newsletter') = '', so not checked
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testCheckboxIgnoresCheckedAttributeWhenOldDataExists()
    {
        // old('agree') = '1', but we pass checked => false
        $html = $this->form->checkbox('agree', 1, ['checked' => false]);

        // old data wins, should still be checked
        $this->assertStringContainsString('checked', $html);
    }

    public function testCheckboxCheckedFromAttributeWhenNoOld()
    {
        $html = $this->form->checkbox('nocheck', 1, ['checked' => true]);

        $this->assertStringContainsString('checked', $html);
    }

    // ============================
    // radio()
    // ============================

    public function testRadioGeneratesInput()
    {
        $html = $this->form->radio('gender', 'male');

        $this->assertStringContainsString('<input type="radio" name="gender" value="male">', $html);
    }

    public function testRadioCheckedFromOldValue()
    {
        $html = $this->form->radio('gender', 'female');

        $this->assertStringContainsString('checked', $html);
    }

    public function testRadioUncheckedWhenOldValueMismatch()
    {
        $html = $this->form->radio('gender', 'male');

        $this->assertStringNotContainsString('checked', $html);
    }

    public function testRadioCheckedFromAttribute()
    {
        $html = $this->form->radio('theme', 'red', ['checked' => true]);

        $this->assertStringContainsString('checked', $html);
    }

    public function testRadioUncheckedWhenOldValueIsEmptyString()
    {
        $html = $this->form->radio('newsletter', 'yes');

        // old('newsletter') = '', so not checked
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testRadioIgnoresCheckedAttributeWhenOldDataExists()
    {
        // old('gender') = 'female', but we pass checked => true on male
        $html = $this->form->radio('gender', 'male', ['checked' => true]);

        // old data wins, should NOT be checked
        $this->assertStringNotContainsString('checked', $html);
    }

    // ============================
    // file()
    // ============================

    public function testFileGeneratesFileInput()
    {
        $html = $this->form->file('avatar');

        $this->assertStringContainsString('<input type="file" name="avatar">', $html);
    }

    public function testFileNeverRepopulatesValue()
    {
        $html = $this->form->file('avatar', ['value' => 'old.jpg']);

        $this->assertStringNotContainsString('value="old.jpg"', $html);
        $this->assertStringNotContainsString('value=', $html);
    }

    // ============================
    // hidden()
    // ============================

    public function testHiddenGeneratesInput()
    {
        $html = $this->form->hidden('id', '42');

        $this->assertEquals('<input type="hidden" name="id" value="42">', $html);
    }

    public function testHiddenWithoutValue()
    {
        $html = $this->form->hidden('token');

        $this->assertEquals('<input type="hidden" name="token">', $html);
    }

    public function testHiddenIgnoresDuplicateValueAttribute()
    {
        $html = $this->form->hidden('id', '42', ['value' => '99']);

        // The $value param should win, $attrs['value'] should be stripped
        $this->assertStringContainsString('value="42"', $html);
        $this->assertStringNotContainsString('value="99"', $html);
    }

    // ============================
    // label()
    // ============================

    public function testLabelGeneratesTag()
    {
        $html = $this->form->label('Name', 'name');

        $this->assertEquals('<label for="name">Name</label>', $html);
    }

    public function testLabelWithoutFor()
    {
        $html = $this->form->label('Name');

        $this->assertEquals('<label>Name</label>', $html);
    }

    public function testLabelWithAttributes()
    {
        $html = $this->form->label('Name', 'name', ['class' => 'block']);

        $this->assertStringContainsString('class="block"', $html);
        $this->assertStringContainsString('for="name"', $html);
    }

    public function testLabelEscapesText()
    {
        $html = $this->form->label('<script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ============================
    // error()
    // ============================

    public function testErrorReturnsMessage()
    {
        $html = $this->form->error('name');

        $this->assertEquals('Name is required.', $html);
    }

    public function testErrorReturnsEmptyWhenNoError()
    {
        $html = $this->form->error('nonexistent');

        $this->assertEquals('', $html);
    }

    public function testErrorReturnsEmptyWhenMessageIsEmptyString()
    {
        $driver = new ArrayDriver;
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(
            fn (string $key, $default = null) => match ($key) {
                'session.name' => 'test_session',
                default => $default,
            }
        );
        $session = new Session($driver, $config);
        $session->flash('_validation_errors', ['empty' => '']);
        Container::getInstance()->instance('session', $session);
        $form = new Form;

        $html = $form->error('empty');

        $this->assertEquals('', $html);
    }

    public function testErrorConvertsArrayNames()
    {
        $html = $this->form->error('user[name]');

        $this->assertEquals('User name is required.', $html);
    }

    public function testErrorEscapesMessage()
    {
        $html = $this->form->error('xss');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ============================
    // open() / close()
    // ============================

    public function testOpenGeneratesFormTag()
    {
        $html = $this->form->open('/submit', 'POST');

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('action="/submit"', $html);
        $this->assertStringContainsString('method="POST"', $html);
    }

    public function testOpenWithCustomAttributes()
    {
        $html = $this->form->open('/submit', 'POST', ['class' => 'form', 'id' => 'myform']);

        $this->assertStringContainsString('class="form"', $html);
        $this->assertStringContainsString('id="myform"', $html);
    }

    public function testOpenIncludesCsrfToken()
    {
        $html = $this->form->open('/submit', 'POST');

        $this->assertStringContainsString('_token', $html);
    }

    public function testOpenExcludesCsrfWhenDisabled()
    {
        $html = $this->form->open('/submit', 'POST', [], false);

        $this->assertStringNotContainsString('_token', $html);
    }

    public function testOpenSpoofsMethod()
    {
        $html = $this->form->open('/update', 'PUT');

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('_method', $html);
        $this->assertStringContainsString('value="PUT"', $html);
    }

    public function testOpenMultipartSetsEnctype()
    {
        $html = $this->form->openMultipart('/upload');

        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('<form', $html);
    }

    public function testCloseReturnsClosingTag()
    {
        $html = $this->form->close();

        $this->assertEquals('</form>', $html);
    }

    // ============================
    // nameToDot() (via reflection)
    // ============================

    public function testNameToDotConvertsBrackets()
    {
        $method = new \ReflectionMethod(Form::class, 'nameToDot');
        $method->setAccessible(true);

        $this->assertEquals('user.email', $method->invoke($this->form, 'user[email]'));
        $this->assertEquals('items.0.name', $method->invoke($this->form, 'items[0][name]'));
    }

    public function testNameToDotHandlesTrailingBrackets()
    {
        $method = new \ReflectionMethod(Form::class, 'nameToDot');
        $method->setAccessible(true);

        $this->assertEquals('items', $method->invoke($this->form, 'items[]'));
    }

    public function testNameToDotLeavesPlainNames()
    {
        $method = new \ReflectionMethod(Form::class, 'nameToDot');
        $method->setAccessible(true);

        $this->assertEquals('name', $method->invoke($this->form, 'name'));
    }

    // ============================
    // Array names (integration)
    // ============================

    public function testInputResolvesArrayNamesWithOldData()
    {
        $html = $this->form->input('user[name]');

        $this->assertStringContainsString('value="Jane"', $html);
    }

    public function testCheckboxResolvesArrayNamesWithOldData()
    {
        $html = $this->form->checkbox('user[settings][theme]', 'dark');

        $this->assertStringContainsString('checked', $html);
    }

    public function testSelectHandlesMissingArrayOldData()
    {
        $html = $this->form->select('user[missing]', ['a' => 'A', 'b' => 'B']);

        $this->assertStringContainsString('<option value="a">A</option>', $html);
        $this->assertStringNotContainsString('selected', $html);
    }

    public function testSelectResolvesArrayNamesWithOldData()
    {
        $html = $this->form->select('user[role]', ['editor' => 'Editor', 'viewer' => 'Viewer']);

        $this->assertStringContainsString('<option value="editor" selected>Editor</option>', $html);
        $this->assertStringContainsString('<option value="viewer">Viewer</option>', $html);
    }

    // ============================
    // select() optgroups
    // ============================

    public function testSelectRendersOptgroups()
    {
        $html = $this->form->select('area', [
            'Europe' => ['uk' => 'UK', 'de' => 'Germany'],
            'Asia' => ['jp' => 'Japan'],
        ]);

        $this->assertStringContainsString('<optgroup label="Europe">', $html);
        $this->assertStringContainsString('<option value="uk">UK</option>', $html);
        $this->assertStringContainsString('<option value="de">Germany</option>', $html);
        $this->assertStringContainsString('</optgroup>', $html);
        $this->assertStringContainsString('<optgroup label="Asia">', $html);
        $this->assertStringContainsString('<option value="jp">Japan</option>', $html);
    }

    public function testSelectRendersMixedFlatAndOptgroupOptions()
    {
        $html = $this->form->select('mixed', [
            'all' => 'All',
            'Regions' => ['uk' => 'UK', 'de' => 'Germany'],
        ]);

        $this->assertStringContainsString('<option value="all">All</option>', $html);
        $this->assertStringContainsString('<optgroup label="Regions">', $html);
        $this->assertStringContainsString('<option value="uk">UK</option>', $html);
    }

    public function testSelectOptgroupMarksSelectedOption()
    {
        $html = $this->form->select('region', [
            'Europe' => ['uk' => 'UK', 'de' => 'Germany'],
        ], ['selected' => 'de']);

        $this->assertStringContainsString('<option value="de" selected>Germany</option>', $html);
        $this->assertStringNotContainsString('<option value="uk" selected>', $html);
    }

    public function testSelectOptgroupMarksSelectedFromOldData()
    {
        // old('region') = 'de'
        $html = $this->form->select('region', [
            'Europe' => ['uk' => 'UK', 'de' => 'Germany', 'fr' => 'France'],
            'Asia' => ['jp' => 'Japan'],
        ]);

        $this->assertStringContainsString('<option value="de" selected>Germany</option>', $html);
        $this->assertStringNotContainsString('<option value="uk" selected>', $html);
        $this->assertStringNotContainsString('<option value="fr" selected>', $html);
        $this->assertStringNotContainsString('<option value="jp" selected>', $html);
    }

    // ============================
    // select() multiple
    // ============================

    public function testSelectMultipleMarksMultipleOptions()
    {
        $html = $this->form->select('interests', ['coding' => 'Coding', 'music' => 'Music', 'art' => 'Art'], ['multiple' => true, 'selected' => ['coding', 'art']]);

        $this->assertStringContainsString('<option value="coding" selected>Coding</option>', $html);
        $this->assertStringContainsString('<option value="music">Music</option>', $html);
        $this->assertStringContainsString('<option value="art" selected>Art</option>', $html);
    }

    public function testSelectMultipleViaSelectMultipleMethod()
    {
        $html = $this->form->selectMultiple('interests', ['a' => 'A', 'b' => 'B']);

        $this->assertStringContainsString('<select name="interests"', $html);
        $this->assertStringContainsString('multiple', $html);
    }

    public function testSelectMultipleWithEmptyArrayOldValue()
    {
        // Flash empty array for 'tags', then create a new form instance
        $driver = new ArrayDriver;
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(
            fn (string $key, $default = null) => match ($key) {
                'session.name' => 'test_session',
                default => $default,
            }
        );
        $session = new Session($driver, $config);
        $session->flash('_old_input', ['tags' => []]);
        Container::getInstance()->instance('session', $session);
        $form = new Form;

        $html = $form->selectMultiple('tags', ['a' => 'A', 'b' => 'B']);

        $this->assertStringNotContainsString('selected', $html);
    }

    public function testSelectMultipleMarksOptionsFromOldData()
    {
        // old('interests') = ['coding', 'art']
        $html = $this->form->selectMultiple('interests', ['coding' => 'Coding', 'art' => 'Art', 'music' => 'Music']);

        $this->assertStringContainsString('<option value="coding" selected>Coding</option>', $html);
        $this->assertStringContainsString('<option value="art" selected>Art</option>', $html);
        $this->assertStringContainsString('<option value="music">Music</option>', $html);
    }

    // ============================
    // Type convenience methods
    // ============================

    public function testEmailGeneratesEmailInput()
    {
        $html = $this->form->email('email');

        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('name="email"', $html);
    }

    public function testPasswordGeneratesPasswordInput()
    {
        $html = $this->form->password('password');

        $this->assertStringContainsString('type="password"', $html);
    }

    public function testNumberGeneratesNumberInput()
    {
        $html = $this->form->number('qty', ['min' => 1]);

        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('min="1"', $html);
    }

    public function testTelGeneratesTelInput()
    {
        $html = $this->form->tel('phone');

        $this->assertStringContainsString('type="tel"', $html);
    }

    public function testUrlGeneratesUrlInput()
    {
        $html = $this->form->url('website');

        $this->assertStringContainsString('type="url"', $html);
    }

    public function testDateGeneratesDateInput()
    {
        $html = $this->form->date('birthdate');

        $this->assertStringContainsString('type="date"', $html);
    }

    public function testSearchGeneratesSearchInput()
    {
        $html = $this->form->search('query');

        $this->assertStringContainsString('type="search"', $html);
    }

    public function testColorGeneratesColorInput()
    {
        $html = $this->form->color('theme');

        $this->assertStringContainsString('type="color"', $html);
    }

    public function testEmailUsesOldValue()
    {
        $html = $this->form->email('email', ['value' => 'override@example.com']);

        $this->assertStringContainsString('value="john@example.com"', $html);
        $this->assertStringNotContainsString('value="override@example.com"', $html);
    }

    public function testColorUsesOldValue()
    {
        $html = $this->form->color('color');

        $this->assertStringContainsString('value="#ff0000"', $html);
    }

    // ============================
    // submit() / button()
    // ============================

    public function testSubmitGeneratesInput()
    {
        $html = $this->form->submit('Save');

        $this->assertEquals('<input type="submit" value="Save">', $html);
    }

    public function testSubmitWithAttributes()
    {
        $html = $this->form->submit('Save', ['class' => 'btn', 'disabled' => true]);

        $this->assertStringContainsString('class="btn"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function testButtonGeneratesButtonElement()
    {
        $html = $this->form->button('Click Me');

        $this->assertEquals('<button>Click Me</button>', $html);
    }

    public function testButtonWithAttributes()
    {
        $html = $this->form->button('Submit', ['type' => 'submit', 'class' => 'btn']);

        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('class="btn"', $html);
        $this->assertStringContainsString('>Submit</button>', $html);
    }

    // ============================
    // checkboxes() / radios()
    // ============================

    public function testCheckboxesGeneratesMultiple()
    {
        $html = $this->form->checkboxes('interests[]', ['coding' => 'Coding', 'music' => 'Music']);

        $this->assertStringContainsString('name="interests[]"', $html);
        $this->assertStringContainsString('value="coding"', $html);
        $this->assertStringContainsString('value="music"', $html);
        $this->assertStringContainsString('Coding', $html);
        $this->assertStringContainsString('Music', $html);
    }

    public function testCheckboxesRespectsOldDataSelection()
    {
        // old('interests') = ['coding', 'art']
        $html = $this->form->checkboxes('interests[]', ['coding' => 'Coding', 'art' => 'Art', 'music' => 'Music']);

        $this->assertStringContainsString('value="coding" checked', $html);
        $this->assertStringContainsString('value="art" checked', $html);
        $this->assertStringNotContainsString('value="music" checked', $html);
    }

    public function testCheckboxesNoneCheckedWhenOldValueIsEmptyString()
    {
        $html = $this->form->checkboxes('newsletter[]', ['yes' => 'Yes', 'no' => 'No']);

        $this->assertStringNotContainsString('checked', $html);
    }

    public function testRadiosGeneratesMultiple()
    {
        $html = $this->form->radios('gender', ['male' => 'Male', 'female' => 'Female']);

        $this->assertStringContainsString('name="gender"', $html);
        $this->assertStringContainsString('value="male"', $html);
        $this->assertStringContainsString('value="female"', $html);
        $this->assertStringContainsString('Male', $html);
        $this->assertStringContainsString('Female', $html);
    }

    public function testRadiosRespectsOldDataSelection()
    {
        // old('level') = 'mid'
        $html = $this->form->radios('level', ['junior' => 'Junior', 'mid' => 'Mid', 'senior' => 'Senior']);

        $this->assertEquals(1, substr_count($html, 'checked'));
        $this->assertStringContainsString('value="mid" checked', $html);
        $this->assertStringNotContainsString('value="junior" checked', $html);
        $this->assertStringNotContainsString('value="senior" checked', $html);
    }

    public function testRadiosNoneCheckedWhenOldValueIsEmptyString()
    {
        $html = $this->form->radios('newsletter', ['yes' => 'Yes', 'no' => 'No']);

        $this->assertStringNotContainsString('checked', $html);
    }

    // ============================
    // datalist()
    // ============================

    public function testDatalistGeneratesTag()
    {
        $html = $this->form->datalist('cities', ['NYC', 'LA', 'Chicago']);

        $this->assertStringContainsString('<datalist id="cities">', $html);
        $this->assertStringContainsString('<option value="NYC"></option>', $html);
        $this->assertStringContainsString('<option value="LA"></option>', $html);
        $this->assertStringContainsString('<option value="Chicago"></option>', $html);
        $this->assertStringContainsString('</datalist>', $html);
    }

    public function testDatalistEscapesOptions()
    {
        $html = $this->form->datalist('x', ['<script>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testSubmitEscapesValue()
    {
        $html = $this->form->submit('<script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testButtonEscapesText()
    {
        $html = $this->form->button('<script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
