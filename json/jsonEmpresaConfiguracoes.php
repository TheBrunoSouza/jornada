<?

error_reporting(0);
session_start();

//require('../includes/BancoPost.class.php');
//$conexao = new BancoPost();

require_once('../includes/OracleCieloJornada.class.php');

$OraCielo       = new OracleCielo();
$conexao        = $OraCielo->getCon();

$acao = $_REQUEST['acao'];
$json = array();

switch ($acao){
    case 'configuracoesAplicadas':
        $sqlEmpConfig = "
            SELECT 
                configuracao.id_configuracao,
                configuracao.descricao, 
                emp_config.tempo,
                emp_config.empresa
            FROM configuracao INNER JOIN empresa_configuracao emp_config ON configuracao.id_configuracao = emp_config.configuracao
            WHERE emp_config.empresa = ".$_REQUEST['idEmpresa'].";";
//        print_r($sqlEmpConfig);exit();
        
        $resEmpConfig   = $conexao->getResult($sqlEmpConfig);
        $arrayEmpConfig = array();

        foreach($resEmpConfig as $rowEmpConfig){
            $arrayEmpConfig[] = array(
                "idConfiguracao"    => $rowEmpConfig['id_configuracao'],
                "descricao"         => utf8_encode($rowEmpConfig['descricao']),
                "tempo"             => $rowEmpConfig['tempo'],
                "idEmpresa"         => $rowEmpConfig['empresa']
            );
        }

        $array['empConfig'] = $arrayEmpConfig;
        $array['status']    = $resEmpConfig;
        $json               = $array;
        
        unset($array, $arrayEmpConfig, $rowEmpConfig, $resEmpConfig, $sqlEmpConfig);
        break;
        
    case 'configuracoesDisponiveis':
        $sqlTiposConfig = "
            SELECT DISTINCT	
                    configuracao.id_configuracao, configuracao.descricao
            FROM 	
                    configuracao, empresa_configuracao emp_config
            WHERE 
                    emp_config.empresa NOT IN (".$_REQUEST['idEmpresa'].")
                    AND configuracao.id_configuracao NOT IN(
                                                            SELECT 	configuracao.id_configuracao
                                                            FROM 	configuracao, empresa_configuracao emp_config
                                                            WHERE 	configuracao.id_configuracao = emp_config.configuracao
                                                                    AND emp_config.empresa = ".$_REQUEST['idEmpresa'].");";
//        print_r($sqlTiposConfig);exit();
        
        $resTiposConfig     = $conexao->getResult($sqlTiposConfig);
        $arrayTiposConfig   = array();

        foreach($resTiposConfig as $rowTiposConfig){
            $arrayTiposConfig[] = array(
                "idConfiguracao"    => $rowTiposConfig['id_configuracao'],
                "descricao"         => utf8_encode($rowTiposConfig['descricao'])
            );
        }

        $array['tiposConfig']   = $arrayTiposConfig;
        $array['status']        = $resTiposConfig;
        $json                   = $array;

        unset($array, $arrayTiposConfig, $rowTiposConfig, $resTiposConfig, $sqlTiposConfig);
        break;
        
    case 'aplicar':
        $sqlAplicarConfig = "
                            INSERT INTO
                                    empresa_configuracao 
                            VALUES (
                                    ".$_REQUEST['idEmpresa'].",
                                    ".$_REQUEST['idConfiguracao'].",
                                    true, 
                                    null, 
                                    null, 
                                    '".$_REQUEST['novoTempo']."'
                            );";
//        print_r($sqlAplicarConfig);exit();
        
        $status             = $conexao->getResult($sqlAplicarConfig);
        $array['status']    = $status;
        $json               = $array;
        
        unset($sqlAplicarConfig, $array, $status);
        break;
        
    case 'desativar':
        $sqlEmpConfig = "
                        DELETE FROM 
                                    empresa_configuracao 
                        WHERE       empresa = ".$_REQUEST['idEmpresa']." 
                                    AND configuracao = ".$_REQUEST['idConfiguracao'].";";
        
        $status             = $conexao->getResult($sqlEmpConfig);
        $array['status']    = $status;
        $json               = $array;
        
        unset($sqlEmpConfig, $array, $status);
        break;
    
    case 'alterar':
        $sqlEmpConfig = "
                        UPDATE
                                empresa_configuracao 
                        SET 
                                tempo = '".$_REQUEST['novoTempo']."'
                        WHERE   empresa = ".$_REQUEST['idEmpresa']."
                                AND configuracao = ".$_REQUEST['idConfiguracao'].";";
        
        $status             = $conexao->getResult($sqlEmpConfig);
        $array['status']    = $status;
        $json               = $array;
        
        unset($sqlEmpConfig, $array, $status);
        break;
    
    case 'empresasComHoraExtra100':
        $sqlEmp100Config = "
                        SELECT  
                                id_empresa, 
                                empresa.nome,
                                empresa_configuracao.configuracao
                        FROM    
                                empresa, 
                                empresa_configuracao 
                        WHERE   empresa_configuracao.empresa = empresa.id_empresa 
                        AND     empresa_configuracao.configuracao = 5";
//        print_r($sqlEmp100Config);exit();

        $resEmpConfig100    = $conexao->getResult($sqlEmp100Config);
        $arrayEmpConfig100  = array();

        foreach($resEmpConfig100 as $rowEmp100){
            $arrayEmpConfig100[] = array(
                "idEmpresa"         => $rowEmp100['id_empresa'],
                "nomeEmpresa"       => utf8_encode($rowEmp100['nome']),
                "idConfiguracao"    => $rowEmp100['configuracao'],
            );
        }

        $array['empresas']  = $arrayEmpConfig100;
        $array['status']    = $resEmpConfig100;
        $json               = $array;
        
        unset($sqlEmp100Config, $resEmpConfig100, $arrayEmpConfig100, $rowEmp100);
        break;
        
    case 'verificaConfiguracao100':
        $sqlVerificaEmp100 = "
                        SELECT empresa 
                        FROM empresa_configuracao 
                        WHERE empresa = ".$_REQUEST['idEmpresa'].";";
//        print_r($sqlVerificaEmp100);exit();

        $resVerificaEmp100 = $conexao->getResult($sqlVerificaEmp100);
        
        break;
}

$callback = $_REQUEST['jsonp'];

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
}