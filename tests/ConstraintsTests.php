<?php

use Gregwar\Formidable\Form;
use Gregwar\Formidable\Factory;

/**
 * Special type "file" returning hash of the file instead of actually saving it 
 */
class FileField_NoSave extends \Gregwar\Formidable\Fields\FileField
{
    public function save($filename)
    {
        return sha1(file_get_contents($this->datas['tmp_name']));
    }
}

/**
 * Testing constraints
 *
 * @author Grégoire Passault <g.passault@gmail.com>
 */
class ConstraintsTests extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing rendering a required field
     */
    public function testRequired()
    {
        $form = $this->getForm('required.html');
        $this->assertContains('required=', "$form");

        $this->assertAccept($form, array(
            'name' => 'jack'
        ));

        $this->assertEquals('jack', $form->name);

        $this->assertRefuse($form, array(
            'name' => ''
        ));

        $this->assertAccept($form, array(
            'name' => '0'
        ));
    }

    /**
     * Testing sending an array on a simple value
     */
    public function testArray()
    {
        $form = $this->getForm('required.html');

        $this->assertRefuse($form, array(
            'name' => array('xyz')
        ));
    }

    /**
     * Testing rendering an optional field
     */
    public function testOptional()
    {
        $form = $this->getForm('optional.html');
        $this->assertNotContains('required=', "$form");

        $this->assertAccept($form, array(
            'name' => ''
        ));
 
        $this->assertEquals('', $form->name);

        $this->assertAccept($form, array(
            'name' => 'Jack'
        ));

        $this->assertEquals('Jack', $form->name);
    }

    /**
     * Testing maxlength constraint
     */
    public function testMaxLength()
    {
        $form = $this->getForm('maxlength.html');
        
        $this->assertContains('maxlength', "$form");

        $this->assertAccept($form, array(
            'nick' => str_repeat('x', 100)
        ));

        $this->assertRefuse($form, array(
            'nick' => str_repeat('x', 101)
        ));
    }

    /**
     * Testing minlength constraint
     */
    public function testMinLength()
    {
        $form = $this->getForm('minlength.html');
        
        $this->assertNotContains('minlength', "$form");

        $this->assertAccept($form, array(
            'nick' => str_repeat('x', 10)
        ));

        $this->assertRefuse($form, array(
            'nick' => str_repeat('x', 9)
        ));
    }

    /**
     * Testing the regex constraint
     */
    public function testRegex()
    {
        $form = $this->getForm('regex.html');

        $this->assertNotContains('regex', "$form");

        $this->assertAccept($form, array(
            'nick' => 'hello'
        ));

        $this->assertRefuse($form, array(
            'nick' => 'hm hm'
        ));
    }

    /**
     * Testing hidden field
     */
    public function testHidden()
    {
        $form = $this->getForm('hidden.html');

        $this->assertContains('hidden', "$form");
        $this->assertContains('123', "$form");

        $this->assertAccept($form, array(
            'cache' => '123'
        ));

        $this->assertRefuse($form, array(
            'cache' => str_repeat('x', 25)
        ));
    }

    /**
     * Testing min and max
     */
    public function testMinMax()
    {
        $form = $this->getForm('minmax.html');

        $this->assertNotContains('min', "$form");
        $this->assertNotContains('max', "$form");

        $this->assertAccept($form, array(
            'num' => 7
        ));

        $this->assertRefuse($form, array(
            'num' => 3
        ));

        $this->assertRefuse($form, array(
            'num' => 13
        ));
    }

    /**
     * Testing custom constraint
     */
    public function testCustomConstraint()
    {
        $form = $this->getForm('custom-constraint.html');

        $form->addConstraint('name', function($value) {
            if ($value[0] == 'J') {
                return 'Le nom ne doit pas commencer par J';
            }
        });

        $this->assertAccept($form, array(
            'name' => 'Paul'
        ));

        $this->assertRefuse($form, array(
            'name' => 'Jack'
        ));
    }

    /**
     * Test du typehint
     * Testing typehinting
     * @expectedException       \InvalidArgumentException
     */
    public function testCustomConstraintType()
    {
        $form = $this->getForm('custom-constraint.html');

        $form->addConstraint('name', 'meeeh');
    }

    /**
     * Testing captcha constraint
     */
    public function testCaptcha()
    {
        $form = $this->getForm('captcha.html');
        $html = "$form";

        $this->assertContains('<img', $html);
        $this->assertContains('code', $html);
        $this->assertContains('type="text"', $html);

        $captchaValue = $form->getField('code')->getCaptchaValue();

        $this->assertNotContains($captchaValue, $html);

        $this->assertAccept($form, array(
            'code' => $captchaValue
        ));

        $form = $this->getForm('captcha.html');
        $html = "$form";

        $this->assertRefuse($form, array(
            'code' => 'xxx'
        ));
    }

    /**
     * Testing that a captcha can't be reused
     */
    public function testCaptchaNotReusable()
    {
        $form = $this->getForm('captcha.html');
        $html = "$form";

        $captchaValue = $form->getField('code')->getCaptchaValue();

        $this->assertAccept($form, array(
            'code' => $captchaValue
        ));

        $this->assertRefuse($form, array(
            'code' => $captchaValue
        ));
    }

    /**
     * Testing posting a value that is not possible for a select
     */
    public function testSelectOut()
    {
        $form = $this->getForm('select.html');

        $this->assertAccept($form, array(
            'city' => 'la'
        ));

        $this->assertEquals('la', $form->city);

        $this->assertRefuse($form, array(
            'city' => 'xy'
        ));
        
        $this->assertRefuse($form, array(
            'city' => array('x')
        ));
    }

    /**
     * Testing multiple
     */
    public function testMultiple()
    {
        $form = $this->getForm('multiple.html');
        $html = "$form";

        $this->assertContains('<script', $html);
        $this->assertContains('<a', $html);

        $this->assertRefuse($form, array(
            'names' => ''
        ));

        $this->assertAccept($form, array(
            'names' => array('a', 'b')
        ));

        $this->assertRefuse($form, array(
            'names' => array(str_repeat('x', 25))
        ));

        $this->assertRefuse($form, array(
            'names' => array(array('a', 'b'))
	));

	$form->names = array('xxx');
	$html = "$form";
	$this->assertContains('xxx', $html);
    }

    /**
     * Testing that we can't change the readonly field
     */
    public function testReadOnly()
    {
        $form = $this->getForm('readonly.html');
        $html = "$form";

        $this->assertContains('Jack', $html);
        $this->assertContains('selected=', $html);

        $this->assertAccept($form, array(
            'nom' => 'Jack',
            'color' => 'g'
        ));

        $this->assertRefuse($form, array(
            'nom' => 'Jack',
            'color' => 'y'
        ));
    }

    /**
     * Testing reseting a form
     */
    public function testReset()
    {
        $form = $this->getForm('reset.html');

        $this->assertEquals('Jack', $form->name);

        $this->assertAccept($form, array(
            'name' => 'Paul'
        ));

        $this->assertEquals('Paul', $form->name);
        $form->reset();
        $this->assertEquals('Jack', $form->name);
    }

    /**
     * Testing sourcing an <options>
     */
    public function testOptions()
    {
        $form = $this->getForm('options.html');
        $html = "$form";

        $this->assertNotContains('option', $html);
        $this->assertNotContains('pretty', $html);
        $this->assertNotContains('Cat', $html);

        $form->source('animals', array(
            '1' => 'Cat',
            '2' => 'Dog', 
            '3' => 'Zebra'
        ));

        $html = "$form";

        $this->assertContains('option', $html);
        $this->assertContains('pretty', $html);
        $this->assertContains('Cat', $html);

        $this->assertAccept($form, array(
            'animal' => 2
        ));

        $this->assertRefuse($form, array(
            'animal' => 4
        ));
    }

    /**
     * Testing radios fields
     */
    public function testRadios()
    {
        $form = $this->getForm('radios.html');
        $html = "$form";

        $this->assertEquals(2, substr_count($form, 'radio'));
        $this->assertEquals(2, substr_count($form, 'gender'));
        $this->assertEquals(1, substr_count($form, 'checked="checked"'));

        $this->assertEquals('male', $form->getValue('gender'));

        $form->setValue('gender', 'female');
        $html = "$form";

        $this->assertEquals($form->getValue('gender'), 'female');
        $this->assertEquals(2, substr_count($form, 'radio'));
        $this->assertEquals(2, substr_count($form, 'gender'));
        $this->assertEquals(1, substr_count($form, 'checked="checked"'));

        $this->assertAccept($form, array(
            'gender' => 'female'
        ));
        $this->assertAccept($form, array(
            'gender' => 'male'
        ));
        $this->assertRefuse($form, array(
            'gender' => 'something'
        ));
    }

    /**
     * Testing multiradio
     */
    public function testMultiradio()
    {
        $form = $this->getForm('multiradio.html');
        $html = "$form";

        $this->assertNotContains('radio', $html);
        $this->assertNotContains('pretty', $html);
        $this->assertNotContains('Cat', $html);

        $form->source('animals', array(
            '1' => 'Cat',
            '2' => 'Dog',
            '3' => 'Zebra'
        ));

        $html = "$form";

        $this->assertContains('radio', $html);
        $this->assertContains('pretty', $html);
        $this->assertContains('Cat', $html);

        $this->assertAccept($form, array(
            'animal' => '2'
        ));

        $this->assertEquals('2', $form->animal);

        $this->assertContains('checked', "$form");

        $this->assertRefuse($form, array(
            'animal' => '4'
        ));

        $this->assertRefuse($form, array(
            'animal' => ''
        ));
    }

    /**
     * Testing a multiradio optional
     */
    public function testOptionalMultiradio()
    {
        $form = $this->getForm('multiradio_optional.html');

        $form->source('animals', array(
            '1' => 'Cat',
            '2' => 'Dog',
            '3' => 'Zebra'
        ));

        $this->assertAccept($form, array(
            'animal' => '2'
        ));

        $this->assertAccept($form, array(
            'animal' => ''
        ));
    }

    /**
     * Testing a multicheckbox
     */
    public function testMultiCheckBox()
    {
        $form = $this->getForm('multicheckbox.html');
        $html = "$form";

        $this->assertNotContains('checkbox', $html);
        $this->assertNotContains('pretty', $html);
        $this->assertNotContains('Cat', $html);

        $form->source('animals', array(
            '1' => 'Cat',
            '2' => 'Dog',
            '3' => 'Zebra'
        ));

        $html = "$form";

        $this->assertContains('checkbox', $html);
        $this->assertContains('pretty', $html);
        $this->assertContains('Cat',$html);

        $this->assertAccept($form, array(
            'animals' => array('1' => '1')
        ));

        $this->assertAccept($form, array(
            'animals' => array('1' => '1', '3' => '1')
        ));

        $this->assertTrue(in_array('1', $form->animals));
        $this->assertTrue(in_array('3', $form->animals));

        $this->assertContains('checked', "$form");

        $this->assertAccept($form, array(
            'animals' => array()
        ));

        $this->assertNotContains('checked', "$form");
    }

    /**
     * Testing file type
     */
    public function testFile()
    {
        $factory = new Factory;
        $factory->registerType('file', '\FileField_NoSave');
        $form = $factory->getForm(__DIR__.'/files/form/upload.html');
        $file = __DIR__.'/files/upload/test.txt';
        $hash = sha1(file_get_contents($file));

        $this->assertContains('file', "$form");
        $this->assertContains('multipart', "$form");

        $this->assertAccept($form, array(), array(
            'attachement' => array(
                'size' => filesize($file),
                'tmp_name' => $file,
                'name' => 'test.txt'
            )
        ));

        $this->assertEquals($hash, $form->attachement->save(null));
        $this->assertEquals('test.txt', $form->getField('attachement')->fileName());

        $file = __DIR__.'/files/upload/long.txt';
        $this->assertRefuse($form, array(), array(
            'attachement' => array(
                'size' => filesize($file),
                'tmp_name' => $file,
                'name' => 'long.txt'
            )
        ));
    }

    /**
     * Testing the filetype="image"
     */
    public function testFileImage()
    {
        $form = $this->getForm('upload_image.html');

        $file = __DIR__.'/files/upload/image.jpg';
        $this->assertAccept($form, array(), array(
            'photo' => array(
                'size' => filesize($file),
                'tmp_name' => $file,
                'name' => 'image.jpg'
            )
        ));

        $file = __DIR__.'/files/upload/test.txt';
        $this->assertRefuse($form, array(), array(
            'photo' => array(
                'size' => filesize($file),
                'tmp_name' => $file,
                'name' => 'test.txt'
            )
        ));
    }

    /**
     * Testing date type
     */
    public function testDate()
    {
	$form = $this->getForm('date.html');
	$html = "$form";

	$this->assertContains('select', $html);
	$this->assertContains(date('Y'), $html);

	$this->assertAccept($form, array(
	    'date' => array(
		'day' => 25,
		'month' => 2,
		'year' => date('Y')-5
	    )
	));

	$this->assertRefuse($form, array(
	    'date' => array(
		'day' => 25,
		'month' => 55,
		'year' => date('Y')-5
	    )
	));


	$this->assertRefuse($form, array(
	    'date' => array(
		'day' => '',
		'month' => 2,
		'year' => date('Y')-5
	    )
	));
    }

    /**
     * Testing that data are escaped
     */
    public function testEscaping()
    {
        $form = $this->getForm('escape.html');

        $this->assertAccept($form, array(
            'name' => 'a"b',
            'text' => 'a',
        ));

        $this->assertContains('&quot;', "$form");

        $this->assertAccept($form, array(
            'name' => 'a',
            'text' => 'a"b',
        ));

        $this->assertContains('&quot;', "$form");
    }

    /**
     * Testing that a form accept data
     */
    private function assertAccept($form, $data, $files = array()) {
        $_POST = $data;
        $_POST['csrf_token'] = $form->getToken();
        $_FILES = $files;
        $this->assertTrue($form->posted());
        $this->assertEmpty($form->check());
    }

    /**
     * Testing that a form doesn't accept data
     */
    private function assertRefuse($form, $data, $files = array()) {
        $_POST = $data;
        $_POST['csrf_token'] = $form->getToken();
        $_FILES = $files;
        $this->assertTrue($form->posted());
        $this->assertNotEmpty($form->check());
    }

    private function getForm($file)
    {
        return new Form(__DIR__.'/files/form/'.$file);
    }
}
