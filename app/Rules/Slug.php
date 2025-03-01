<?php

namespace App\Rules;

use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * DatabaseServiceProvider set Rule Macro and Extends Validator
 *
 * @example
 * Rule Macro - "slug"
 * Validator Extend - Rule::slug()
 */
class Slug
{
    public static $validationStr = 'slug';

    /**
     * Validator Extend
     */
    public function validate(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {

        // Replace Message for fail
        $validator->addReplacer(self::$validationStr,
            function ($message, $attribute, $rule, $parameters) {
                return $this->getValidationMessage($message, $attribute, $rule, $parameters);
            }
        );

        $validSlug = (string) Str::of($value)->slug('-');

        // Setup fields Array
        return strtolower($value) == strtolower($validSlug);
    }

    /**
     * Convert to Validation String
     */
    public function convertToValidationString(): string
    {
        return self::$validationStr.'|string';
    }

    /**
     * Get Validation Message
     */
    private function getValidationMessage($message, $attribute, $rule, $parameters): string
    {
        return 'validation.'.self::$validationStr != $message ?
            $message :
            "The {$attribute} must be url-safe.";
    }
}
