<?php

namespace Gregwar\DSD\Fields;

/**
 * Champ date
 *
 * @author Grégoire Passault <g.passault@gmail.com>
 */
class DateField extends Field
{
    /**
     * Sauvegarde du push
     */
    private $pushSave = array();

    /**
     * Sous-champs
     */
    private $fields;

    public function push($var, $value)
    {
	if ($var == 'name' || $var == 'optional' || $var == 'mapping') {
	    parent::push($var, $value);
	} else {
	    $this->pushSave[$var] = $value;
	}
    }

    public function setValue($val)
    {
	if (is_string($val)) {
	    $val = new \DateTime($val);
	}

	if ($val instanceof \DateTime) {
	    $val = array(
		'day' => $val->format('d'),
		'month' => $val->format('m'),
		'year' => $val->format('Y')
	    );
	}

	$this->value = $val;
    }

    public function getValue()
    {
	if (!$this->check()) {
	    return new \DateTime(sprintf('%04d-%02d-%02d',
		$this->value['year'],
		$this->value['month'],
		$this->value['day']
	    ));
	}

	return null;
    }

    public function check()
    {
	$this->generate();
	$filled = 0;

	foreach ($this->fields as $field) {
	    if ($field->getValue() && !$field->check()) {
		$filled++;
	    }
	}

	if ((!$this->optional && $filled==0)||($filled>0 && $filled<count($this->fields))) {
	    return 'La date '.$this->printName().' n\'est pas correcte';
	}
    }

    private function generate()
    {
	$this->fields = array();

	$this->fields[] = $this->createSelect('day', range(1, 31));
	$this->fields[] = $this->createSelect('month', range(1, 12));
	$this->fields[] = $this->createSelect('year', range(date('Y')-120, date('Y')));
    }

    private function createSelect($name, $options)
    {
	$select = new Select;
	$select->push('name', $this->name.'['.$name.']');

	if ($this->value && $this->value[$name]) {
	    $select->setValue($this->value[$name]);
	}

	$this->proxyPush($select);
	$this->buildOptions($select, $options);

	return $select;
    }

    private function buildOptions(&$select, $range)
    {
	foreach ($range as $value) {
	    $option = new Option;
	    $option->setValue($value, true);
	    $option->setLabel($value);
	    $select->addOption($option);
	}
    }

    private function proxyPush($target)
    {
	foreach ($this->pushSave as $var => $val) {
	    $target->push($var, $val);
	}
    }

    public function getHtml()
    {
	$this->generate();
	$html = '';

	foreach ($this->fields as $field) {
	    $html .= $field->getHtml();
	}

	return $html;
    }
}