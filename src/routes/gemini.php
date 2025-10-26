<?php
// Configura o cabeçalho antes de qualquer saída
header('Content-Type: application/json; charset=utf-8');

// Garante que o arquivo de conexão seja incluído (assumindo que ele define $conn)
include_once __DIR__ . '/../../conn.php';

// 1. VERIFICAÇÃO DE CONEXÃO E FUNÇÃO DE ERRO
if (!isset($conn) || $conn->connect_error) {
    // Definir o cabeçalho de status HTTP para erro de servidor (500)
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Encerra a execução e retorna um erro JSON.
 * @param string $mensagem A mensagem de erro.
 * @param int $http_status O código de status HTTP (ex: 404 Not Found, 400 Bad Request).
 */
function responderErro(string $mensagem, int $http_status = 404): void {
    http_response_code($http_status);
    echo json_encode(['error' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. FUNÇÕES AUXILIARES DE BANCO DE DADOS (DRY)

/**
 * Executa uma consulta preparada simples para buscar uma única linha.
 */
function executarConsultaUnica($conn, string $sql, array $params, string $types) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro de preparação SQL: " . $conn->error);
        return null;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function obterUsuario($conn, int $id_user) {
    $sql = "SELECT username, firstname, lastname, phone2 FROM kt7u_user WHERE id = ? LIMIT 1";
    return executarConsultaUnica($conn, $sql, [$id_user], "i");
}

function obterCurso($conn, int $id_curso) {
    $sql = "SELECT fullname, shortname FROM kt7u_course WHERE id = ? LIMIT 1";
    return executarConsultaUnica($conn, $sql, [$id_curso], "i");
}

function obterConteudoPorId($conn, int $id_label) {
    $sql = "SELECT id, intro FROM kt7u_label WHERE id = ? LIMIT 1";
    return executarConsultaUnica($conn, $sql, [$id_label], "i");
}

function obterConteudosPorCurso($conn, int $id_curso) {
    $stmt = $conn->prepare("SELECT id, name, intro FROM kt7u_label WHERE course = ?");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();

    $conteudo_html = "";
    $conteudo_ids = [];

    while ($row = $result->fetch_assoc()) {
        // Uso de separadores claros
        $conteudo_html .= $row['name'] . "\n\n";
        $conteudo_html .= $row['intro'] . "\n\n\n"; // Mais quebras para separar conteúdos
        $conteudo_ids[] = $row['id'];
    }

    if (empty($conteudo_ids)) return null;

    return ['ids' => $conteudo_ids, 'intro' => $conteudo_html];
}

// 3. FUNÇÃO DE LIMPEZA DE CONTEÚDO (SLIGHTLY OPTIMIZED REGEX)

/**
 * Trata conteúdo: remove tags HTML e adiciona quebras de linha para formatação de texto.
 */
function limparConteudoParaTexto(string $html): string {
    // 1. Substitui tags comuns de quebra/parágrafo por quebras de linha para formatação de texto
    $html = preg_replace('#<\s*(br|BR)\s*/?>#', "\n", $html); // <br> por \n
    $html = preg_replace('#<\s*/\s*p\s*>#', "\n\n", $html);    // </p> por \n\n (separador de parágrafo)

    // 2. Remove comentários HTML
    $html = preg_replace('//s', '', $html);

    // 3. Remove todas as outras tags
    $texto = strip_tags($html);

    // 4. Normaliza e limpa:
    // a) Remove tabs e espaços extras (troca múltiplos espaços/tabs por um único espaço)
    $texto = preg_replace("/[ \t]+/", " ", $texto);
    // b) Normaliza quebras de linha: troca múltiplos \n (com ou sem espaços) por uma única quebra
    $texto = preg_replace("/(\n\s*)+/", "\n", $texto);

    return trim($texto);
}

// 4. VALIDAÇÃO DE ENTRADA (USANDO filter_input)

// Captura parâmetros do POST e aplica validação/filtragem
$id_user = filter_input(INPUT_POST, 'id_user', FILTER_VALIDATE_INT) ?? 3;
$id_curso = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT) ?? 2;
// Permite que id_label seja nulo
$id_label = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// 5. LÓGICA PRINCIPAL COM EARLY EXITS

$usuario = obterUsuario($conn, $id_user);
if (!$usuario) {
    responderErro("Usuário com ID {$id_user} não encontrado.", 404);
}

$curso = obterCurso($conn, $id_curso);
if (!$curso) {
    responderErro("Curso com ID {$id_curso} não encontrado.", 404);
}

// Busca o conteúdo
if ($id_label) {
    $conteudo = obterConteudoPorId($conn, $id_label);
    if (!$conteudo) {
        responderErro("Conteúdo (Label) com ID {$id_label} não encontrado.", 404);
    }
    $conteudo_id_final = $id_label;
} else {
    $conteudo = obterConteudosPorCurso($conn, $id_curso);
    if (!$conteudo) {
        // Assume que o curso pode estar vazio
        $conteudo_texto = "O curso '{$curso['fullname']}' não possui conteúdos (labels) associados.";
        $conteudo_id_final = "N/A";
    } else {
        $conteudo_id_final = implode(',', $conteudo['ids']);
    }
}

// Trata conteúdo para WhatsApp (texto limpo)
$conteudo_texto = $conteudo ? limparConteudoParaTexto($conteudo['intro']) : $conteudo_texto;


// Monta resposta final
$response = [
    "status" => "sucesso",
    "usuario" => [
        "username" => $usuario['username'],
        "firstname" => $usuario['firstname'],
        "lastname" => $usuario['lastname'],
        "phone" => $usuario['phone2']
    ],
    "curso" => [
        "fullname" => $curso['fullname'],
        "shortname" => $curso['shortname']
    ],
    "conteudo" => [
        "id" => $conteudo_id_final,
        "intro_texto" => $conteudo_texto
    ]
];

// Retorna JSON formatado
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Fechar a conexão é uma boa prática
$conn->close();

?>