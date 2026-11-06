<?php

namespace App\ViewModels\Admin;

use App\Services\SettingService;
use DateTimeZone;

class SettingFormData
{
    public function __construct(
        public readonly string $group,
        public readonly array $fields,
        public readonly array $values,
    ) {}

    public static function forGroup(string $group, SettingService $settingService): self
    {
        $config = config("settings.{$group}");
        $fields = self::resolveFields($config['fields']);
        $values = $settingService->getByGroup($group);

        return new self(
            group: $group,
            fields: $fields,
            values: $values,
        );
    }

    private static function resolveFields(array $fields): array
    {
        foreach ($fields as $key => &$field) {
            if (isset($field['options']) && is_string($field['options'])) {
                $field['options'] = self::resolveOptions($field['options']);
            }
        }

        return $fields;
    }

    private static function resolveOptions(string $key): array
    {
        return match ($key) {
            'timezones' => self::getTimezones(),
            'languages' => self::getLanguages(),
            'date_formats' => self::getDateFormats(),
            'time_formats' => self::getTimeFormats(),
            default => [],
        };
    }

    private static function getTimezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        return array_combine($timezones, $timezones);
    }

    private static function getLanguages(): array
    {
        return [
            'en' => 'English',
            'bn' => 'Bengali (বাংলা)',
            'ar' => 'Arabic (العربية)',
            'es' => 'Spanish (Español)',
        ];
    }

    private static function getDateFormats(): array
    {
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd M, Y', 'M d, Y', 'F j, Y'];

        $options = [];
        foreach ($formats as $format) {
            $options[$format] = now()->format($format) . " ({$format})";
        }

        return $options;
    }

    private static function getTimeFormats(): array
    {
        return [
            'h:i A' => '02:30 PM (h:i A)',
            'H:i' => '14:30 (H:i)',
            'h:i:s A' => '02:30:45 PM (h:i:s A)',
            'H:i:s' => '14:30:45 (H:i:s)',
        ];
    }

    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'fields' => $this->fields,
            'values' => $this->values,
        ];
    }
}