<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public string $name;
    public ?string $label;
    public array $options;
    public $selected;
    public bool $select2;
    public bool $multiple;

    /**
     * Create the component instance.
     */
    public function __construct(
        string $name,
        array $options,
               $selected = null,
        ?string $label = null,
        bool $select2 = false,
        bool $multiple = false
    ) {
        $this->name = $name;
        $this->options = $options;
        $this->selected = $selected;
        $this->label = $label;
        $this->select2 = $select2;
        $this->multiple = $multiple;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form.select');
    }
}
