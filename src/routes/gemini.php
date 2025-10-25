<?php
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../../conn.php';

// Função para tratar conteúdo: remove tags HTML e adiciona quebras de linha entre parágrafos
function limparConteudoParaTexto($html) {
    // Substitui <br> e </p> por quebras de linha
    $html = preg_replace('#<\s*(br|BR)\s*/?>#', "\n", $html);
    $html = preg_replace('#<\s*/\s*p\s*>#', "\n\n", $html); // duas quebras de linha entre parágrafos

    // Remove comentários HTML
    $html = preg_replace('/<!--.*?-->/s', '', $html);

    // Remove todas as outras tags
    $texto = strip_tags($html);

    // Normaliza múltiplos espaços e múltiplas quebras de linha
    $texto = preg_replace("/[ \t]+/", " ", $texto);
    $texto = preg_replace("/(\n\s*)+/", "\n", $texto);

    return trim($texto);
}

// Função para buscar conteúdo por ID
function obterConteudoPorId($conn, $id_label) {
    $stmt = $conn->prepare("SELECT id, intro FROM kt7u_label WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_label);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

// Função para buscar todos conteúdos de um curso
function obterConteudosPorCurso($conn, $id_curso) {
    $stmt = $conn->prepare("SELECT id, name, intro FROM kt7u_label WHERE course = ?");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();

    $conteudo_html = "";
    $conteudo_ids = [];

    while ($row = $result->fetch_assoc()) {
        $conteudo_html .= $row['name'] . "\n\n"; // título seguido de duas quebras
        $conteudo_html .= $row['intro'] . "\n\n"; // conteúdo seguido de duas quebras
        $conteudo_ids[] = $row['id'];
    }

    if (empty($conteudo_ids)) return null;

    return ['ids' => $conteudo_ids, 'intro' => $conteudo_html];
}

// Função para buscar usuário
function obterUsuario($conn, $id_user) {
    $stmt = $conn->prepare("SELECT username, firstname, lastname, phone2 FROM kt7u_user WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

// Função para buscar curso
function obterCurso($conn, $id_curso) {
    $stmt = $conn->prepare("SELECT fullname, shortname FROM kt7u_course WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

// Captura parâmetros do POST (ou usa valores padrão)
$id_user = isset($_POST['id_user']) ? intval($_POST['id_user']) : 3;
$id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : 2;
$id_label = isset($_POST['id']) ? intval($_POST['id']) : null;

// Consulta dados
$usuario = obterUsuario($conn, $id_user);
$curso = obterCurso($conn, $id_curso);

if ($id_label) {
    $conteudo = obterConteudoPorId($conn, $id_label);
} else {
    $conteudo = obterConteudosPorCurso($conn, $id_curso);
}

// Verifica se encontrou dados essenciais
if (!$usuario || !$curso || !$conteudo) {
    echo json_encode(['error' => 'Dados não encontrados']);
    exit;
}

// Trata conteúdo para WhatsApp (texto limpo, com quebras de linha entre parágrafos)
$conteudo_texto = limparConteudoParaTexto($conteudo['intro']);

// Monta resposta final apenas com texto limpo
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
        "id" => $id_label ?? implode(',', $conteudo['ids']),
        "intro_texto" => $conteudo_texto
    ]
];

// Retorna JSON formatado
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
