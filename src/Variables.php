<?php

namespace Noop\Documents;

use InvalidArgumentException;
use Stringable;

final class Variables
{
    /**
     * Flatten template variables into the text fields word2pdf expects, using bracket
     * notation (points[0][title]) that the service turns back into nested data.
     *
     * @param  array<array-key, mixed>  $variables
     * @return array<string, string>
     */
    public static function normalize(array $variables): array
    {
        $fields = [];

        foreach ($variables as $key => $value) {
            self::add($fields, (string) $key, $value);
        }

        return $fields;
    }

    /** @param  array<string, string>  $fields */
    private static function add(array &$fields, string $key, mixed $value): void
    {
        if (is_array($value) && $value !== []) {
            foreach ($value as $child => $item) {
                self::add($fields, "{$key}[{$child}]", $item);
            }

            return;
        }

        $fields[$key] = match (true) {
            // The template engine treats the text "false" as truthy and prints "null";
            // an empty array is still sent so the template does not print "undefined".
            $value === null, $value === false, $value === [] => '',
            $value === true => '1',
            is_int($value), is_float($value), is_string($value), $value instanceof Stringable => (string) $value,
            default => throw new InvalidArgumentException("Template variable [{$key}] has an unsupported type: ".get_debug_type($value).'.'),
        };
    }
}
