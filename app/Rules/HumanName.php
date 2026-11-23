<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects bot-generated names such as "DdyaMTxVcRXkoaPci": words with several
 * capital letters scattered in the middle, or a single word of 16+ characters.
 */
class HumanName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->looksRandom($value)) {
            $fail('Please enter your real full name.');
        }
    }

    protected function looksRandom(string $name): bool
    {
        foreach (preg_split('/[\s\-\']+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $innerCapitals = preg_match_all('/\p{Lu}/u', mb_substr($word, 1));

            if ($innerCapitals >= 3) {
                return true;
            }

            if (mb_strlen($word) >= 16) {
                return true;
            }
        }

        return false;
    }
}
