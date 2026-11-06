<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class UniqueTranslation implements ValidationRule
{
    protected string $field;
    protected string $modelClass;
    protected ?int $ignoreId;
    protected string $locale;

    public function __construct(string $modelClass, string $field, ?int $ignoreId = null)
    {
        $this->modelClass = $modelClass;
        $this->field = $field;
        $this->ignoreId = $ignoreId;
        $this->locale = App::getLocale();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_subclass_of($this->modelClass, Model::class)) {
            $fail(__('Invalid model specified for translation validation.'));
            return;
        }

        $exists = $this->modelClass::whereJsonContains("{$this->field}->{$this->locale}", $value)
            ->when($this->ignoreId, fn($query) => $query->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($exists) {
            $fail(__(trans('validation.unique')));
        }
    }
}
