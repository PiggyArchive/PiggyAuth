<?php

//Credits to @thebigsmileXD
namespace PiggyAuth;
class FakeAttribute
{
    public $min, $max, $value, $name;

    public function __construct($min, $max, $value, $name)
    {
        $this->min = $min;
        $this->max = $max;
        $this->value = $value;
        $this->name = $name;
    }

    public function getMinValue()
    {
        return $this->min;
    }

    public function getMaxValue()
    {
        return $this->max;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefaultValue()
    {
        return $this->min;
    }

}
