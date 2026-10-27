<?php
/**
 * Informe de expedición de Forja Heroes ||.
 *
 * @author Manuel Capitas León
 * @version UD3 · Variante A
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/reglas.php';
require_once __DIR__ . '/inc/datos-' . VARIANTE . '.php';

// Se calculan las estadísticas de cada héroe y se guardan para reutilizarlas en el informe.
foreach ($party as $i => $heroe) {
    $nivel = intdiv($heroe['xp'], XP_POR_NIVEL) + 1;
    $vidaMax = VIDA_BASE + $heroe['constitucion'] * $nivel * MULT_VIDA[$heroe['clase']];
    $pctVida = $heroe['vida'] / $vidaMax * 100;

    $danio = match ($heroe['clase']) {
        'Guerrera' => $heroe['fuerza'] ** 2 / 10 + $nivel * 2,
        'Hechicero' => $heroe['inteligencia'] * 3 + $nivel ** 2 / 4,
        'Pícara' => $heroe['destreza'] * 2 + $nivel ** 1.5,
        'Clérigo' => ($heroe['inteligencia'] + $heroe['constitucion']) * 1.5 + $nivel,
    };

    if ($heroe['vida'] <= 0) {
        $estado = 'K.O.';
    } elseif ($pctVida < 35) {
        $estado = 'En peligro';
    } elseif ($pctVida < 70) {
        $estado = 'Herido';
    } else {
        $estado = 'En pie';
    }

    switch ($heroe['clase']) {
        case 'Guerrera':
        case 'Clérigo':
            $posicion = 'Primera línea';
            break;
        case 'Pícara':
            $posicion = 'Flanco';
            break;
        case 'Hechicero':
            $posicion = 'Retaguardia';
            break;
        default:
            $posicion = 'Reserva';
    }

    $poder = (int) round($danio * $nivel);
    $bloquesLlenos = max(0, min(BLOQUES_BARRA, (int) round($pctVida / 100 * BLOQUES_BARRA)));
    $barra = '';
    for ($bloque = 0; $bloque < BLOQUES_BARRA; $bloque++) {
        $barra .= $bloque < $bloquesLlenos ? '█' : '░';
    }

    $party[$i]['nivel'] = $nivel;
    $party[$i]['vidaMax'] = $vidaMax;
    $party[$i]['pctVida'] = $pctVida;
    $party[$i]['danio'] = $danio;
    $party[$i]['poder'] = $poder;
    $party[$i]['estado'] = $estado;
    $party[$i]['posicion'] = $posicion;
    $party[$i]['barra'] = $barra;
}

// Los totales salen de las columnas de la matriz para no duplicar cálculos.
$vidaParty = array_sum(array_column($party, 'vida'));
$vidaMaxParty = array_sum(array_column($party, 'vidaMax'));
$poderParty = array_sum(array_column($party, 'poder'));
$nivelMedio = array_sum(array_column($party, 'nivel')) / count($party);

/* El segundo recorrido excluye a quienes ya no pueden participar en la expedición. */
// Los héroes K.O. no aportan daño; el líder es el activo con más poder.
$danioParty = 0;
$heroesActivos = 0;
$lider = null;
foreach ($party as $heroe) {
    if ($heroe['estado'] === 'K.O.') {
        continue;
    }

    $danioParty += $heroe['danio'];
    $heroesActivos++;
    if ($lider === null || $heroe['poder'] > $lider['poder']) {
        $lider = $heroe;
    }
}

# Las comprobaciones estrictas evitan confundir nombres de clase similares.
$clasesParty = array_column($party, 'clase');
$hayPicara = in_array('Pícara', $clasesParty, true);
$hayClerigo = in_array('Clérigo', $clasesParty, true);

// Cada petición empieza desde los datos originales: el diario y el oro no persisten.
$oro = $oroInicial;
$xpGanada = 0;
$diario = [];
$salasExploradas = 0;
foreach ($salas as $n => $sala) {
    $salasExploradas++;

    switch ($sala['tipo']) {
        case 'vacía':
            $diario[] = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': vacía, la party sigue avanzando.';
            continue 2;

        case 'trampa':
            if ($hayPicara) {
                $diario[] = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': ¡trampa evitada por la Pícara!';
            } else {
                $vidaParty -= $sala['valor'];
                $diario[] = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': ¡trampa! -' . $sala['valor'] . ' de vida.';
            }
            break;

        case 'combate':
            $danioRecibido = intdiv($sala['valor'], 4);
            $vidaParty -= $danioRecibido;
            $xpGanada += $sala['valor'];
            $diario[] = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': combate, -' . $danioRecibido
                . ' de vida y +' . $sala['valor'] . ' XP.';
            break;

        case 'tesoro':
            $oro += $sala['valor'];
            $mensajeTesoro = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': +' . $sala['valor'] . ' mo';
            if (array_key_exists('objeto', $sala)) {
                $objeto = $sala['objeto'];
                $inventario[$objeto] = ($inventario[$objeto] ?? 0) + 1;
                $mensajeTesoro .= ' y «' . $objeto . '»';
            }
            $diario[] = $mensajeTesoro . '.';
            break;

        case 'descanso':
            $curacion = $sala['valor'] * ($hayClerigo ? 2 : 1);
            $vidaAntesDeCurar = $vidaParty;
            $vidaParty = min($vidaMaxParty, $vidaParty + $curacion);
            $vidaCurada = $vidaParty - $vidaAntesDeCurar;
            $detalleDescanso = $hayClerigo ? ' (bendición del Clérigo)' : '';
            $diario[] = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': descanso, +' . $vidaCurada
                . ' de vida' . $detalleDescanso . '.';
            break;
    }

    if ($vidaParty <= 0) {
        $vidaParty = 0;
        $diario[] = 'La party se ha quedado sin vida y no puede continuar.';
        break;
    }
}

$vidaTrasSalas = $vidaParty;
$salasPendientes = count($salas) - $salasExploradas;

// La puerta y el combate se resuelven después de explorar, usando la llave encontrada.
$tieneLlave = array_key_exists($jefe['llave'], $inventario);
$golpe = (int) round($danioParty);
$rondas = [];
$ronda = 0;
$vidaJefe = $jefe['vida'];
$motivo = '';

if ($vidaParty <= 0) {
    $resultado = 'Derrota';
    $motivo = 'La party no llegó a la guarida del jefe.';
} elseif (!$tieneLlave) {
    $resultado = 'Puerta sellada';
    $motivo = 'La party no encontró ' . $jefe['llave'] . '.';
} else {
    while ($vidaJefe > 0 && $vidaParty > 0 && $ronda < MAX_RONDAS) {
        $ronda++;
        $vidaJefe = max(0, $vidaJefe - max(0, $golpe));
        if ($vidaJefe > 0) {
            $vidaParty = max(0, $vidaParty - max(0, $jefe['ataque']));
        }
        $rondas[] = [
            'ronda' => $ronda,
            'vidaJefe' => $vidaJefe,
            'vidaParty' => $vidaParty,
        ];
    }

    $resultado = match (true) {
        $vidaJefe <= 0 => 'Victoria',
        $vidaParty <= 0 => 'Derrota',
        default => 'Retirada',
    };

    if ($resultado === 'Victoria') {
        $oro += $jefe['recompensa'];
        $motivo = 'El jefe cae en la ronda ' . $ronda . '.';
    } elseif ($resultado === 'Derrota') {
        $motivo = 'La party ha caído durante el combate.';
    } else {
        $motivo = 'El combate alcanza el límite de ' . MAX_RONDAS . ' rondas.';
    }
}

// El inventario se valora tras el combate para incluir cualquier objeto encontrado.
$valorInventario = 0;
foreach ($inventario as $objeto => $cantidad) {
    $valorInventario += $cantidad * $precios[$objeto];
}
$totalObjetos = array_sum($inventario);
$inventarioOrdenado = $inventario;
arsort($inventarioOrdenado);
$objetoMasAbundante = $inventarioOrdenado === []
    ? 'Sin objetos'
    : (string) array_key_first($inventarioOrdenado);

// El recorrido por filas y columnas cuenta las celdas que se resumen en la leyenda.
$conteoCeldas = [];
for ($fila = 0; $fila < count($mapa); $fila++) {
    for ($columna = 0; $columna < count($mapa[$fila]); $columna++) {
        $celda = $mapa[$fila][$columna];
        $conteoCeldas[$celda] = ($conteoCeldas[$celda] ?? 0) + 1;
    }
}
$mapaCoherente = ($conteoCeldas['S'] ?? 0) === count($salas);

// La barra final representa la vida de toda la party en la misma escala de veinte bloques.
$bloquesParty = max(0, min(BLOQUES_BARRA, (int) round($vidaParty / $vidaMaxParty * BLOQUES_BARRA)));
$barraParty = '';
for ($bloque = 0; $bloque < BLOQUES_BARRA; $bloque++) {
    $barraParty .= $bloque < $bloquesParty ? '█' : '░';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expedición · <?= htmlspecialchars($nombreMazmorra, ENT_QUOTES, 'UTF-8') ?> · Forja Heroes ||</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<main>
    <span class="insignia">VARIANTE <?= htmlspecialchars(VARIANTE, ENT_QUOTES, 'UTF-8') ?></span>
    <h1><?= htmlspecialchars($nombreMazmorra, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="subtitulo">Informe de expedición · líder:
        <?= htmlspecialchars($lider['nombre'] ?? 'Sin líder', ENT_QUOTES, 'UTF-8') ?></p>

    <h2>La party</h2>
    <table>
        <tr><th>Héroe</th><th>Clase</th><th>Nivel</th><th>Vida</th><th>Daño</th><th>Poder</th><th>Posición</th><th>Estado</th></tr>
        <?php foreach ($party as $heroe): ?>
            <tr>
                <td><?= htmlspecialchars($heroe['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($heroe['clase'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $heroe['nivel'] ?></td>
                <td><span class="barra mini"><?= $heroe['barra'] ?></span> <?= $heroe['vida'] ?>/<?= $heroe['vidaMax'] ?></td>
                <td><?= number_format($heroe['danio'], 1, ',', '.') ?></td>
                <td><?= $heroe['poder'] ?></td>
                <td><?= htmlspecialchars($heroe['posicion'], ENT_QUOTES, 'UTF-8') ?></td>
                <?php if ($heroe['estado'] === 'K.O.' || $heroe['estado'] === 'En peligro'): ?>
                    <td class="peligro"><?= htmlspecialchars($heroe['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                <?php else: ?>
                    <td class="vida"><?= htmlspecialchars($heroe['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="rejilla">
        <div class="stat"><div class="etq">Poder total</div><div class="val"><?= $poderParty ?></div></div>
        <div class="stat"><div class="etq">Nivel medio</div><div class="val"><?= number_format($nivelMedio, 1, ',', '.') ?></div></div>
        <div class="stat"><div class="etq">Daño por ronda</div><div class="val"><?= number_format($danioParty, 1, ',', '.') ?></div></div>
        <div class="stat"><div class="etq">Héroes activos</div><div class="val"><?= $heroesActivos ?> / <?= count($party) ?></div></div>
    </div>

    <h2>Diario de la expedición</h2>
    <ol class="diario">
        <?php foreach ($diario as $linea): ?>
            <li><?= htmlspecialchars($linea, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ol>
    <p>Salas exploradas: <?= $salasExploradas ?> de <?= count($salas) ?>
        <?php if ($salasPendientes > 0): ?> · <span class="peligro"><?= $salasPendientes ?> pendientes</span><?php endif; ?>
        · XP ganada: <?= $xpGanada ?> · Vida al salir de las salas: <?= $vidaTrasSalas ?>/<?= $vidaMaxParty ?></p>

    <h2>El jefe: <?= htmlspecialchars($jefe['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Vida <?= $jefe['vida'] ?> · ataque <?= $jefe['ataque'] ?> · golpe de la party: <?= $golpe ?> por ronda</p>
    <?php if ($rondas !== []): ?>
        <table>
            <tr><th>Ronda</th><th>Vida del jefe</th><th>Vida de la party</th></tr>
            <?php foreach ($rondas as $datosRonda): ?>
                <tr>
                    <td><?= $datosRonda['ronda'] ?></td>
                    <td><?= $datosRonda['vidaJefe'] ?></td>
                    <td><?= $datosRonda['vidaParty'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <p class="resultado <?= $resultado === 'Victoria' ? 'vida' : 'peligro' ?>">
        <strong><?= htmlspecialchars($resultado, ENT_QUOTES, 'UTF-8') ?></strong> ·
        <?= htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <p class="barra vida">PARTY <?= $barraParty ?> <?= $vidaParty ?>/<?= $vidaMaxParty ?></p>

    <h2>Inventario</h2>
    <table>
        <tr><th>Objeto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        <?php foreach ($inventarioOrdenado as $objeto => $cantidad): ?>
            <tr>
                <td><?= htmlspecialchars($objeto, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $cantidad ?></td>
                <td><?= number_format($precios[$objeto], 2, ',', '.') ?> mo</td>
                <td><?= number_format($cantidad * $precios[$objeto], 2, ',', '.') ?> mo</td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div class="rejilla">
        <div class="stat"><div class="etq">Objetos</div><div class="val"><?= $totalObjetos ?></div></div>
        <div class="stat"><div class="etq">Valor del inventario</div><div class="val"><?= number_format($valorInventario, 2, ',', '.') ?> mo</div></div>
        <div class="stat"><div class="etq">Oro final</div><div class="val"><?= number_format($oro, 2, ',', '.') ?> mo</div></div>
        <div class="stat"><div class="etq">Más abundante</div><div class="val peq"><?= htmlspecialchars($objetoMasAbundante, ENT_QUOTES, 'UTF-8') ?></div></div>
    </div>

    <h2>Mapa de la mazmorra</h2>
    <table class="mapa">
        <?php foreach ($mapa as $filaMapa): ?>
            <tr>
                <?php foreach ($filaMapa as $celda): ?>
                    <td class="<?= htmlspecialchars(LEYENDA_MAPA[$celda][0] ?? 'desconocida', ENT_QUOTES, 'UTF-8') ?>"
                        title="<?= htmlspecialchars(LEYENDA_MAPA[$celda][1] ?? 'Desconocida', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($celda, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>Leyenda:
        <?php foreach ($conteoCeldas as $caracter => $cantidadCelda): ?>
            <?= htmlspecialchars(LEYENDA_MAPA[$caracter][1] ?? 'Desconocida', ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars($caracter, ENT_QUOTES, 'UTF-8') ?>) × <?= $cantidadCelda ?> ·
        <?php endforeach; ?>
    </p>
    <?php if ($mapaCoherente): ?>
        <p class="vida">El mapa es coherente: hay una sala marcada por cada sala del recorrido.</p>
    <?php else: ?>
        <p class="peligro">Aviso: el mapa muestra <?= $conteoCeldas['S'] ?? 0 ?> salas, pero el recorrido contiene <?= count($salas) ?>.</p>
    <?php endif; ?>

    <footer>Informe generado el <?= date('d/m/Y H:i:s') ?> con PHP <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?> · cada recarga lo recalcula todo desde cero.</footer>
</main>
</body>
</html>
