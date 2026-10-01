<?php
declare(strict_types=1);

namespace App\Utils;

/**
 * Validador robusto de datos con soporte para reglas personalizadas y mensajes en español
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $sanitized = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->sanitizeAll();
    }

    /**
     * Sanitiza todos los valores de entrada de forma recursiva
     */
    private function sanitizeAll(): void
    {
        foreach ($this->data as $key => $value) {
            $this->sanitized[$key] = $this->sanitizeValue($value);
        }
    }

    /**
     * Sanitiza un valor individual (elimina tags, espacios extras, caracteres de control)
     */
    public static function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            $value = str_replace(chr(0), '', $value); // Previene null-byte injection
            $value = strip_tags($value);
            return $value;
        }

        if (is_array($value)) {
            $cleaned = [];
            foreach ($value as $k => $v) {
                $cleaned[$k] = self::sanitizeValue($v);
            }
            return $cleaned;
        }

        return $value;
    }

    /**
     * Ejecuta las reglas de validación
     *
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rulesString) {
            $ruleList = is_array($rulesString) ? $rulesString : explode('|', $rulesString);
            $value = $this->sanitized[$field] ?? null;

            foreach ($ruleList as $rule) {
                $ruleParams = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $ruleParams = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $ruleName = trim($ruleName);

                // Si no es requerido y está vacío, omitir las demás reglas
                if ($ruleName !== 'required' && ($value === null || $value === '')) {
                    continue;
                }

                $this->applyRule($field, $ruleName, $value, $ruleParams);
            }
        }

        return empty($this->errors);
    }

    /**
     * Aplica una regla específica al campo
     */
    private function applyRule(string $field, string $rule, mixed $value, array $params): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "El campo '{$field}' es obligatorio.");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "El campo '{$field}' debe ser un correo electrónico válido.");
                }
                break;

            case 'min':
                $min = (int)($params[0] ?? 0);
                if (is_string($value)) {
                    if (mb_strlen($value, 'UTF-8') < $min) {
                        $this->addError($field, "El campo '{$field}' debe tener al menos {$min} caracteres.");
                    }
                } elseif (is_numeric($value) && (float)$value < $min) {
                    $this->addError($field, "El campo '{$field}' debe ser mayor o igual a {$min}.");
                }
                break;

            case 'max':
                $max = (int)($params[0] ?? 255);
                if (is_string($value)) {
                    if (mb_strlen($value, 'UTF-8') > $max) {
                        $this->addError($field, "El campo '{$field}' no debe superar los {$max} caracteres.");
                    }
                } elseif (is_numeric($value) && (float)$value > $max) {
                    $this->addError($field, "El campo '{$field}' debe ser menor o igual a {$max}.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "El campo '{$field}' debe ser un valor numérico.");
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "El campo '{$field}' debe ser un número entero válido.");
                }
                break;

            case 'alpha_spaces':
                // Solo letras y espacios (permite tildes y caracteres en español)
                if (!preg_match('/^[\p{L}\s]+$/u', (string)$value)) {
                    $this->addError($field, "El campo '{$field}' solo debe contener letras y espacios.");
                }
                break;

            case 'alpha_num':
                if (!preg_match('/^[a-zA-Z0-9]+$/', (string)$value)) {
                    $this->addError($field, "El campo '{$field}' solo puede contener letras y números.");
                }
                break;

            case 'in':
                if (!in_array((string)$value, $params, true)) {
                    $validOptions = implode(', ', $params);
                    $this->addError($field, "El campo '{$field}' debe ser una de las siguientes opciones: {$validOptions}.");
                }
                break;
        }
    }

    /**
     * Agrega un mensaje de error para un campo
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Indica si la validación fue exitosa
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Indica si la validación falló
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retorna los errores agrupados por campo
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna los datos limpios y sanitizados
     */
    public function getSanitizedData(): array
    {
        return $this->sanitized;
    }

    /**
     * Método estático para validar y sanitizar rápidamente
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->validate();
        return $validator;
    }
}
