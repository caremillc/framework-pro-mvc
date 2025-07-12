<?php declare (strict_types = 1);
namespace Careminate\Http\Requests;

use Careminate\Validation\Validate;

abstract class FormRequest extends Request
{
    protected array $validated = [];
    protected array $errors    = [];

    public function __construct()
    {
        parent::__construct(
            $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER,
            [], file_get_contents('php://input')
        );

        $this->prepareForValidation();

        if (! $this->authorize()) {
            $this->failedAuthorization();
        }

        $validator = new Validate($this->all(), $this->rules(), $this->messages());

        if (! $validator->passes()) {
            $this->errors = $validator->errors();
            $this->failedValidation();
        }

        $this->validated = $validator->validated();
    }

    public function prepareForValidation(): void
    {
        // Optional: override in child class
    }

    public function validated(): array
    {
        return $this->validated;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    protected function failedValidation(): void
    {
        // Default handling: throw exception or redirect
        throw new \Exception('Validation failed: ' . json_encode($this->errors));
    }

    protected function failedAuthorization(): void
    {
        throw new \Exception('This action is unauthorized.');
    }

    abstract public function rules(): array;

    public function messages(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}
