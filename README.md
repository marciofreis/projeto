# Podocare

Sistema web (e PWA) para clínica de podologia e salão: agenda, clientes, prontuário do pé, financeiro com PIX e portal do paciente.

## Como executar

1. Inicie Apache e MySQL no XAMPP (neste ambiente o Apache usa a porta **8080**).
2. Importe `database/schema.sql` no phpMyAdmin. Em uma base já criada, o sistema completa colunas novas sozinho.
3. Confira usuário e senha em `config/database.php`.
4. Acesse `http://localhost:8080/projeto/login.php` (ou `http://localhost/projeto/login.php` se a porta for 80).
5. No celular, use o IP da rede Wi-Fi, por exemplo `http://SEU-IP:8080/projeto/login.php`. O QR aparece no login e em Configurações.

## Acessos iniciais

- Clínica: `login.php` — `admin@podocare.local` / `admin123`
- Cliente: `portal.php` — a clínica libera telefone + senha na ficha do cliente

## O que o sistema faz

- **Agenda central:** calendário mensal com todos os horários, lista do dia, status e WhatsApp.
- **Agendamento público:** `agendar.php` com dias, horários, almoço e intervalo definidos em Configurações.
- **Clientes:** cadastro, foto, anamnese e acesso ao portal.
- **Prontuário:** mapa do pé, fotos, notas internas e orientação visível só para o paciente.
- **Financeiro:** caixa e PIX com QR Code e copia-e-cola.
- **Portal do cliente:** exames, preços, agenda e pagamentos (sem notas internas).
- **Usuários:** papéis e permissões (administrador, recepção, profissional, financeiro).
- **Impressão:** exame (clínica ou paciente), ficha e recibo.
- **PWA:** instalar na tela inicial; no iPhone, Compartilhar → Adicionar à Tela de Início.
- **Identidade:** logo, cores da marca e textos de WhatsApp editáveis em Configurações.

## Estrutura

- `index.php` — painel da clínica (login da equipe)
- `portal.php` — área do cliente
- `agendar.php` — link público de horário
- `pages/` — um arquivo por módulo
- `config/` — banco, login, PIX, agenda e permissões
- `uploads/` — logo, fotos de clientes e exames
- `assets/` — visual, mapa do pé, PIX e PWA
