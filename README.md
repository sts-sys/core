# STS Core Autoloader

Acest proiect oferă un sistem de autoloading personalizat pentru o aplicație PHP, inclusiv gestionarea automată a fișierelor noi, crearea cache-ului, și optimizarea performanței încărcării claselor. 

## Structura Proiectului
```
/vendor │
  ├── sts │
  └── core │
    └── src │
      ├── Core │
      │
        ├── Autoloader.php │
        │ └── Utils │
            │
              └── FileCache.php │
               └── fallback_autoload.php
        ├── cache │
          └── autoload_cache.php
  └── public └── index.php

```

## Funcționalități Cheie

- **Autoloading Automat**: Încărcare automată a claselor folosind autoloader-ul definit.
- **Detectarea Fișierelor Noi**: Verificarea automată pentru fișiere noi și actualizarea cache-ului.
- **Gestionarea Cache-ului pe Bază de Fișiere**: Salvarea și încărcarea informațiilor de cache pentru a îmbunătăți performanța.
- **Fallback pentru Încărcarea Claselor**: Logica de fallback pentru gestionarea claselor care nu pot fi găsite.
- **Permisiuni și Gestionarea Erorilor**: Verificarea permisiunilor și gestionarea adecvată a erorilor pentru a evita probleme neașteptate.

## Instalare

1. **Clonează repository-ul:**

```bash
  git clone https://github.com/username/myapp-core.git
```

```
require_once __DIR__ . '/src/Utils/FileCache.php';
require_once __DIR__ . '/src/Core/Autoloader.php';
require_once __DIR__ . '/fallback_autoload.php';

use STS\Core\Utils\Autoloader;

// Inițializează autoloader-ul
$autoloader = new Autoloader(__DIR__ . '/cache/autoload_cache.php');

// Înregistrează namespace-urile
$autoloader->addNamespaceMap('STS\\Core\\', __DIR__ . '/src/Core');
$autoloader->addNamespaceMap('STS\\Controllers\\', __DIR__ . '/src/Controllers');
$autoloader->addNamespaceMap('STS\\Models\\', __DIR__ . '/src/Models');
$autoloader->addNamespaceMap('STS\\Services\\', __DIR__ . '/src/Services');

// Apelează metoda generateClassMap() pentru a genera și salva harta claselor
$autoloader->generateClassMap(__DIR__ . '/src');

// Înregistrează autoloader-ul
$autoloader->register();
``

### FileCache
```bash
use STS\Core\Utils\FileCache;

$cache = new FileCache('/path/to/cache/autoload_cache.php');

// Setează o valoare în cache
$cache->set('key', 'value');

// Salvează cache-ul
$cache->saveCache();

// Obține o valoare din cache
$value = $cache->get('key');
```


### Explicații

- **Instalare**: Informații despre cum să clonezi repository-ul și să instalezi dependențele.
- **Utilizare**: Informații detaliate despre configurarea autoloader-ului, gestionarea cache-ului și debugging.
- **Contribuții**: Ghid pentru cei care doresc să contribuie la proiect.
- **Licență și Contact**: Informații despre licențiere și contact pentru întrebări sau sugestii.
