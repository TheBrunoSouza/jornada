<?
class sendMail{
	private $con = null;	
	function __construct($conexao){
		if($conexao){
			$this->con = $conexao;
		}
		else{
			require_once("BancoPost.class.php");
			$this->con = new BancoPost();
		}
	}

	function __destruct(){
		$con = null;
	}

	 public function getHtml($idCliente, $idUsuario, $descEquipamento, $equipCodigo, $equipSenha){
		$sql = "SELECT cliente.email AS cli_email, cliente.nome AS cli_nome, usuario.email AS usu_email, usuario.nome AS usu_nome, inst.placa AS plc
				FROM ist_cliente cliente, ist_usuario usuario, ist_instalacao inst
				WHERE id_cliente = ".$idCliente."
				AND id_usuario = ".$idUsuario."
				AND inst.usuario = id_usuario
				AND inst.cliente = cliente";
		//echo $sql;
		$result = $this->con->getResult($sql);		
		
		$html="
		<table width='80%' class='email'>
			<tr>
			  <th colspan='2'>Libera&ccedil;&atilde;o de Instala&ccedil;&atilde;o</th>
			</tr>
			<tr>
			  <td>Empresa:</td>
			  <td>".utf8_decode($result[0]['cli_nome'])."
			  </td>
			</tr>
			<tr>
			  <td>Usuario Instalador:</td>
			  <td>".utf8_decode($result[0]['usu_nome'])."</td>
			</tr>
			<tr>
			  <td>Tipo de equipamento:</td>
			  <td>".$descEquipamento."</td>
			</tr>
			<tr>
			  <td>Codigo:</td>
			  <td>".$equipCodigo."</td>
			</tr>
			<tr>
			  <td>Placa:</td>
			  <td>".utf8_decode($result[0]['plc'])."</td>
			</tr>
			<tr>
			  <td>Senha:</td>
			  <td>".$equipSenha."</td>
			</tr>
		</table><br><br>";
		//$to = $email;		  
		//echo $html;
		$nome = utf8_decode('Gestor de instalações');
		$subject = "CIVITEC Instalacao";
		if($result[0]['cli_email']){
			$this->enviaEmail($result[0]['cli_email'], $result[0]['cli_nome'], $result[0]['usu_email'], $subject, $html, 'Mensagem enviada!');
		//if($result[0]['usu_email'])
			//$this->enviaEmail($result[0]['usu_email'], $result[0]['usu_nome'], $subject, $html, 'Mensagem enviada!');
			return true;
		}
		exit;
	 }
	
	public function enviaEmail($emaildestinatario, $nome, $comcopia, $assunto, $html, $msgAlert){
	
		$html.="
		<style>
		.email td{
			padding: 2px 5px 2px 5px;
			border: 1px #f0f0f0 solid;
			background-color:#f0f0f0;
		}
		.email th{
			font-weight:bold;
			border: 1px #D0D0D0 solid;
			padding: 3px 3px 3px 5px;
			background: #F9F9F9;
			color:#666;
			zoom:1;
			height:24px;
		}
		</style>";
		
		/* Medida preventiva para evitar que outros domínios sejam remetente da sua mensagem. */
		if (eregi('tempsite.ws$|gestordefrotas.com.br$|hospedagemdesites.ws$|websiteseguro.com$', $_SERVER[HTTP_HOST])) {  
				$emailsender='instalacao@gestordefrotas.com.br'; // Substitua essa linha pelo seu e-mail@seudominio
		} else {
				$emailsender = "instalacao@" . $_SERVER[HTTP_HOST];
				//    Na linha acima estamos forçando que o remetente seja 'webmaster@seudominio',
				// Você pode alterar para que o remetente seja, por exemplo, 'contato@seudominio'.
		}
		 
		/* Verifica qual éo sistema operacional do servidor para ajustar o cabeçalho de forma correta.  */
		if(PATH_SEPARATOR == ";") $quebra_linha = "\r\n"; //Se for Windows
		else $quebra_linha = "\n"; //Se "nÃ£o for Windows"
		 
		// Passando os dados obtidos pelo formulário para as variáveis abaixo
		$nomeremetente     = 'Libera&ccedil;&atilde;o de Instala&ccedil;&atilde;o';//$_POST['nomeremetente'];
		$emailremetente    = 'instalacao@civitec.ind.br';//$_POST['emailremetente'];
		//$emaildestinatario = 'esboeno@gmail.com';//$_POST['emaildestinatario'];
		//$comcopia          = $_POST['comcopia'];
		//$comcopiaoculta    = $_POST['comcopiaoculta'];
		//$assunto           = $_POST['assunto'];
		$mensagem          = $html;//'TESTE DE MENSAGEM';//$_POST['mensagem'];
		 
		 
		/* Montando a mensagem a ser enviada no corpo do e-mail. */
		$mensagemHTML = '<p><b><i>'.$mensagem.'</i></b></p><hr>';
		 
		 
		/* Montando o cabeÃ§alho da mensagem */
		$headers = "MIME-Version: 1.1" .$quebra_linha;
		$headers .= "Content-type: text/html; charset=iso-8859-1" .$quebra_linha;
		// Perceba que a linha acima contém "text/html", sem essa linha, a mensagem não chegará formatada.
		$headers .= "From: " . $emailsender.$quebra_linha;
		$headers .= "Cc: " . $comcopia . $quebra_linha;
		$headers .= "Bcc: " . $comcopiaoculta . $quebra_linha;
		$headers .= "Reply-To: " . $emailremetente . $quebra_linha;
		// Note que o e-mail do remetente será usado no campo Reply-To (Responder Para)
		 
		/* Enviando a mensagem */
		
		//É obrigatório o uso do parâmetro -r (concatenação do "From na linha de envio"), aqui na Locaweb:
		
		if(!mail($emaildestinatario, $assunto, $mensagemHTML, $headers ,"-r".$emailsender)){ // Se for Postfix
			$headers .= "Return-Path: " . $emailsender . $quebra_linha; // Se "não for Postfix"
			mail($emaildestinatario, $assunto, $mensagemHTML, $headers );
		}
		 
		/* Mostrando na tela as informações enviadas por e-mail */
		//print "Mensagem <b>$assunto</b> enviada com sucesso!<br>";
	}
}
?>
