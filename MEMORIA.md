# Memoria · Actividad «Forja Heroes ||»

**Creador:** magit · **Alumno/a:** Manuel Capitas León · **Variante:** A · **Fecha:** 27/10/2026

> La variante A se ha usado porque es la que viene seleccionada en `src/inc/reglas.php`. Si el aula virtual asignó otra, cambia `VARIANTE` y vuelve a comprobar los resultados antes de entregar.

## 1. Comprobación de resultados

He ejecutado la página con PHP 8.5.12 en el servidor local de PHP. Respondió con HTTP 200, sin `Warning`, `Notice`, `Deprecated` ni errores fatales. También comprobé la sintaxis de todos los archivos PHP. No he guardado capturas de esa ejecución.

| Dato | Valor esperado (enunciado) | Valor de mi página | ¿Coincide? |
|---|---:|---:|---|
| Poder total de la party | 1243 | 1243 | Sí, comprobado |
| Daño por ronda | 205,6 | 205,6 | Sí, comprobado |
| Líder | Aldric | Aldric | Sí, comprobado |
| Vida al salir de las salas | 305 de 774 | 305 de 774 | Sí, comprobado |
| Resultado frente al jefe (y ronda) | Victoria en la ronda 5 | Victoria en la ronda 5 | Sí, comprobado |
| Oro final | 2.260,75 mo | 2.260,75 mo | Sí, comprobado |
| Valor del inventario | 151,20 mo | 151,20 mo | Sí, comprobado |
| Salas en el mapa | 7 | 7 | Sí, comprobado |

La party empieza la expedición con 355 puntos de vida. El combate de la sala de las ratas resta 50; el descanso cura 80 gracias al Clérigo; el combate de los esqueletos resta otros 80. Por eso sale de las salas con 305 puntos. El jefe recibe golpes de 206 y cae en cinco rondas.

## 2. Bucles y documento resultante

La imagen `capturas/foreach-calculos-party.png` muestra el `foreach` de cálculo de los héroes en el editor. No es el `foreach` que pinta la tabla; si se exige específicamente ese bucle de presentación, falta capturarlo también. También está pendiente guardar una captura de Ctrl+U centrada en la tabla de la party. La captura de la página generada está en `capturas/pagina-variante-a.png`.

- En el código fuente hay una sola fila `<tr>` de datos dentro del `foreach`; el navegador la genera cuatro veces, una por héroe. Sumada a la fila de encabezados, en el Ctrl+U aparecen cinco filas `<tr>` en la tabla de la party.
- En el Ctrl+U no aparecen `endforeach` ni los bloques `if`: PHP los ejecuta en el servidor y envía al navegador únicamente el HTML resultante.

## 3. Experimento: predigo el efecto de un cambio

**Predicción antes de recargar:** al añadir al principio de `$salas` la sala de combate de valor 2400, la party perderá `intdiv(2400, 4) = 600` puntos de vida. Como entra con 355, quedará sin vida en esa primera sala. Se explorará 1 sala de 8, se ganarán 2400 XP y el jefe dará como resultado «Derrota» porque la party no llegó a la guarida. No habrá tabla de rondas. El mapa avisará de que contiene 7 salas marcadas, mientras que los datos contienen 8 salas.

**Resultado real:** comprobé que se explora 1 sala de 8, se ganan 2400 XP, la party queda sin vida, no hay combate contra el jefe y el mapa avisa de que contiene 7 salas mientras el recorrido tiene 8. La captura está en `capturas/experimento-derrumbe.png`.

La sentencia `break` situada después de comprobar la vida corta el `foreach` de salas. La tabla de rondas no se muestra porque no hubo combate contra el jefe y, por tanto, `$rondas` queda vacío.

## 4. Experimento: match frente a switch

Al cambiar temporalmente la clase de un héroe a `'Bardo'`, el `match` de daño produce un `UnhandledMatchError`: no hay una rama para esa clase y, deliberadamente, no existe `default`. El cálculo ocurre antes de generar el HTML, por lo que la página se interrumpe antes de llegar al `switch` de posición.

Si se evaluara solo el `switch`, al no coincidir con ninguna clase conocida ejecutaría `default` y asignaría la posición «Reserva». El `switch` sí permite ese caso alternativo; el `match` exige una rama coincidente.

**Captura del error:** `capturas/experimento-bardo-error.png`. El experimento se ejecutó y después se restauró la clase original. Esa captura muestra la ruta local de Windows, así que se conserva en la carpeta local, pero está excluida del repositorio público mediante `.gitignore`.

En un programa real prefiero validar explícitamente los datos de entrada y mostrar un error claro para una clase desconocida. Un `default` silencioso puede ocultar datos incorrectos; un `match` sin rama, por otro lado, deja ver inmediatamente que falta un caso.

## 5. Experimento: continue dentro de switch

Al sustituir `continue 2;` por `continue;` en el caso `'vacía'`, PHP muestra el aviso:

> Warning: "continue" targeting switch is equivalent to "break". Did you mean to use "continue 2"?

`continue 2` salta el `switch` y pasa directamente a la siguiente iteración del `foreach` de salas. `continue` afecta al `switch` como un `break`: sale del `switch` y ejecuta el código que viene después, incluida la comprobación de vida de la party, antes de que el `foreach` avance normalmente a la siguiente iteración. Con los datos originales de la variante A, el diario y el recuento final de salas no cambian.

**Captura del aviso:** `capturas/experimento-continue-warning.png`. El experimento se ejecutó y se restauró `continue 2;`.

## 6. Uso de IA (obligatorio declararlo)

Sí. He usado un asistente de IA para interpretar el enunciado, completar los cálculos y explicar el comportamiento de `continue` dentro de un `switch`.

Respuesta del asistente: «`continue 2` salta a la siguiente iteración del `foreach` al salir también del `switch`.»

La respuesta es correcta: el nivel `2` indica que se continúa el segundo nivel de control anidado, que en este caso es el bucle `foreach`. Con `continue;`, PHP solo sale del `switch` y muestra el aviso indicado.

---

**Antes de entregar:** indicar el nombre del alumno/a, confirmar la variante asignada, obtener la captura del `foreach` que genera las filas de la tabla y la captura de Ctrl+U. Añadir también la rúbrica de autoevaluación si se ha facilitado por separado; no venía en los archivos del kit.
