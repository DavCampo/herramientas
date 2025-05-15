<?php

namespace App\Http\Controllers\Servicios;

use App\Models\SMS;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioSMS extends Controller
{
    public function ObtenerClientes(){
        //$clientesDisponibles = session()->get('clientes');
        $clientesDisponibles = ['017','324'];

        // Recupera la información para la construcción del selector.
        $datosClientes = SMS::obtenerClientes($clientesDisponibles);
        
        foreach($datosClientes as $registro){
            $arregloDatosClientes['data'][]=$registro;
        };
        // Salida de la información.
        echo json_encode($arregloDatosClientes);
    }
    public function obtenerDidGerente(){
        // Asigna a las variables los datos recibidos en la petición.
        //$claveCliente = session()->get('claveCliente');
        //$claveGerente = session()->get('claveGerente');
        $claveCliente = 486;
        $claveGerente = 'AAABB';
        
        $complementarySQLFilter = Helpers::getDidsWhenManagerHaveMoreClients($claveCliente);
        $rsPeticion = SMS::obtenerDidGerente($claveGerente, $complementarySQLFilter);
        foreach ($rsPeticion as $registro) {
            $arregloRespuesta['data'][]=$registro;
        };
        // Salida de la información.
        echo json_encode($arregloRespuesta);
    }
    public function obtenerWhatsAppGerente(){
        //$claveCliente = session()->get('claveCliente');
        //$claveGerente = session()->get('claveGerente');
        $claveCliente = 486;
        $claveGerente = 'AAABB';
        // Recupera la información para la construcción del selector.
        $rsPeticion = SMS::obtenerWhatsAppGerente(
        $claveCliente, $claveGerente
        );

        // Recorre cada registro del indicador de voz recuperado.
        foreach ($rsPeticion as $registro) {
        // Agrega el registro al arreglo.
        $arregloRespuesta['data'][] = $registro;
        }

        // Salida de la información.
        echo json_encode($arregloRespuesta);
    }
    public function obtenerMascaraCliente()
    {
        // Asigna a las variables los datos recibidos en la petición.
        //$claveCliente = session()->get('claveCliente');
        //$claveGerente = session()->get('claveGerente');
        $claveCliente = 486;
        $claveGerente = 'AAABB';
        // Recupera la información para la construcción del selector.
        $rsPeticion = SMS::obtenerMascaraCliente($claveCliente);

        if( $claveCliente == '025')
        {
            if($claveGerente == 'AAIPX')
            {
                $arregloRespuesta['data'][] = [
                    'IdCalixta' => '803',
                    'Mascara' => 'OPORTUNIDAD'
                ];
            }
            else
            {
                $arregloRespuesta['data'][] = [
                    'IdCalixta' => '681',
                    'Mascara' => 'SERTEC'
                ];
            };
        }
        else
        {
            foreach ($rsPeticion as $registro)
                $arregloRespuesta['data'][] = $registro;
        }
        // Salida de la información.
        echo json_encode($arregloRespuesta, JSON_PRETTY_PRINT);
    }
    public function construirBitacoraResultadosSS(Request $request) {
        $request->validate([
            'draw'=>'required',
            'start'=>'required|integer',
            'length'=>'required|integer',
            'search'=>'nullable'
        ]);
        // Recupera los valores de configuración enviados por DataTable
        // para el proceso de ServerSide.
        $draw = $request->draw;
        $comienzoFilas = $request->start;
        $maximoFilas = $request->length;
        $valorFiltroBusqueda = $request->search;
      
        // Inicializa el string que almacenará la sentencia SQL de condición.
        $stringSentenciaSQL = '';
      
        // Valida si existe un filtro desde el inicio de la carga de la UI.
        if ($valorFiltroBusqueda != '') {
         // Agrega al string de la sentencia el nombre de las columnas por las que
         // podrán filtrarse los datos.
         $stringSentenciaSQL = "" .
          "AND (" .
          "IdRandom LIKE '%" . $valorFiltroBusqueda . "%' OR " .
          "Fecha LIKE '%" . $valorFiltroBusqueda . "%' OR " .
          "ClaveUsuario LIKE '%" . $valorFiltroBusqueda . "%' OR " .
          "BitacoraT.Cliente LIKE '%" . $valorFiltroBusqueda . "%' OR " .
          "ClaveGerente LIKE '%" . $valorFiltroBusqueda . "%' OR " .
          "BitacoraT.IdPlantilla LIKE '%" . $valorFiltroBusqueda . "%'" .
          ")
         ";
        };
      
        // Asigna a la variable los clientes a los cuales pertenece
        // el usuario con sesión activa.
        //$clientesDisponibles = session()->get('clientes');
        $clientesDisponibles = ['017','324'];
        $listacliente="";
        foreach($clientesDisponibles as $cliente){
            $listacliente.="'".$cliente."',";
        };
        $listacliente=substr($listacliente,0,-1);
        // Recupera el total de registros de la bitácora de resultados.
        $registros = SMS::contarRegistrosBitacoraResultados($listacliente, '');
         // Recupera el total de registros de la bitácora de resultados
        // que cumplen con los filtros especificados.
        $totalConFiltro = SMS::contarRegistrosBitacoraResultados(
         $listacliente, $stringSentenciaSQL);
        if(session()->get('nivel')!=25){
            $info = "-- Información mensaje.
            '<a class=\"mx-1 btn btn-info btn-sm badge\" href=\"../storage/bases/sms/csv_envios/',IdRandom,'.csv\" role=\"button\"><i class=\"fas fa-info\"></i></a>',
            CASE
             -- Cancelar campaña.
             WHEN `EstadoEnvio` = 1 THEN CONCAT('<button id=\"',`IdRandom`,'\" class=\"mx-1 btn btn-danger btn-sm badge\" onclick=\"cancelarEnvioMensaje(''',`IdRandom`,'\',\'',BitacoraT.Cliente,''')\"><i class=\"fas fa-times\"></i></button>')
             -- Resultados campaña.
             WHEN ResultadoTransaccion = IdRandom THEN CONCAT('<a class=\"mx-1 btn btn-secondary btn-sm badge\" href=\"ServicioSMS/resultados_csv_directo/',`IdRandom`,'/',BitacoraT.`Cliente`,'\" role=\"button\"><i class=\"fas fa-file-download\"></i></a>')
             WHEN ResultadoTransaccion !='' AND BitacoraT.`Cliente` = '521'  THEN CONCAT('<a class=\"mx-1 btn btn-secondary btn-sm badge\" href=\"ServicioSMS/resultados_csv_nu/',`IdRandom`,'/',BitacoraT.`Cliente`,'\" role=\"button\"><i class=\"fas fa-file-download\"></i></a>')
             WHEN `ResultadoTransaccion` != '' THEN CONCAT('<a class=\"mx-1 btn btn-secondary btn-sm badge\" href=\"ServicioSMS/resultados_csv/',`ResultadoTransaccion`,'/',BitacoraT.`Cliente`,'\" role=\"button\"><i class=\"fas fa-file-download\"></i></a>')
            END,";
        }else{
            $info="";
        }
        // Recupera los registros de la bitácora de resultados.
        $registrosBitacora = SMS::construirBitacoraResultados(
         $listacliente, $stringSentenciaSQL, $comienzoFilas, $maximoFilas,$info);
        
      
        // Verifica la cantidad de resultados recuperados para asignar
        // el contenido del contedor de datos.
        if (count($registrosBitacora) >= 1) {
            foreach($registrosBitacora as $comienzoFilas){
                $data[] = $comienzoFilas;
            };
        } else {
         $data = [];
        }
      
        // Construye el arreglo de respuesta.
        $arregloDatosBitacora = [
         'draw' => intval($draw),
         'iTotalRecords' => $registros,
         'iTotalDisplayRecords' => $totalConFiltro,
         'aaData' => $data
        ];
      
        // Da salida a la información de respuesta.
        echo json_encode($arregloDatosBitacora);
    }
 /** Interacciones con la bitácora de presupuestos. */
//##########################################################################
//#Se documenta codigo por la implementacion del modulo de presupuestos    #
//##########################################################################

    public function construirBitacoraResumenEnvios(Request $request) {
        // Recupera los valores de configuración enviados por DataTable
        // para el proceso de ServerSide.
        $request->validate([
            'draw'=>'required',
            'start'=>'required|integer',
            'length'=>'required|integer',
            'search'=>'nullable'
        ]);
        $draw = $request->draw;
        $comienzoFilas = $request->start;
        $maximoFilas = $request->length;
        $valorFiltroBusqueda = $request->search;
        
        // Inicializa el string que almacenará la sentencia SQL de condición.
        // Inicializa el string que almacenará la sentencia SQL de condición.
        $stringSentenciaSQL = '';

        // Valida si existe un filtro desde el inicio de la carga de la UI.
        if ($valorFiltroBusqueda != '') {
        // Agrega al string de la sentencia el nombre de las columnas por las que
        // podrán filtrarse los datos.
        $stringSentenciaSQL = "" .
            "AND (" .
            "Cliente LIKE '%" . $valorFiltroBusqueda . "%' OR " .
            "ClaveGerente LIKE '%" . $valorFiltroBusqueda . "%' OR " .
            "ClaveSupervisor LIKE '%" . $valorFiltroBusqueda . "%'" .
            ")
        ";
        }
    
        // Asigna a la variable el cliente seleccionado.
        $clientesDisponibles = $request->claveCliente === "'1'"
         ? session()->get('clientes')
        // : $request->claveCliente;
         : "'017','324'";
        
        // Recupera el total de registros de la bitácora de resultados.
        $registros = SMS::contarRegistrosBitacoraResumenEnvios(
         $clientesDisponibles, '');
        // Recupera el total de registros de la bitácora de resultados
        // que cumplen con los filtros especificados.
        $totalConFiltro = SMS::contarRegistrosBitacoraResumenEnvios(
         $clientesDisponibles, $stringSentenciaSQL);

        // Recupera los registros de la bitácora de resultados.
        $registrosBitacora = SMS::construirBitacoraResumenEnvios(
         $clientesDisponibles, $stringSentenciaSQL, $comienzoFilas, $maximoFilas);
        
        // Verifica la cantidad de resultados recuperados para asignar
        // el contenido del contedor de datos.
        if (count($registrosBitacora) >= 1) {
         foreach ($registrosBitacora as $comienzoFilas) {
          $data[] = $comienzoFilas;
         }
        } else {
         $data = [];
        }
    
        // Construye el arreglo de respuesta.
        $arregloDatosResumenMensajes = [
         'draw' => intval($draw),
         'iTotalRecords' => $registros,
         'iTotalDisplayRecords' => $totalConFiltro,
         'aaData' => $data
        ];
    
        // Salida de la información.
        echo json_encode($arregloDatosResumenMensajes);
   }
   public function construirBitacoraResumenPlantillas(Request $request) {
    $request->validate([
        'claveCliente'=>'required'
    ]);
    // Asigna a la variable el cliente seleccionado.
    $claveCliente = $request->claveCliente;
  
    // Recupera la información para la construcción de la bitácora.
    $datosResumenGes = SMS::construirBitacoraResumenPlantillas(
     $claveCliente
    );
  
    // Inicializa el arreglo contenedor de los datos.
    //$arregloDatosResumenGes['data'] = '';
  
    // Recorre cada registro del resumen recuperado.
    foreach ($datosResumenGes as $registro) {
     // Agrega el registro al arreglo.
     $arregloDatosResumenGes['data'][] = $registro;
    };
  
    // Salida de la información.
    echo json_encode($arregloDatosResumenGes);
   }
   public function construirReporteBitacoraResultados() {
        // Asigna a las variables los valores necesarios para la descarga del archivo.
        $contentType = 'application/vnd.sm-excel';
        $nombreArchivo = 'Bitacora_Servicio_SMS_Resultados.csv';
        $tipoArchivo = 'bitacoraResultados';

        // Asigna a la variable los clientes a los cuales pertenece
        // el usuario con sesión activa.
        $clientesDisponibles = session()->get('clientes');
    
        // Recupera los registros de la bitácora de resultados.
        $registrosBitacora = SMS::construirReporteBitacoraResultados(
         $clientesDisponibles
        );
        $registroBit= $registrosBitacora[0];
    
        // Recupera el nombre de las columnas recuperadas.
        $columnasTabla = count($registroBit->getAttributes());
        
        // Inicializa el arreglo de columnas.
        $arrayColumnas = [];
        return $columnasTabla;
        // Recorre cada nombre de la columna de la tabla y lo agrega al arreglo.
        foreach ($columnasTabla as $columna) {
         $arrayColumnas[] = $columna->name;
        }
    
        // Transforma el arreglo al tipo necesario.
        $stringColumnas = implode(',', $arrayColumnas);
    
        // Inicializa el arreglo de las columnas del reporte.
        $columnasCSV[] = $stringColumnas . "\n";
    
        // Recorre cada registro obtenido.
        foreach ($registrosBitacora as $registro) {
         $columnasCSV[] = (implode(',', $registro) . "\n");
        }
    
        // Instancia al método de la clase para descargar el archivo.
        $this->descargarArchivos(
         $contentType, $nombreArchivo, $columnasCSV, $tipoArchivo
        );
   }
   /**
  * Genera el reporte de la bitácora de resultados.
  */
 public function construirReporteBitacoraResumenEnvios($claveCliente) {
    // Asigna a las variables los valores necesarios para la descarga del archivo.
    $contentType = 'application/vnd.sm-excel';
    $nombreArchivo = 'Bitacora_Servicio_SMS_Resumen_Envios.csv';
    $tipoArchivo = 'bitacoraResumenEnvios';
  
    // Asigna a la variable los clientes a los cuales pertenece
    // el usuario con sesión activa.
    $clientesDisponibles = session()->get('clientes');
  
    // Asigna a la variable el cliente seleccionado.
    $clientesDisponibles = $claveCliente === "1"
     ? session()->get('clientes')
     : "'017','324'";
     //: $claveCliente;
  
    // Recupera los registros de la bitácora de resultados.
    $registrosBitacora = SMS::construirReporteBitacoraResumenEnvios(
     $clientesDisponibles
    );
    $registroBit= $registrosBitacora[0];
    
    // Recupera el nombre de las columnas recuperadas.
    $columnasTabla = $registroBit->getAttributes();
    return $columnasTabla;
    // Inicializa el arreglo de columnas.
    $arrayColumnas = [];
  
    // Recorre cada nombre de la columna de la tabla y lo agrega al arreglo.
    foreach ($columnasTabla as $columna) {
     $arrayColumnas[] = $columna->name;
    }
  
    // Transforma el arreglo al tipo necesario.
    $stringColumnas = implode(',', $arrayColumnas);
  
    // Inicializa el arreglo de las columnas del reporte.
    $columnasCSV[] = $stringColumnas . "\n";
  
    // Recorre cada registro obtenido.
    while ($registro = $registrosBitacora->fetch_assoc()) {
     $columnasCSV[] = (implode(',', $registro) . "\n");
    }
  
    // Instancia al método de la clase para descargar el archivo.
    $this->descargarArchivos(
     $contentType, $nombreArchivo, $columnasCSV, $tipoArchivo
    );
   }
  
   /**
    * Realiza la salida (descarga) de un archivo.
    *
    * @param string $contentType      Especifica la naturaleza de los datos.
    * @param string $nombreArchivo    Define el nombre que tendrá el archivo.
    * @param string $contenidoArchivo Contenido del documento.
    * @param string $tipoArchivo      Especifica el recurso que se descargará.
    *
    * @return void
    */
   public function descargarArchivos($contentType, $nombreArchivo, $contenidoArchivo, $tipoArchivo) {
    // Configura los encabezados.
    header('Content-Type: ' . $contentType);
    header(
     'Content-Disposition: attachment; filename="' . $nombreArchivo . '"'
    );
  
    // Limpia el buffer de salida.
    ob_clean();
  
    // Determina las acciones para generar la salida del archivo de acuerdo a su tipo.
    switch ($tipoArchivo) {
     case 'layout':
      echo $contenidoArchivo;
  
      break;
     case 'archivoErrores':
     case 'reporteCampania':
     case 'bitacoraResultados':
     case 'bitacoraResumenEnvios':
      // Recorre cada fila.
      for ($fila = 0; $fila < count($contenidoArchivo); $fila++) {
       echo $contenidoArchivo[$fila];
      }
  
      break;
     default:
      echo 'No ha sido posible generar el archivo. Contactar al área de M.I.S.';
    }
  
    exit();
   }
}