<?php
error_reporting(0);

require 'vendor/autoload.php';

use Sinesp\Sinesp;
use Sinesp\MandadoSinesp;

$veiculo = new Sinesp;
$mandado = new MandadoSinesp();

try {

	// Pega pelo Get
    $placa = $_GET['placa'];
    $nome = $_GET['nome'];

    if (!empty($placa)){
	    $veiculo->buscar($placa);
	    
	    if ($veiculo->existe()) {
		    print_r($veiculo->dados());
	    }
    }
	
	if (!empty($nome)) {
		$mandado->buscar($nome);
		
		if ($mandado->existe()){
			print_r($mandado->dados());
		}
	}
 
} catch (\Exception $e) {
    echo $e->getMessage();
}
