<?php

namespace App\View\Components\Table;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Header extends Component
{
    public $title;
    public $icon;
    public $tabs;
    public $addButton;

    /**
     * Create a new component instance.
     */
    public function __construct($title, $icon = null, $tabs = [], $addButton = null)
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->tabs = $tabs;
        $this->addButton = $addButton;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.table.header');
    }
}
