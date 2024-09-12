<?php
namespace sts\template\Traits;

trait CompilesDirectives {
    protected function compileDirectives(string $content): string {
        // Compilare pentru sintaxa @directive
        $content = preg_replace_callback('/@(\w+)(\((.*?)\))?/', function ($matches) {
            $directive = $matches[1];
            $arguments = $matches[3] ?? '';
            if (method_exists($this, $method = 'compile' . ucfirst($directive))) {
                return $this->$method($arguments);
            }
            return $matches[0];
        }, $content);

        // Compilare pentru sintaxa [directive]
        $content = preg_replace_callback('/\[(\w+)(\((.*?)\))?\]/', function ($matches) {
            $directive = $matches[1];
            $arguments = $matches[3] ?? '';
            if (method_exists($this, $method = 'compile' . ucfirst($directive))) {
                return $this->$method($arguments);
            }
            return $matches[0];
        }, $content);

        return $content;
    }

    // Metode de compilare pentru directivele personalizate
    protected function compileForm($arguments) {
        return "<?php echo \$form->open($arguments); ?>";
    }

    protected function compileEndform() {
        return "<?php echo \$form->close(); ?>";
    }
    
    protected function compileCsrf() {
        return "<?php echo \$form->csrf(); ?>";
    }
}
