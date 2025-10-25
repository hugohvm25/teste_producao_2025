<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Gemini Multimodal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f9; }
        h1 { color: #333; }
        form { margin-bottom: 20px; }
        input[type="number"] { width: 100%; margin-bottom: 10px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        pre { background: #eee; padding: 15px; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Teste API Gemini Multimodal</h1>

    <!-- Adicionado id e removido action para evitar redirect -->
    <form id="geminiForm" onsubmit="return false;">
        <label>Escolha o conteúdo (ID):</label>
        <input type="number" id="id" name="id" value="1" />
        <button id="sendBtn" type="button">Enviar</button>
    </form>

    <h2>Resposta:</h2>
    <pre id="response">Aqui aparecerá a resposta...</pre>

    <script>
        const btn = document.getElementById('sendBtn');
        const responseBox = document.getElementById('response');

        btn.addEventListener('click', async () => {
            const id = document.getElementById('id').value.trim();
            if (!id) {
                alert("Informe um ID válido!");
                return;
            }

            responseBox.textContent = "Carregando...";

            try {
                const res = await fetch('src/routes/gemini.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(id)
                });

                const data = await res.text();

                try {
                    const json = JSON.parse(data);
                    responseBox.textContent = JSON.stringify(json, null, 2);
                } catch {
                    responseBox.textContent = data;
                }
            } catch (err) {
                responseBox.textContent = "Erro: " + err.message;
            }
        });
    </script>
</body>
</html>
