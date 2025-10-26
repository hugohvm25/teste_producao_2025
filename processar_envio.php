<?php
// Mostra todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Inclui o seu "cérebro"
require_once 'funcoes_api.php';

// 2. Pega os dados que vieram do formulário
$telefone_para_enviar = $_POST['numero'] ?? null;     // <-- MUDANÇA 1: Lendo o novo campo 'numero'
$mensagem_para_enviar = $_POST['mensagem'] ?? null;
$legenda_para_enviar = $_POST['legenda'] ?? null;

// 3. Define as configurações
// !! FAEL, ÚLTIMO AVISO SÉRIO: SUA SENHA ESTÁ EXPOSTA !!
// !! TROQUE AGORA MESMO NO PAINEL DA HOSTINGER E AQUI !!
$api_config = [
    'base_url' => 'https://api.z-api.io/instances/3E93D7FC741B61AF08719E400CFFE64E',
    'token_url' => '/token/DD42686BD48BB58A1D9D412D',
    'token_security' => 'F7a1f9dcc50a94d12aa5f8b4db10a6b78S', 
    'seu_telefone_conectado' => '5521965368839'
];
$db_config = [
    'host' => 'localhost',
    'name' => 'u490880839_7xii0',
    'user' => 'u490880839_7ZrhP',
    'pass' => 'SUA_NOVA_SENHA_AQUI_PELO_AMOR_DE_DEUS', // <-- TROQUE A SENHA!
    'charset' => 'utf8mb4'
];
// NÃO PRECISAMOS MAIS DESTA LINHA FIXA:
// $telefone_para_teste = '5521992491608'; 

// 4. Verifica se os campos obrigatórios não estão vazios
if (empty($telefone_para_enviar) || empty($mensagem_para_enviar)) { // <-- MUDANÇA 2: Verificando o telefone
    die("Erro: Os campos 'Enviar Para' e 'Mensagem ou URL' não podem estar vazios.");
}

// 5. INICIA O BUFFER DE SAÍDA
ob_start();

// 5. CHAMA A SUA FUNÇÃO UNIVERSAL!
$resultado_json = enviarMensagem(
    $telefone_para_enviar, // <-- MUDANÇA 3: Usando a variável do formulário
    $mensagem_para_enviar, 
    $api_config,
    $db_config,
    $legenda_para_enviar
);

// 6. PEGA TUDO QUE FOI "ECHADO"
$debug_html = ob_get_clean();


// 7. Analisa o resultado final
$resposta_array = json_decode($resultado_json, true);
$sucesso = isset($resposta_array['zaapId']);

// 8. Define as variáveis de status
$status_titulo = $sucesso ? "Mensagem Enviada!" : "Falha no Envio!";
$status_icone = $sucesso ? "✅" : "❌";
$status_classe_css = $sucesso ? "status-success" : "status-error";

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Envio</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* (Todo o seu CSS anterior continua aqui, sem alterações) */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f4f7f6; margin: 0; padding: 20px;
            display: grid; place-items: center; min-height: 90vh;
        }
        .chat-container {
            width: 100%; max-width: 500px; background-color: #ffffff;
            border-radius: 12px; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }
        .status-card {
            padding: 30px 25px; text-align: center;
        }
        .status-card h1 {
            font-size: 28px; margin: 15px 0 10px 0; color: #1a1a1a;
        }
        .status-card p {
            font-size: 16px; color: #666; margin-bottom: 25px;
        }
        .status-icon { font-size: 48px; line-height: 1; }
        .status-success h1 { color: #28a745; }
        .status-error h1 { color: #dc3545; }
        .button {
            width: 100%; padding: 14px; font-size: 16px;
            font-weight: 600; color: #ffffff; background-color: #007bff;
            border: none; border-radius: 8px; cursor: pointer;
            transition: background-color 0.2s ease; text-decoration: none;
            display: inline-block; box-sizing: border-box; text-align: center;
        }
        .button:hover { background-color: #005ecf; }
        .debug-container { padding: 0 25px 25px 25px; }
        details {
            font-size: 12px; background-color: #f9f9f9; border: 1px solid #eee;
            border-radius: 8px; padding: 10px;
        }
        summary { font-weight: 600; cursor: pointer; color: #555; }
        pre {
            white-space: pre-wrap; word-wrap: break-word; background: #eee;
            padding: 10px; border-radius: 5px; margin-top: 10px;
            max-height: 300px; overflow-y: auto;
        }
    </style>
</head>
<body>

    <div class="chat-container">
        
        <div class="status-card <?php echo $status_classe_css; ?>">
            <div class="status-icon"><?php echo $status_icone; ?></div>
            <h1><?php echo $status_titulo; ?></h1>
            <p>
                <?php 
                if ($sucesso) {
                    // Mensagem de sucesso atualizada
                    echo "Sua mensagem foi enviada para " . htmlspecialchars($telefone_para_enviar) . ".";
                } else {
                    echo "Houve um erro ao tentar enviar para " . htmlspecialchars($telefone_para_enviar) . ".";
                }
                ?>
            </p>
            <a href="chat.html" class="button">Enviar Nova Mensagem</a>
        </div>

        <div class="debug-container">
            <details>
                <summary>Ver Log de Processamento e Resposta</summary>
                
                <strong>Etapas do Processamento (Debug):</strong>
                <pre><?php echo htmlspecialchars($debug_html); ?></pre>
                
                <strong>Resposta Bruta Final da API (JSON):</strong>
                <pre><?php echo htmlspecialchars($resultado_json); ?></pre>
            </details>
        </div>
        
    </div>

</body>
</html>