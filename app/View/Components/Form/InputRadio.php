<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputRadio extends Component
{
    public $name;
    public $label;
    public $options;
    public $selected;
    public $required;

    /**
     * Create a new component instance.
     */
    public function __construct($name, $label, $options = [], $selected = '', $required = false)
    {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->selected = $selected;
        $this->required = $required;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form.input-radio');
    }
}
