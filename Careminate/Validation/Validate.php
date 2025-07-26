<?php declare(strict_types=1);
namespace Careminate\Validation;

class Validate
{
    protected array $data;
    protected array $rules;
    protected array $messages;
    protected array $errors = [];
    protected array $validated = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;

        $this->run();
    }

    protected function run(): void
    {
        foreach ($this->rules as $field => $rules) {
            $rules = is_string($rules) ? explode('|', $rules) : $rules;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $parameters = [];

                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $parameters = explode(',', $paramStr);
                }

                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    if (! $this->$method($field, $value, $parameters)) {
                        $this->addError($field, $rule, $parameters);
                        break; // Stop at first failure for the field
                    }
                }
            }

            if (! isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    protected function addError(string $field, string $rule, array $params = []): void
    {
        $key = $field . '.' . $rule;
        $message = $this->messages[$key] ?? $this->defaultMessage($field, $rule, $params);
        $this->errors[$field][] = $message;
    }

    protected function defaultMessage(string $field, string $rule, array $params): string
    {
        return match ($rule) {
            'required'  => "The {$field} field is required.",
            'string'    => "The {$field} must be a string.",
            'email'     => "The {$field} must be a valid email address.",
            'min'       => "The {$field} must be at least {$params[0]} characters.",
            'max'       => "The {$field} may not be greater than {$params[0]} characters.",
            'confirmed' => "The {$field} confirmation does not match.",
            'numeric'   => "The {$field} must be a number.",
            'integer'   => "The {$field} must be an integer.",
            'boolean'   => "The {$field} must be true or false.",
            'array'     => "The {$field} must be an array.",
            'in'        => "The {$field} must be one of: " . implode(', ', $params),
            'not_in'    => "The {$field} must not be one of: " . implode(', ', $params),
            'same'      => "The {$field} must match {$params[0]}.",
            'different' => "The {$field} must be different from {$params[0]}.",
            'date'      => "The {$field} must be a valid date.",
            'url'       => "The {$field} must be a valid URL.",
            'regex'     => "The {$field} format is invalid.",
            default     => "The {$field} is invalid.",
        };
    }

    // --- Validation Methods ---

    protected function validateRequired(string $field, mixed $value, array $params): bool
    {
        return !is_null($value) && $value !== '';
    }

    protected function validateString(string $field, mixed $value, array $params): bool
    {
        return is_string($value);
    }

    protected function validateEmail(string $field, mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(string $field, mixed $value, array $params): bool
    {
        return strlen((string) $value) >= (int) $params[0];
    }

    protected function validateMax(string $field, mixed $value, array $params): bool
    {
        return strlen((string) $value) <= (int) $params[0];
    }

    protected function validateConfirmed(string $field, mixed $value, array $params): bool
    {
        $confirmation = $this->data[$field . '_confirmation'] ?? null;
        return $value === $confirmation;
    }

    protected function validateNumeric(string $field, mixed $value, array $params): bool
    {
        return is_numeric($value);
    }

    protected function validateInteger(string $field, mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateBoolean(string $field, mixed $value, array $params): bool
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
    }

    protected function validateArray(string $field, mixed $value, array $params): bool
    {
        return is_array($value);
    }

    protected function validateIn(string $field, mixed $value, array $params): bool
    {
        return in_array($value, $params, true);
    }

    protected function validateNot_in(string $field, mixed $value, array $params): bool
    {
        return !in_array($value, $params, true);
    }

    protected function validateSame(string $field, mixed $value, array $params): bool
    {
        $other = $this->data[$params[0]] ?? null;
        return $value === $other;
    }

    protected function validateDifferent(string $field, mixed $value, array $params): bool
    {
        $other = $this->data[$params[0]] ?? null;
        return $value !== $other;
    }

    protected function validateDate(string $field, mixed $value, array $params): bool
    {
        return strtotime((string) $value) !== false;
    }

    protected function validateUrl(string $field, mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateRegex(string $field, mixed $value, array $params): bool
    {
        return @preg_match($params[0], $value) === 1;
    }
}


