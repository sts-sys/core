<?php
class CssHelper
{
    protected $styles = [];

    // Adaugă stiluri pentru o anumită clasă
    public function addClassStyle(string $className, array $styles): self
    {
        if (!isset($this->styles[$className])) {
            $this->styles[$className] = [];
        }

        // Combinăm stilurile noi cu cele existente
        $this->styles[$className] = array_merge($this->styles[$className], $styles);

        return $this;
    }

    // Adaugă stiluri pentru un anumit element
    public function addElementStyle(string $element, array $styles): self
    {
        if (!isset($this->styles[$element])) {
            $this->styles[$element] = [];
        }

        $this->styles[$element] = array_merge($this->styles[$element], $styles);

        return $this;
    }

    // Generează codul CSS
    public function generateCss(): string
    {
        $css = '';

        foreach ($this->styles as $selector => $styles) {
            $css .= $selector . ' {' . PHP_EOL;
            foreach ($styles as $property => $value) {
                $css .= "  {$property}: {$value};" . PHP_EOL;
            }
            $css .= '}' . PHP_EOL;
        }

        return $css;
    }
}
