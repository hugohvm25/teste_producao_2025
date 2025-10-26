<?php

// Configurações de Cabeçalho e Exibição de Erros
header('Content-Type: application/json; charset=utf-8');


// Inclui o arquivo de conexão MySQLi
include_once __DIR__ . '/../../conn.php'; 

// ===================================================================
// CONFIGURAÇÕES (DEVE ESTAR EM UM ARQUIVO DE AMBIENTE OU INCLUÍDO)
// ===================================================================

// Configurações da API Z-API
$api_config = [
    'base_url' => 'https://api.z-api.io/instances/3E93D7FC741B61AF08719E400CFFE64E',
    'token_url' => '/token/DD42686BD48BB58A1D9D412D',
    'token_security' => 'F7a1f9dcc50a94d12aa5f8b4db10a6b78S', 
    'seu_telefone_conectado' => '5521965368839' 
];

$apiKey = 'AIzaSyAE1zNhiQxRM6yR7_gnzZzIXTrZ4qjKnmk';

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// Validação da Conexão
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na conexão com o banco de dados.']);
    exit;
}

// ===================================================================
// FUNÇÕES DE LIMPEZA E TRATAMENTO DE MÍDIA
// ===================================================================

/**
 * Limpa o texto de tags HTML remanescentes e normaliza espaços.
 */
function limparConteudoParaTexto(string $html): string {
    // 1. Substitui tags comuns de quebra/parágrafo por quebras de linha
    $html = preg_replace('#<\s*(br|BR)\s*/?>#', "\n", $html);
    $html = preg_replace('#<\s*/\s*p\s*>#', "\n\n", $html);
    // 2. Remove comentários HTML
    $html = preg_replace('//s', '', $html);
    // 3. Remove todas as outras tags
    $texto = strip_tags($html);
    // 4. Normaliza e limpa
    $texto = preg_replace("/[ \t]+/", " ", $texto);
    $texto = preg_replace("/(\n\s*)+/", "\n", $texto);
    return trim($texto);
}

/**
 * Extrai a primeira URL de mídia (Áudio, Vídeo ou Imagem) do HTML.
 * @param string $html Conteúdo HTML do kt7u_label.intro.
 * @return array Contendo 'text' (texto limpo) e 'media_url'.
 */
function extrairMidiaETratarConteudo(string $html): array {
    $media_url = null;
    $remaining_html = $html;

    // Regex combinado para tags de mídia com 'src' e remove a primeira ocorrência
    $regex_midia = [
        '/(<(video|audio)[^>]*src=["\']([^"\']+)["\'][^>]*>.*?<\/\2>)/is', // video/audio com fechamento
        '/(<(video|audio)[^>]*src=["\']([^"\']+)["\'][^>]*>)/i',           // video/audio sem fechamento
        '/(<img[^>]*src=["\']([^"\']+)["\'][^>]*>)/i'                      // img
    ];

    foreach ($regex_midia as $regex) {
        if (preg_match($regex, $remaining_html, $matches)) {
            $media_url = $matches[count($matches) - 2]; // A URL é a penúltima ou última captura
            // Remove a tag de mídia completa que foi encontrada
            $remaining_html = preg_replace($regex, '', $remaining_html, 1);
            break; 
        }
    }

    // Trata o HTML restante como texto limpo
    $texto_limpo = limparConteudoParaTexto($remaining_html);

    return [
        'text' => $texto_limpo,
        'media_url' => $media_url
    ];
}

// ===================================================================
// FUNÇÕES AUXILIARES DE BANCO DE DADOS (MySQLi)
// ===================================================================

/**
 * Executa uma consulta preparada e retorna a primeira linha como um array associativo.
 * Usa o objeto $conn global (MySQLi).
 */
function executarConsultaUnica(mysqli $conn, string $sql, array $params, string $types): ?array {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro de preparação SQL: " . $conn->error);
        return null;
    }
    // O operador '...' (splat operator) requer que o PHP use as variáveis por referência, 
    // ou seja, o bind_param precisa receber referências na maioria das versões de PHP.
    // Uma solução mais compatível (mas mais verbosa) é passar as referências. 
    // Assumindo PHP moderno (>=5.6 ou usando call_user_func_array).
    // Para simplificar, vamos passar diretamente, mas tenha em mente o requisito de referência.
    $stmt->bind_param($types, ...$params); 
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

function obterUsuario(mysqli $conn, int $id_user): ?array {
    $sql = "SELECT username, firstname, lastname, phone2 FROM kt7u_user WHERE id = ? LIMIT 1";
    return executarConsultaUnica($conn, $sql, [$id_user], "i");
}

function obterCurso(mysqli $conn, int $id_curso): ?array {
    $sql = "SELECT fullname, shortname FROM kt7u_course WHERE id = ? LIMIT 1";
    return executarConsultaUnica($conn, $sql, [$id_curso], "i");
}

/**
 * Busca o ID do último conteúdo enviado ao usuário para este curso.
 */
function obterUltimoConteudoEnviado(mysqli $conn, int $id_user, int $id_curso): ?int {
    // Implementação real DEVE buscar em uma tabela de progresso.
    // Exemplo de SQL real:
    // $sql = "SELECT last_label_id FROM user_course_progress WHERE id_user = ? AND id_course = ?";
    // $progress = executarConsultaUnica($conn, $sql, [$id_user, $id_curso], "ii");
    // return $progress['last_label_id'] ?? null;
    
    // Mantendo a SIMULAÇÃO original
    return null; 
}

/**
 * Busca o PRÓXIMO item (kt7u_label) após o último enviado, ordenado por ID.
 */
function obterProximoConteudoSequencial(mysqli $conn, int $id_curso, ?int $last_label_id): ?array {
    $params = [$id_curso];
    $types = "i";
    
    $sql = "SELECT id, name, intro 
            FROM kt7u_label 
            WHERE course = ? 
            " . ($last_label_id !== null ? "AND id > ?" : "") . " 
            ORDER BY id ASC 
            LIMIT 1";

    if ($last_label_id !== null) {
        $params[] = $last_label_id;
        $types .= "i";
    }

    return executarConsultaUnica($conn, $sql, $params, $types);
}

/**
 * CORREÇÃO: Salva o registro de envio da mensagem usando a conexão MySQLi ($conn).
 */
function salvarEnvioNoDB(mysqli $conn, array $api_response, string $sender_phone, string $receiver_phone, ?string $message_text, string $media_type, ?string $media_url): string {
    // Garante que a conexão MySQLi seja usada
    if ($conn->connect_error) {
        return "<h3 style='color:red;'>ERRO AO SALVAR NO BANCO: Conexão MySQLi não está ativa.</h3>";
    }
    
    $message_id = $api_response['zaapId'] ?? 'envio-' . microtime(true);
    $sender_name = "Você (Sistema)";
    $api_timestamp = round(microtime(true) * 1000);
    
    $sql = "INSERT IGNORE INTO whatsapp_messages 
            (message_id, sender_phone, receiver_phone, message_text, sender_name, api_timestamp, media_type, media_url)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return "<h3 style='color:red;'>ERRO AO SALVAR NO BANCO (Prepare): " . $conn->error . "</h3>";
    }

    // Assumindo a tipagem dos campos: s, s, s, s, s, i, s, s (message_text, media_url podem ser NULL)
    // O timestamp é guardado como bigint/int (i) e os outros como string (s).
    $bind_success = $stmt->bind_param("sssssiss", 
        $message_id, 
        $sender_phone, 
        $receiver_phone, 
        $message_text, 
        $sender_name, 
        $api_timestamp, 
        $media_type, 
        $media_url
    );

    if (!$bind_success) {
        return "<h3 style='color:red;'>ERRO AO SALVAR NO BANCO (Bind): bind_param falhou.</h3>";
    }

    $execute_success = $stmt->execute();
    $stmt->close();
    
    if ($execute_success) {
        return "<h3 style='color:green;'>Mensagem ENVIADA salva no banco com sucesso!</h3>";
    } else {
        return "<h3 style='color:red;'>ERRO AO SALVAR NO BANCO (Execute): " . $conn->error . "</h3>";
    }
}


// ===================================================================
// FUNÇÕES DE ENVIO PARA A API (Z-API)
// ===================================================================

// Funções de envio de API (enviaTexto, enviaImagem, etc.) foram mantidas
// e ajustadas para usar a função `salvarEnvioNoDB` corrigida, 
// removendo a dependência do $db_config se a função usar a conexão MySQLi principal.

function callZApi(string $api_url, string $token, array $payload): string {
    $jsonData = json_encode($payload);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $jsonData,
        CURLOPT_HTTPHEADER     => [
            "client-token: $token",
            "content-type: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($curl);
    $err      = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return json_encode(['error' => 'cURL Error', 'message' => $err]);
    } else {
        return $response;
    }
}

// --- 1. ENVIAR TEXTO ---
function enviaTexto(mysqli $conn, string $phone, string $message, array $api_config): string {
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-text';
    $payload = ['phone' => $phone, 'message' => $message];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    if (isset($resposta_array['zaapId'])) {
        // Passando $conn (MySQLi) para a função de log
        echo salvarEnvioNoDB($conn, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $message, 'text', null);
    }
    return $resultado;
}

// --- 2. ENVIAR IMAGEM ---
function enviaImagem(mysqli $conn, string $phone, string $public_image_url, ?string $caption, array $api_config): string {
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-image'; 
    $payload = ['phone' => $phone, 'image' => $public_image_url, 'caption' => $caption];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    echo "<strong>DEBUG (enviaImagem): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($conn, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $caption, 'image/jpeg', $public_image_url);
    }
    return $resultado;
}

// --- 3. ENVIAR ÁUDIO ---
function enviaAudio(mysqli $conn, string $phone, string $public_audio_url, array $api_config): string {
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-audio'; 
    $payload = ['phone' => $phone, 'audio' => $public_audio_url];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    echo "<strong>DEBUG (enviaAudio): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($conn, $resposta_array, $api_config['seu_telefone_conectado'], $phone, null, 'audio/ogg', $public_audio_url);
    }
    return $resultado;
}

// --- 4. ENVIAR VÍDEO ---
function enviaVideo(mysqli $conn, string $phone, string $public_video_url, ?string $caption, array $api_config): string {
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-video'; 
    $payload = ['phone' => $phone, 'video' => $public_video_url, 'caption' => $caption];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    echo "<strong>DEBUG (enviaVideo): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($conn, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $caption, 'video/mp4', $public_video_url);
    }
    return $resultado;
}

/**
 * Função unificada para decidir e enviar mensagem (texto ou mídia).
 */
function enviarMensagem(mysqli $conn, string $phone, $message_or_url, ?string $caption, array $api_config): string
{
    // Listas de extensões conhecidas
    $image_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $video_exts = ['mp4', 'mov', '3gp', 'mkv'];
    $audio_exts = ['ogg', 'mp3', 'aac', 'opus', 'wav', 'm4a'];

    // 1. Verifica se é uma URL
    if (str_starts_with(strtolower($message_or_url), 'http://') || str_starts_with(strtolower($message_or_url), 'https://')) {
        
        $ext = strtolower(pathinfo($message_or_url, PATHINFO_EXTENSION));

        if (in_array($ext, $image_exts)) {
            echo "<h2>Reconhecido: IMAGEM</h2>";
            return enviaImagem($conn, $phone, $message_or_url, $caption, $api_config);
        } elseif (in_array($ext, $video_exts)) {
            echo "<h2>Reconhecido: VÍDEO</h2>";
            return enviaVideo($conn, $phone, $message_or_url, $caption, $api_config);
        } elseif (in_array($ext, $audio_exts)) {
            echo "<h2>Reconhecido: ÁUDIO</h2>";
            return enviaAudio($conn, $phone, $message_or_url, $api_config);
        } else {
            // URL de um tipo desconhecido
            echo "<h2>Reconhecido: ARQUIVO (enviando link como texto)</h2>";
            $text_message = "Te enviei um arquivo que não consigo processar, aqui está o link: " . $message_or_url;
            return enviaTexto($conn, $phone, $text_message, $api_config);
        }

    } else {
        // 2. Não é uma URL, é texto.
        echo "<h2>Reconhecido: TEXTO</h2>";
        return enviaTexto($conn, $phone, $message_or_url, $api_config);
    }
}


// ===================================================================
// LÓGICA PRINCIPAL (Fluxo Sequencial)
// ===================================================================

// Captura e valida parâmetros do POST. Usando valores padrão para teste.
$id_user = filter_input(INPUT_POST, 'id_user', FILTER_VALIDATE_INT) ?? 3; 
$id_curso = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT) ?? 2;

// 1. Consulta dados básicos
$usuario = obterUsuario($conn, $id_user);
$curso = obterCurso($conn, $id_curso);

if (!$usuario || !$curso) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuário ou Curso não encontrado.'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 2. Determina o próximo conteúdo
$last_label_id = obterUltimoConteudoEnviado($conn, $id_user, $id_curso);
$proximo_conteudo = obterProximoConteudoSequencial($conn, $id_curso, $last_label_id);

if (!$proximo_conteudo) {
    // FIM DO CURSO
    $response = [
        "status" => "curso_concluido",
        "mensagem" => "Parabéns, {$usuario['firstname']}! Você concluiu todo o conteúdo do curso '{$curso['fullname']}'.",
        "conteudo" => [
            "id" => null,
            "intro_texto" => "Fim do Curso.",
            "media_url" => null 
        ]
    ];
    $conteudo_texto = $response['mensagem']; // Mensagem de conclusão

} else {
    
    // 3. Processa o conteúdo para extrair mídia e limpar o texto
    $resultado_processamento = extrairMidiaETratarConteudo($proximo_conteudo['intro']);
    
    // Constrói a mensagem a ser enviada
    $conteudo_texto = "Módulo: *" . limparConteudoParaTexto($proximo_conteudo['name']) . "*\n\n";
    $conteudo_texto .= $resultado_processamento['text'];
    
    $response = [
        "status" => "conteudo_sequencial",
        "usuario" => [
            "username" => $usuario['username'],
            "firstname" => $usuario['firstname'],
            "phone" => $usuario['phone2'] // Usando apenas o phone2
        ],
        "curso" => [
            "fullname" => $curso['fullname']
        ],
        "conteudo" => [
            "id" => $proximo_conteudo['id'],
            "intro_texto" => $conteudo_texto,
            "media_url" => $resultado_processamento['media_url'] // CAMPO DE MÍDIA
        ]
    ];
}

// Retorna JSON formatado
//echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$prompt = "Faça um resumo atrativo desse texto e envie como uma mensagem para um grande amigo, falando como se fosse uma grande novidade do mundo atual: " . $conteudo_texto;

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
];

// 4. Converta os dados para JSON
$jsonData = json_encode($data);

// 5. Inicialize o cURL
$ch = curl_init($url);

// 6. Configure as opções do cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string
curl_setopt($ch, CURLOPT_POST, true);           // Define o método como POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Define o corpo da requisição (JSON)
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Recomenda-se manter a verificação SSL

// 7. Execute a requisição
$response = curl_exec($ch);

// 8. Verifique por erros no cURL
if (curl_errno($ch)) {
    echo 'Erro no cURL: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// 9. Feche a sessão cURL
curl_close($ch);

// 10. Decodifique a resposta JSON
// O cURL já ecoa $response (que contém o JSON do Gemini)
$responseData = json_decode($response, true); // <--- CORREÇÃO: Decodificar para ARRAY associativo

// Inicializa a variável para garantir que seja uma string
$texto_retorno = null; 

// CORREÇÃO da lógica de extração para usar $responseData e ser segura
if (isset($responseData['candidates']) && 
    is_array($responseData['candidates']) && 
    isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    
    // Extrai o texto
    $texto_retorno = $responseData['candidates'][0]['content']['parts'][0]['text'];

    // DEBUG: Se quiser ver o texto da IA no HTML
    // echo "### Texto do Gemini Extraído: ###\n" . htmlspecialchars($texto_retorno) . "<hr>";
}

// ===================================================================
// FLUXO DE ENVIO DE MENSAGEM (TESTE)
// ===================================================================

$telefone_para_teste = !empty($usuario['phone2']) ? $usuario['phone2'] : '5521992491608'; // O número do CLIENTE

// Prioriza o texto gerado pela IA. Se a IA falhar, usa o texto original ($conteudo_texto).
// Se $texto_retorno for NULL, ele assume o valor de $conteudo_texto (o fallback).
$mensagem_para_envio = $texto_retorno ?? $conteudo_texto; 
$legenda_final = null; // A legenda só será usada se for mídia

// Aqui usamos a $midia_url que deve vir do seu fluxo principal (variável já existente).
// Se o seu fluxo principal definiu $midia_url (que não estava no trecho anterior, mas assumindo que existe)
if (!empty($midia_url)) {
    // Se há URL de mídia, enviamos a mídia e o texto limpo (da IA ou fallback) como LEGENDA/CAPTION
    $mensagem_para_envio_final = $midia_url; // Variável temporária para a URL
    $legenda_final = $mensagem_para_envio; 
} else {
    // Se não há mídia, enviamos apenas o texto (da IA ou fallback)
    $mensagem_para_envio_final = $mensagem_para_envio; // Variável temporária para o texto
    $legenda_final = null; 
}


// --- CHAMADA DE ENVIO ---
// $mensagem_para_envio_final agora tem a string garantida (ou URL da mídia)
$resultado = enviarMensagem($conn, $telefone_para_teste, $mensagem_para_envio_final, $legenda_final, $api_config);

?>