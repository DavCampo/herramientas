<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Helpers extends Controller
{
    static function getDidsWhenManagerHaveMoreClients($idClient){
        switch ($idClient) {
            case '472': //472
                return "`Cartera` = 'BANCO AZTECA' AND ";
            case '486': //486
                return "`Cartera` = 'MULTICLIENTE' AND ";
            case '490': //490
                return "`Cartera` = 'VOLKSWAGEN' AND ";
            case '510': //510
                return "`Cartera` = 'SCOTIABANK' AND ";
            dafault:
                return "";
        }
    }
}
