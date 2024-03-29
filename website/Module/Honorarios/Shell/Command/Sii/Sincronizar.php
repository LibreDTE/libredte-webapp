<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace website\Honorarios;

/**
 * Comando para sincronizar datos del SII en LibreDTE
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2021-06-29
 */
class Shell_Command_Sii_Sincronizar extends \Shell_App
{

    public function main($grupo = 'dte_plus', $meses = 2, $sincronizar = 'all')
    {
        // recorrer contribuyentes y obtener datos
        $contribuyentes = $this->getContribuyentes($grupo);
        $Contribuyentes = new \website\Dte\Model_Contribuyentes();
        foreach ($contribuyentes as $rut) {
            // crear objeto del contribuyente
            $Contribuyente = $Contribuyentes->get($rut);
            // verificar que el contribuyente exista y tenga clave de SII
            if (!$Contribuyente->exists() or !$Contribuyente->config_sii_pass) {
                continue;
            }
            // sincronizar
            if ($this->verbose) {
                $this->out('Sincronizando datos de honorarios del SII de: '.$Contribuyente->razon_social);
            }
            try {
                if (in_array($sincronizar, ['all', 'bhe'])) {
                    (new Model_BoletaHonorarios())->setContribuyente($Contribuyente)->sincronizar($meses);
                }
                if (in_array($sincronizar, ['all', 'bte'])) {
                    (new Model_BoletaTerceros())->setContribuyente($Contribuyente)->sincronizar($meses);
                }
            } catch (\Exception $e) {
                if ($this->verbose) {
                    $this->out('  - '.$e->getMessage());
                }
            }
        }
        $this->showStats();
        return 0;
    }

    private function getContribuyentes($grupo = null)
    {
        if (is_numeric($grupo)) {
            return [$grupo];
        }
        $db = \sowerphp\core\Model_Datasource_Database::get();
        return $db->getCol('
            SELECT DISTINCT c.rut
            FROM
                contribuyente AS c
                JOIN contribuyente_config AS cc ON cc.contribuyente = c.rut AND cc.configuracion = \'sii\' AND cc.variable = \'pass\'
                JOIN usuario_grupo AS ug ON ug.usuario = c.usuario
                JOIN grupo AS g ON ug.grupo = g.id AND g.grupo = :grupo
            WHERE
                g.grupo IS NOT NULL
                AND cc.valor IS NOT NULL
        ', [':grupo' => $grupo]);
    }

}
