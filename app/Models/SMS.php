<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SMS extends Model
{
    static function ObtenerClientes($listaClientes){
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

        $sentencia = DB::table('herramientas.rep_herramientas_bitacora AS BitacoraT')
        ->leftJoin('herramientas.cat_herramientas_layouts AS LayoutsT',
        'LayoutsT.Cliente', '=', 'BitacoraT.Cliente')
        ->select(
            'BitacoraT.Cliente',
            'LayoutsT.LayoutNombre AS Nombre',
        )
        ->whereIn('BitacoraT.Cliente', $listaClientes)
        ->whereIn('EstadoEnvio', [7,8])
        ->whereRaw("LayoutsT.Herramienta = 'sms' AND Fecha between DATE_ADD(LAST_DAY(DATE_ADD(CURRENT_DATE(), INTERVAL -2 MONTH)), INTERVAL + 1 DAY) AND DATE_ADD(current_date(), INTERVAL -0 MONTH)")
        ->orderBy('Cliente')
        ->groupBy('BitacoraT.Cliente')
        ->get();
        return $sentencia;
    }
    static function obtenerDidGerente($claveGerente, $filterSQL){
        $sentencia = DB::table('herramientas.cat_recursos_dids')
        ->select('Did')
        ->whereRaw($filterSQL."ClaveGerente = ? 
        AND Servicio IN ('SMS', 'HERRAMIENTAS') ",[$claveGerente])
        ->orderBy('IdDid')
        ->get();

        return $sentencia;
    }
    static function obtenerWhatsAppGerente( $claveCliente , $claveGerente )
    {
     // Define la sentencia SQL.
     $sentencia = DB::table('data.NumerosWhatsApp')
     ->select('idwhatsapp', 'whatsapp')
     ->where([
        ['cliente',[$claveCliente]],
        ['Claves',[$claveGerente]],
        ['status',1]
        ])
        ->orderBy('idwhatsapp')
        ->get();
  
     // Ejecuta la sentencia.
     return ( $sentencia );
    }
    static function obtenerMascaraCliente( $claveCliente )
    {
        // Define la sentencia SQL.
        $sentencia=DB::table('herramientas.cat_mascaras_sms')
        ->select('IdCalixta','Mascara')
        ->where([
            ['Cliente',$claveCliente],
            ['EstadoActivacion',1]
        ])
        ->orderBy('IdMascara')
        ->get();

        // Ejecuta la sentencia.
        return $sentencia;
    }
    static function contarRegistrosBitacoraResultados( $listaClientes , $condicionSentencia )
    {
     // Define la sentencia SQL.
     $sentencia = DB::table('herramientas.rep_herramientas_bitacora AS BitacoraT')
     ->whereRaw("Fecha >= ADDDATE(LAST_DAY(SUBDATE(NOW(), INTERVAL 2 MONTH)), 1) AND Cliente IN ($listaClientes) $condicionSentencia")
     ->count();
     // Ejecuta la sentencia.
     return $sentencia;
    }
    static function construirBitacoraResultados( $listaClientes , $condicionSentencia , $comienzoFilas , $maximoFilas,$info )
    {
     // Define la sentencia SQL.
     $sentencia = DB::select("
     SELECT
      `IdRandom`,
      `Fecha`,
      `HoraInicio`,
      `HoraFin`,
      `ClaveUsuario`,
      BitacoraT.`Cliente`,
      BitacoraT.`ClaveGerente`,
      IF(`Flash` = 1, 'FLASH', 'SMS' ) AS Tipo,
      GesT.`secuencialCliente` AS IdPlantilla,
      BitacoraT.Comentario,
      FORMAT(`Registros`, 0) AS Registros,
      CASE
       WHEN `ResultadoTransaccion` = '0' THEN CONCAT('Error Proveedor')  
       ELSE `ResultadoTransaccion`
      END AS `Id Envio`,
      FORMAT(`Activos`, 0) AS Activos,
      FORMAT(`Efectivos`, 0) AS Efectivos,
      IF(`%Efectividad` = 0,
       CONCAT(
        '
         <div class=\"progress\">
          <div class=\"progress-bar bg-dark\" role=\"progressbar\" style=\"width: 100%\" aria-valuenow=\"100\" aria-valuemin=\"0\" aria-valuemax=\"100\">0%</div>
         </div>
        '
       ),
       CONCAT(
        '
         <div class=\"progress\">
          <div class=\"progress-bar bg-primary\" role=\"progressbar\" style=\"width:', `%Efectividad`,'%;\">',CAST(`%Efectividad` AS DECIMAL(16,0)),'%</div>
         </div>
        '
       )
      ) AS Efectividad,
      CASE
       WHEN `EstadoEnvio` = -1 THEN CONCAT('<span class=\"badge badge-danger\"><i class=\"fa fa-exclamation-triangle\"></i> Error</span>')
       WHEN `EstadoEnvio` = 0 THEN CONCAT('<span class=\"badge badge-dark\"><i class=\"fas fa-times mr-2\"></i>Cancelada</span>')
       WHEN `EstadoEnvio` = 1 THEN CONCAT('<span class=\"badge badge-secondary\"><i class=\"fas fa-clock mr-2\"></i>Programada</span>')   
       WHEN `EstadoEnvio` = 2 THEN CONCAT('<span class=\"badge badge-primary\"><i class=\"fas fa-spinner fa-spin mr-2\"></i>Procesando</span>')
       WHEN `EstadoEnvio` = 3 THEN CONCAT('<span class=\"badge badge-primary\"><i class=\"far fa-hourglass mr-2\"></i>En Progreso</span>')
       WHEN `EstadoEnvio` = 4 THEN CONCAT('<span class=\"badge badge-warning\"><i class=\"fas fa-paper-plane mr-2\"></i>Obteniendo Resultados</span>')
       WHEN `EstadoEnvio` = 5 THEN CONCAT('<span class=\"badge badge-warning\"><i class=\"fas fa-paper-plane mr-2\"></i>Con Resultados</span>')
       WHEN `EstadoEnvio` = 6 THEN CONCAT('<span class=\"badge badge-info\"><i class=\"fas fa-check-double mr-2\"></i>Generando Gescar</span>')
       WHEN `EstadoEnvio` = 7 THEN CONCAT('<span class=\"badge badge-info\"><i class=\"fas fa-check mr-2\"></i>Terminada</span>')
       WHEN `EstadoEnvio` = 8 THEN CONCAT('<span class=\"badge badge-info\"><i class=\"fas fa-check-double mr-2\"></i>Terminada</span>')
       WHEN `EstadoEnvio` = 9 THEN CONCAT('<span class=\"badge badge-warning\"><i class=\"fas fa-business-time mr-2\"></i>Proveedor</span>')
      END AS Estado,
      CONCAT(
       '<div>',$info'</div>'
      ) AS Acciones
     FROM
      herramientas.rep_herramientas_bitacora AS BitacoraT
      LEFT JOIN herramientas.PlantillaSMS AS GesT
       ON GesT.`idPlantilla` = BitacoraT.`IdPlantilla`
     WHERE
      `Fecha` >= ADDDATE(LAST_DAY(SUBDATE(NOW(), INTERVAL 3 MONTH)), 1)
      AND
       BitacoraT.`Cliente` IN ($listaClientes)
      $condicionSentencia
     ORDER BY
      BitacoraT.`Fecha` DESC,
      BitacoraT.`HoraInicio` DESC
     LIMIT
      $comienzoFilas, $maximoFilas
    ");
  
     // Ejecuta la sentencia.
     return $sentencia;
    }

    static function contarRegistrosBitacoraResumenEnvios( $listaClientes , $condicionSentencia )
    {
     // Define la sentencia SQL.
     $sentencia = DB::select("
     SELECT
      COUNT(*)
     FROM
      herramientas.rep_herramientas_bitacora
     WHERE
      `Fecha` >= ADDDATE(LAST_DAY(SUBDATE(NOW(), INTERVAL 2 MONTH)), 1)
      AND
       `Cliente` IN ($listaClientes)
      AND
       `EstadoEnvio` IN (5, 7, 8)
      $condicionSentencia
     GROUP BY
      `Cliente`,
      `ClaveGerente`,
      `ClaveSupervisor`,
      `Fecha`;
    ");
  
     // Ejecuta la sentencia.
     return $sentencia;
    }
    static function construirBitacoraResumenEnvios( $claveCliente , $condicionSentencia , $comienzoFilas , $maximoFilas )
    {
     // Define la sentencia SQL.
     $sentencia = DB::table('herramientas.rep_herramientas_bitacora')
     ->selectRaw("`Cliente`,`ClaveGerente`,`ClaveSupervisor`,`Fecha`, FORMAT(SUM(`Activos`), 0) AS Activos,FORMAT(SUM(`Efectivos`), 0) as Efectivos")
     ->whereRaw("`Fecha` >= ADDDATE(LAST_DAY(SUBDATE(NOW(), INTERVAL 2 MONTH)), 1) AND `Cliente` IN ($claveCliente) AND `EstadoEnvio` IN (5, 7, 8) $condicionSentencia")
     ->groupBy("Cliente","ClaveGerente","ClaveSupervisor","Fecha")
     ->orderBy("Cliente")
     ->orderBy("ClaveGerente")
     ->orderBy("ClaveSupervisor")
     ->orderBy("Fecha",'desc')
     ->offset($comienzoFilas)
     ->limit($maximoFilas)
     ->get();
  
     // Ejecuta la sentencia.
     return $sentencia;
    }
    static function construirBitacoraResumenPlantillas( $claveCliente )
    {
     // Define la sentencia SQL.
     $sentencia = DB::select("
     SELECT
     GesT.`secuencialCliente` as ges,
     -- GesT.`clave`,
     IF(BitacoraT.`Uso` IS NULL,
      CONCAT(
       '
        <div class=\"progress\">
         <div class=\"progress-bar bg-dark\" role=\"progressbar\" style=\"width: 100%\" aria-valuenow=\"100\" aria-valuemin=\"0\" aria-valuemax=\"100\">Sin uso</div>
        </div>
       '
      ),
      CONCAT(
       '
        <div class=\"progress\">
         <div class=\"progress-bar\" role=\"progressbar\" aria-valuemin=\"0\" style=\"width: ',BitacoraT.`Uso`,'%\">',BitacoraT.`Uso`,'</div>
        </div>
       '
      )
     ) AS Uso,
     CONCAT('<button class=\"btn btn-info btn-sm badge\" onclick=\"aperturaModalVisualizarGes(''',GesT.`idPlantilla`,''')\"><i class=\"fas fa-eye\"></i></button>') AS Acciones
    FROM
     herramientas.PlantillaSMS AS GesT
   INNER JOIN 
     core.Cliente as Cli
   ON
     Cli.idCliente = GesT.idCliente
    LEFT JOIN(
     SELECT
      `Cliente`,
      `IdPlantilla`,
      COUNT(*) AS Uso
     FROM
      herramientas.rep_herramientas_bitacora
     WHERE
      YEAR(`Fecha`) = YEAR(NOW())
       AND
        MONTH(`Fecha`) = MONTH(NOW())
       AND
        `Cliente` = '$claveCliente'
     GROUP BY
      `Cliente`, `IdPlantilla`
    ) AS BitacoraT
     ON BitacoraT.`Cliente` = Cli.`clave`
      AND BitacoraT.`IdPlantilla` = GesT.`idPlantilla`
    WHERE
     GesT.`estado` = 1
    AND
      Cli.`clave` = '$claveCliente'
    ORDER BY BitacoraT.Uso DESC
    ");
  
     // Ejecuta la sentencia.
     return $sentencia;
    }
    static function construirReporteBitacoraResultados( $listaClientes )
  {
   // Define la sentencia SQL.
   $sentencia = DB::select("
   SELECT
   `IdBitacora` AS `Id Bitacora`,
   `IdRandom` AS `Id Random`,
   `Fecha`,
   `HoraInicio` AS `Hora Inicio`,
   `HoraFin` AS `Hora Fin`,
   `ClaveUsuario` AS `Clave Usuario`,
   IF(BitacoraT.Flash = 1, 'FLASH','SMS') AS TIPO,
   BitacoraT.`Cliente`,
   BitacoraT.`ClaveGerente` AS `Clave Cliente`,
   GesT.`secuencialCliente`  AS `Id Plantilla`,
   `Registros`,
   `Activos`,
   `Inactivos`,
   `Efectivos`,
   `ResultadoTransaccion`  AS `Id Envio`,
   CASE
    WHEN `Distribuido` = 0 THEN 'No'
    WHEN `Distribuido` = 1 THEN 'Si'
   END AS Distribuido,
   CASE
    WHEN `EstadoEnvio` = 0 THEN 'Cancelada'
    WHEN `EstadoEnvio` = 1 THEN 'Programada'   
    WHEN `EstadoEnvio` = 2 THEN 'Procesando'
    WHEN `EstadoEnvio` = 3 THEN 'En Progreso'
    WHEN `EstadoEnvio` = 4 THEN 'Obteniendo Resultados'
    WHEN `EstadoEnvio` = 5 THEN 'Con Resultados'
    WHEN `EstadoEnvio` = 6 THEN 'Generando Gescar'
    WHEN `EstadoEnvio` = 7 THEN 'Con Gescar'
    WHEN `EstadoEnvio` = 8 THEN 'Sin Gescar'
   END AS `Estado Mensaje`
  FROM
   herramientas.rep_herramientas_bitacora AS BitacoraT
   LEFT JOIN herramientas.PlantillaSMS AS GesT
    ON GesT.`idPlantilla` = BitacoraT.`IdPlantilla`
  WHERE
   `Fecha` >= CASE WHEN BitacoraT.`Cliente` = '911' THEN DATE_ADD(CURRENT_DATE(), INTERVAL -60 DAY) ELSE ADDDATE(LAST_DAY(SUBDATE(NOW(), INTERVAL 2 MONTH)), 1) END
   AND
    BitacoraT.`Cliente` IN ($listaClientes)
  ORDER BY
   BitacoraT.`Fecha` DESC,
   BitacoraT.`HoraInicio` DESC
  ");

   // Ejecuta la sentencia.
   return $sentencia;
  }
}
