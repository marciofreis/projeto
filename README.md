# Podocare

Sistema web responsivo para gestão de podologia e salão, desenvolvido para rodar localmente no XAMPP com PHP e MySQL.

## Como executar

1. Inicie Apache e MySQL no XAMPP.
2. Abra o phpMyAdmin e importe `database/schema.sql`.
3. Confira usuário e senha em `config/database.php`.
4. Acesse `http://localhost/projeto/`.

## Estrutura

- `index.php`: roteador simples que escolhe a página pela URL.
- `pages/`: uma página PHP por módulo (`clientes.php`, `configuracoes.php`, `agenda.php` etc.).
- `config/`: conexão PDO e configurações.
- `uploads/`: imagens enviadas pelos formulários, separadas por cliente e clínica.
- `includes/`: layout compartilhado.
- `assets/css/`: identidade visual responsiva.
- `assets/js/`: interações do menu mobile.
- `database/`: schema MySQL e dados iniciais.

O layout funciona em desktop e mobile. Clientes e dados da clínica já possuem formulários com persistência via PDO e upload de imagem.

## Cadastro e imagens

- Em `?page=clientes`, o formulário grava o cliente na tabela `clientes` e aceita foto JPG, PNG ou WEBP de até 3 MB.
- Em `?page=configuracoes`, o formulário grava o estabelecimento na tabela `clinicas` e aceita o logo nos mesmos formatos.
- O PHP cria automaticamente `uploads/clientes` e `uploads/clinica` quando o primeiro arquivo é enviado.
- Para uma base que já tenha sido importada antes desta versão, execute manualmente: `ALTER TABLE clientes ADD foto_path VARCHAR(255);` e crie a tabela `clinicas` usando a definição atual de `database/schema.sql`.

No XAMPP, confirme que `file_uploads` está habilitado no `php.ini`. Se o arquivo ultrapassar o limite do servidor, ajuste `upload_max_filesize` e `post_max_size`, reinicie o Apache e mantenha a validação de 3 MB do formulário.