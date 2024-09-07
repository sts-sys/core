<?php
class FormHelper {
    protected $errors = [];
    protected $oldValues = [];
    protected $validator;
    protected $formAttributes = [];

    public function __construct(array $errors = [], array $oldValues = [], $validator = null) {
        $this->errors = $errors;
        $this->oldValues = $oldValues;
        $this->validator = $validator;
    }

    // Inițierea formularului cu atribute
    public function open(array $attributes = []): string {
        $this->formAttributes = $attributes; // Salvăm atributele
        $attrs = $this->buildAttributes($attributes);
        return "<form {$attrs}>";
    }

    // Închiderea formularului
    public function close(): string {
        return "</form>";
    }

    // Metodă simplificată pentru input text
    public function text(string $name, string $label = '', array $attributes = []): string {
        return $this->input('text', $name, $label, $attributes);
    }

    // Metodă simplificată pentru input email
    public function email(string $name, string $label = '', array $attributes = []): string {
        return $this->input('email', $name, $label, $attributes);
    }

    // Metodă simplificată pentru input password
    public function password(string $name, string $label = '', array $attributes = []): string {
        return $this->input('password', $name, $label, $attributes);
    }

    // Metodă pentru generarea input-ului general
    public function input(string $type, string $name, string $label = '', array $attributes = [], string $tooltip = '', string $defaultValue = ''): string {
        $value = $this->old($name) ?: $defaultValue;
        $error = $this->error($name);
        $attrs = $this->buildAttributes($attributes);

        $html = '';
        if ($label) {
            $html .= "<label for='{$name}'>{$label}";
            if ($tooltip) {
                $html .= "<span class='tooltip' title='{$tooltip}'>?</span>";
            }
            $html .= "</label>";
        }

        $html .= "<input type='{$type}' name='{$name}' value='{$value}' {$attrs} class='form-control'>";

        if ($error) {
            $html .= "<span class='error'>{$error}</span>";
        }

        return $html;
    }

    // Funcție pentru generarea mesajelor de eroare
    public function error(string $name): string {
        return $this->errors[$name] ?? '';
    }

    // Funcție pentru construirea atributelor HTML
    protected function buildAttributes(array $attributes): string {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= "{$key}='{$value}' ";
        }
        return trim($attrs);
    }

    // Alte metode...
}
