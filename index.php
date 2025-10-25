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
        textarea, input[type="file"] { width: 100%; margin-bottom: 10px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        pre { background: #eee; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Teste API Gemini Multimodal</h1>
    
    <form id="geminiForm">
        <label for="prompt">Digite seu prompt:</label><br>
        <textarea id="prompt" name="prompt" placeholder="Ex: Explique esta imagem"></textarea><br>

        <label for="file">Anexo (imagem ou áudio):</label><br>
        <input type="file" id="file" name="file"><br><br>

        <button type="submit">Enviar</button>
    </form>

    <h2>Resposta:</h2>
    <pre id="response">Aqui aparecerá a resposta...</pre>

    <script>
        const form = document.getElementById('geminiForm');
        const responseBox = document.getElementById('response');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const prompt = document.getElementById('prompt').value;
            const fileInput = document.getElementById('file');
            const file = fileInput.files[0];

            if (!prompt && !file) return alert("Digite um prompt ou envie um arquivo!");

            responseBox.textContent = "Carregando...";

            let fileData = null;
            if (file) {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = async () => {
                    fileData = reader.result.split(',')[1]; // remove prefix data:image/...;base64,
                    await sendRequest(prompt, fileData, file.type);
                };
            } else {
                await sendRequest(prompt, null, null);
            }
        });

        async function sendRequest(prompt, base64File, mimeType) {
            try {
                const body = { prompt };
                if (base64File) {
                    body.file = base64File;
                    body.mimeType = mimeType;
                }

                const res = await fetch('src/routes/gemini.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });

                const data = await res.json();
                responseBox.textContent = JSON.stringify(data, null, 2);
            } catch (err) {
                responseBox.textContent = "Erro: " + err.message;
            }
        }
    </script>
</body>
</html>
