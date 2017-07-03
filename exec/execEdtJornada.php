<?php
session_start();

require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');
require_once('../includes/execute.class.php');

$OraCielo       = new OracleCielo();
$conexaoOra     = $OraCielo->getCon();
$ExecClass      = new ExecClass($conexaoOra);
$CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
$json_request   = file_get_contents('php://input');
$json           = json_decode($json_request);
$exp            = explode('-', $_REQUEST['dtEdt']);
$dtEdt          = $exp[2].$exp[1].$exp[0];
$idUsuario      = $CtrlAcesso->getUserID($_SESSION);
$justificativa  = "";
$aux1           = $json->{'data'};
$tamanho        = sizeof($aux1);

//Teste feito para evitar erros na busca da justificativa:
if($tamanho > 1){
    $aux2           = $aux1[0];
    $justificativa  = $aux2->{'observacao'};
}else{
    $justificativa  = $aux1->{'observacao'};
}

//CAPTURANDO A HORA DO SISTEMA DO MOMENTO DA ALTERACAO DO USUARIO, PARA QUE ASSIM FIQUE IGUAL NOS DOIS INSERTS REALIZADOS DO LOG
$horaDaAlteracao = $ExecClass->getHoraSistema();

//GRAVANDO PRIMEIRO LOG (Jornada atual do condutor)
$ExecClass->gravaLogJornada($dtEdt, $_REQUEST['idCondutor'], $idUsuario, $justificativa, $horaDaAlteracao['DATA_SISTEMA']);

switch ($_REQUEST['acao']){
    case 'update':
        foreach ($json as $values){
            $values = (is_array($values))?$values:Array(0 => $values);
            foreach($values as $result){
                $sqlUpJornada = "  
                    UPDATE jornada SET
                        situacao = ".$result->{'situacaoID'}.",
                        data_ini = to_timestamp('".$dtEdt.$result->{'diarioHrIni'}."', 'DD-MM-YYHH24:MI'), 
                        data_fim = to_timestamp('".$dtEdt.$result->{'diarioHrFim'}."', 'DD-MM-YYHH24:MI')
                    WHERE id_jornada = ".$result->{'idJornada'};

                $respostaUpJornada = oci_parse($conexaoOra, $sqlUpJornada);

                if(!oci_execute($respostaUpJornada)){
                    echo ' Erro no update da jornada.';
                    echo $sqlUpJornada;
                    exit();
                }       
            }
        }
        $ExecClass->atualizaEditado($_REQUEST['idCondutor'], $dtEdt);
        oci_free_statement($respostaUpJornada);
        break;
    case 'delete':
        foreach ($json as $values){
            $values = (is_array($values))?$values:Array(0 => $values);
            foreach($values as $result){
                $sqlDelJornada = "
                        DELETE FROM jornada
                        WHERE id_jornada = ".$result->{'idJornada'};

                $respostaDelJornada = oci_parse($conexaoOra, $sqlDelJornada);

                if(!oci_execute($respostaDelJornada)){
                    echo ' Erro no delete da jornada.';
                    echo $sqlDelJornada;
                    exit();
                }
            }
        }
        $ExecClass->atualizaEditado($_REQUEST['idCondutor'], $dtEdt);
        oci_free_statement($respostaDelJornada);
        break;
    case 'create':
        foreach ($json as $values){            
            $values = (is_array($values))?$values:Array(0 => $values);                
            foreach($values as $result){
                $sqlCreateJornada = "
                    INSERT INTO jornada(
                        id_jornada, 
                        situacao,
                        data_ini, 
                        data_fim, 
                        data_db,  
                        data_hora,
                        condutor,
                        placa,
                        data_alt,    
                        editado)
                    VALUES(
                        SEQ_JORNADA.nextval,
                        ".$result->{'situacaoID'}.", 
                        to_timestamp('".$dtEdt.$result->{'diarioHrIni'}."', 'DD-MM-YYHH24:MI'),
                        to_timestamp('".$dtEdt.$result->{'diarioHrFim'}."', 'DD-MM-YYHH24:MI'),
                        null, 
                        null,
                        ".$_REQUEST['idCondutor'].", 
                        (
                            SELECT  * from (SELECT  DISTINCT placa 
                            FROM    jornada 
                            WHERE   trunc(data_ini) = to_date('$dtEdt', 'DDMMYYYY') 
                                    AND condutor = ".$_REQUEST['idCondutor']." AND placa IS NOT NULL) WHERE rownum = 1
                        ),
                        SYSDATE,
                        'T')";

                $respostaCreate = oci_parse($conexaoOra, $sqlCreateJornada);

                if(!oci_execute($respostaCreate)){
                    echo ' Erro ao criar jova situacao na jornada.';
                    echo $sqlCreateJornada;
                    exit();
                }
            }
        }
        $ExecClass->atualizaEditado($_REQUEST['idCondutor'], $dtEdt);
        oci_free_statement($respostaCreate);
        break;
    default:
        echo 'default';
        break;
}

oci_close($conexaoOra);

//GRAVANDO O SEGUNDO LOG (Jornada após as modificações feitas pelo usuário)
$ExecClass->gravaLogJornada($dtEdt, $_REQUEST['idCondutor'], $idUsuario, NULL, $horaDaAlteracao['DATA_SISTEMA']);