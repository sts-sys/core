<?php

class TemplateEngine {
    use CompilesDirectives, CompilesConditions, CompilesVariables;

    protected $cachePath;
    protected $templatePath;
    protected $directives = [];
    protected $variables = [];

    public function __construct(string $templatePath, string $cachePath) {
        $this->templatePath = rtrim($templatePath, '/') . '/';
        $this->cachePath = rtrim($cachePath, '/') . '/';
    }

    public function render(string $template, array $variables = []): string {
        // Adăugăm instanța FormHelper la variabilele disponibile
        $variables['form'] = new FormHelper($variables['errors'] ?? [], $variables['old'] ?? []);
        $this->variables = $variables;

        $compiledPath = $this->getCompiledPath($template);

        if (!$this->isCached($template)) {
            $compiled = $this->compileTemplate($template);
            file_put_contents($compiledPath, $compiled);
        }

        extract($variables);
        ob_start();
        include $compiledPath;
        return ob_get_clean();
    }

    protected function getCompiledPath(string $template): string {
        return $this->cachePath . md5($template) . '.php';
    }

    protected function isCached(string $template): bool {
        $compiledPath = $this->getCompiledPath($template);
        $templatePath = $this->templatePath . $template;

        return file_exists($compiledPath) && filemtime($templatePath) <= filemtime($compiledPath);
    }

    protected function compileTemplate(string $template): string {
        $content = file_get_contents($this->templatePath . $template);
        $content = $this->compileDirectives($content);
        $content = $this->compileConditions($content);
        $content = $this->compileVariables($content);
        return $content;
    }
}
