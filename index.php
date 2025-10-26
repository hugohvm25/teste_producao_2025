<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API Gemini (PHP)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f9; }
        h1 { color: #333; }
        form { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        label { display: block; margin-top: 10px; font-weight: bold; color: #555; }
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        button:hover:not(:disabled) { background-color: #0056b3; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }
        pre {
            background: #272822; /* Fundo escuro para código */
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            overflow-x: auto;
            border: 1px solid #000;
        }
        .loading {
            text-align: center;
            color: #007bff;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Teste API Gemini Multimodal (PHP)</h1>
    <p>Endpoints testados: <code>src/routes/gemini.php</code></p>

    <form id="geminiForm">
        <label for="id">Conteúdo Específico (ID do Label):</label>
        <input type="number" id="id" name="id" value="1" placeholder="Deixe em branco para buscar todos os conteúdos do curso" />

        <label for="id_curso">ID do Curso (Padrão: 2):</label>
        <input type="number" id="id_curso" name="id_curso" value="2" />

        <label for="id_user">ID do Usuário (Padrão: 3):</label>
        <input type="number" id="id_user" name="id_user" value="3" />

        <button id="sendBtn" type="submit">Enviar Requisição</button>
    </form>

    <h2>Resposta da API:</h2>
    <pre id="response">Aguardando envio do formulário...</pre>

    <script>
        const form = document.getElementById('geminiForm');
        const btn = document.getElementById('sendBtn');
        const responseBox = document.getElementById('response');
        const apiPath = 'src/routes/gemini.php'; // Caminho para sua API PHP

        form.addEventListener('submit', async (e) => {
            e.preventDefault(); // Impede o envio padrão do formulário (redirect)

            // --- UX: Desabilita e mostra carregando ---
            btn.disabled = true;
            btn.textContent = 'Enviando...';
            responseBox.className = 'loading';
            responseBox.textContent = "Carregando a resposta da API...";
            // ------------------------------------------

            // Coleta os dados do formulário de forma moderna
            const formData = new FormData(form);
            // Prepara o body para o Content-Type: application/x-www-form-urlencoded
            const body = new URLSearchParams(formData).toString();

            try {
                const res = await fetch(apiPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body
                });

                // Tenta ler o texto da resposta
                const data = await res.text();

                // Tenta parsear JSON para formatar
                try {
                    const json = JSON.parse(data);
                    responseBox.textContent = JSON.stringify(json, null, 2);
                } catch {
                    // Se não for JSON (ex: erro de PHP, erro de HTML), exibe o texto puro
                    responseBox.textContent = data;
                }

            } catch (err) {
                responseBox.textContent = `Erro de Conexão: ${err.message}. Verifique se o servidor está rodando e o caminho ('${apiPath}') está correto.`;
            } finally {
                // --- UX: Reabilita e restaura o texto do botão ---
                btn.disabled = false;
                btn.textContent = 'Enviar Requisição';
                responseBox.className = ''; // Remove classe de loading
                // ------------------------------------------
            }
        });

    </script>
</body>
</html>