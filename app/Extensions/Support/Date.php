<?php

namespace App\Extensions\Support;

use Illuminate\Support\Carbon;

class Date extends Carbon {

    /*
    |--------------------------------------------------------------------------
    | Formata a string passada para data em padrão americano
    |--------------------------------------------------------------------------
    */
    public static function toYMD($date) {
        return date("Y-m-d", strtotime(str_replace('/', '-', $date)));
    }

    /*
    |--------------------------------------------------------------------------
    | Formata a string passada para um unix-timestamp
    |--------------------------------------------------------------------------
    */
    public static function toYMDHIS($date){
        return date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $date)));
    }

    /*
    |--------------------------------------------------------------------------
    | Formata a string passada para data em padrão brasileiro
    |--------------------------------------------------------------------------
    */
    public static function toDMY($date, $format = "d/m/Y") {
        return date($format, strtotime(str_replace('/', '-', $date)));
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna a data em uma string legivel
    |--------------------------------------------------------------------------
    */
    public static function toEXT($date, $hours = false){
        if(!empty($date)){
            $d = date('d', strtotime(str_replace('/', '-', $date)));
            $dia = date('w', strtotime(str_replace('/', '-', $date)));
            $m = date('m', strtotime(str_replace('/', '-', $date)));
            $y = date('Y', strtotime(str_replace('/', '-', $date)));
            $h = date('H:i', strtotime(str_replace('/', '-', $date)));
            $f =  Self::dayName($dia) . ', ' . $d . ' de ' . self::monthName($m).' de '.$y;
            return (!$hours) ? $f : $f. ' às '.$h;
        }else{
            return '-';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se a string é uma data
    |--------------------------------------------------------------------------
    */
    public static function isYMD($date){
        try{
            return self::createFromFormat("Y-m-d", $date, APP_TIMEZONE)->format("Y-m-d") == $date;
        }catch (\InvalidArgumentException $e){
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se a string é uma data e hora
    |--------------------------------------------------------------------------
    */
    public static function isYMDHIS($date){
        try{
            return self::createFromFormat("Y-m-d H:i:s", $date, APP_TIMEZONE)->format("Y-m-d H:i:s") == $date;
        }catch (\InvalidArgumentException $e){
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se a string é uma data
    |--------------------------------------------------------------------------
    */
    public static function isDMY($date){
        try{
            return self::createFromFormat("d/m/Y", $date, APP_TIMEZONE)->format("d/m/Y") == $date;
        }catch (\InvalidArgumentException $e){
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se a string segue um formato hora-minuto válido
    |--------------------------------------------------------------------------
    */
    public static function isHI($hour){
        try{
            return self::createFromFormat("H:i", $hour, APP_TIMEZONE)->format("H:i") == $hour;
        }catch (\InvalidArgumentException $e){
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se a string segue um formato hora-minuto-segundo válido
    |--------------------------------------------------------------------------
    */
    public static function isHIS($hour){
        try{
            return self::createFromFormat("H:is", $hour, APP_TIMEZONE)->format("H:is") == $hour;
        }catch (\InvalidArgumentException $e){
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Formata o número do mês se ele for menor que dez
    |--------------------------------------------------------------------------
    */
    public static function formatMonth($month){
        $month = (int) $month;
        return ($month < 10) ? (($month > 0) ? "0{$month}" : 0) : $month;
    }

    /*
    |--------------------------------------------------------------------------
    |  Retorna o nome do mês
    |--------------------------------------------------------------------------
    */
    public static function monthName($month) {
        return [
            "01" => 'Janeiro',
            "02" => 'Fevereiro',
            "03" => 'Março',
            "04" => 'Abril',
            "05" => 'Maio',
            "06" => 'Junho',
            "07" => 'Julho',
            "08" => 'Agosto',
            "09" => 'Setembro',
            "10" => 'Outubro',
            "11" => 'Novembro',
            "12" => 'Dezembro'
        ][self::formatMonth($month)];
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna o nome do dia
    |--------------------------------------------------------------------------
    */
    public static function dayName($day) {
        return [
            1 => 'Segunda-Feira',
            2 => 'Terça-Feira',
            3 => 'Quarta-Feira',
            4 => 'Quinta-Feira',
            5 => 'Sexta-Feira',
            6 => 'Sábado',
            0 => 'Domingo'
        ][$day];
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna o número do dia da semana
    |--------------------------------------------------------------------------
    */
    public static function weekDayNumber($day) {
        return [
            'mo' => 1,
            'tu' => 2,
            'we' => 3,
            'th' => 4,
            'fr' => 5,
            'sa' => 6,
            'su' => 0
        ][$day];
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna a data por extenso
    |--------------------------------------------------------------------------
    */
    public static function toWords($date){
        if(!empty($date)){
            $dateD = date('d', strtotime(str_replace('/', '-', $date)));
            $dateM = date('m', strtotime(str_replace('/', '-', $date)));
            $dateY = date('Y', strtotime(str_replace('/', '-', $date)));
            $dateH = date('H:i', strtotime(str_replace('/', '-', $date)));
            return $dateD.' de '.self::mtn($dateM).' de '.$dateY. ' às '.$dateH;
        }else{
            return '-';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna o próximo dia útil a partir da data
    |--------------------------------------------------------------------------
    */
    public function nextUsefulDay(){

        while ($this->isWeekend()) {
            $this->addDay(1);
        }

        return $this;

    }

    //overrides

    public static function now($tz = null){
        return parent::now(APP_TIMEZONE);
    }

    public static function createFromFormat($format, $time, $tz = null){
        return parent::createFromFormat($format, $time, APP_TIMEZONE);
    }

    public static function createFromYMD($time){
        return parent::createFromFormat('Y-m-d', $time, APP_TIMEZONE);
    }

    public static function createFromYMDHIS($time){
        return parent::createFromFormat('Y-m-d H:i:s', $time, APP_TIMEZONE);
    }

    public static function createFromHI($time){
        return parent::createFromFormat('H:i', $time, APP_TIMEZONE);
    }

    public static function createFromHIS($time){
        return parent::createFromFormat('H:i:s', $time, APP_TIMEZONE);
    }

}