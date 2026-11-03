# Forja Heroes ||

Actividad UD3 de PHP: informe de una expedición con cálculos de héroes,
recorrido de salas, combate contra un jefe, inventario y mapa.

**Creador:** Manuel Capitas León

**Variante implementada:** A

## Ejecución local

Desde la carpeta del proyecto, inicia el servidor integrado de PHP:

```powershell
php -d display_errors=1 -d error_reporting=-1 -S localhost:8080 -t src
```

Abre <http://localhost:8080/expedicion.php>. Para detener el servidor, pulsa
`Ctrl+C` en la terminal.

Docker también puede usarse si el motor de Docker está iniciado y la carpeta
del proyecto está montada en un entorno LAMP.

## Uso de inteligencia artificial

Se utilizó un asistente de inteligencia artificial para analizar el enunciado,
implementar y revisar partes del PHP, comprobar los cálculos de la variante A,
explicar el uso de `match`, `switch`, `continue` y `continue 2`, y ayudar a
preparar esta memoria.

Un ejemplo de explicación fue: «`continue 2` sale del `switch` y pasa a la
siguiente iteración del `foreach` que lo contiene». Esta explicación se
contrastó con el comportamiento observado al hacer temporalmente el
experimento con `continue;`: PHP mostró el aviso de que, en ese `switch`,
`continue` equivale a `break`.

La IA sirvió de apoyo, pero sus respuestas y el código se comprobaron con el
enunciado y ejecutando la página en PHP. Es importante que quien entregue el
trabajo pueda explicar las decisiones y los bucles.

## Problemas encontrados y resolución

- **Docker no estaba disponible:** el motor de Docker no estaba iniciado.
  Como Docker no es imprescindible para probar esta actividad, se instaló PHP
  8.5.12 en el perfil local y se utilizó el servidor integrado de PHP.
- **Instalación de PHP con Chocolatey:** necesitaba permisos elevados. Para no
  modificar la instalación global ni depender de esos permisos, se descargó
  PHP desde la distribución oficial de Windows y se instaló para el usuario.
- **Dato de vida tras la expedición:** la primera comprobación detectó que el
  informe no mostraba explícitamente la vida al salir de las salas. Se añadió
  el dato al resumen y se comprobó el resultado de la variante A: `305/774`.
- **Captura del error de clase desconocida:** el error de PHP mostraba la ruta
  local del equipo. La captura se conserva localmente, pero se excluye del
  repositorio público mediante `.gitignore` para no divulgar esa ruta.
- **Capturas pendientes:** la imagen del editor disponible muestra el bucle
  `foreach` de cálculo, no el que genera las filas de la tabla. También falta
  guardar una captura válida de Ctrl+U centrada en esa tabla. Estas evidencias
  están señaladas en `MEMORIA.md`.
- **Panel de colaboradores de GitHub:** el historial publicado se reemplazó
  para que el único commit esté atribuido a Manuel Capitas León y no incluya
  una línea de coautoría. La lista lateral de colaboradores la calcula GitHub
  y puede tardar en actualizarse; no se controla desde el código del proyecto.

## Verificación

La página de la variante A se ejecutó con PHP 8.5.12 y respondió correctamente.
También se comprobó que los archivos PHP no tuvieran errores de sintaxis ni
avisos durante la ejecución. Los resultados principales coinciden con la
tabla de comprobación del enunciado; los detalles están en `MEMORIA.md`.
