# E-Labore

O **E-Labore** é um projeto integrador desenvolvido por estudantes de Análise e Desenvolvimento de Sistemas da **FATEC São Sebastião**. A plataforma web tem como objetivo facilitar o envio, a organização, o acompanhamento e a validação das horas dos Projetos Integradores.

## Funcionalidades previstas

- Cadastro e autenticação de alunos e professores.
- Cadastro e gerenciamento de atividades acadêmicas (CRUD).
- Envio de documentos e comprovantes.
- Consulta ao histórico e ao status das solicitações.
- Aprovação ou rejeição de comprovantes pelos professores.
- Contabilização das horas validadas e geração de relatórios.

## Tecnologias previstas

- **Back-end:** PHP
- **Banco de dados:** MySQL
- **Front-end:** HTML, CSS e JavaScript

## Metodologia

O desenvolvimento segue a **prototipagem**, com construção e aprimoramento progressivo do sistema a partir de avaliações e feedbacks. O projeto é realizado em equipe, com acompanhamento de um professor orientador que também atua no setor empresarial.

## Status

Em desenvolvimento — projeto acadêmico da FATEC São Sebastião.

## Backend PHP — ambiente local

O backend utiliza PHP 8.2 ou superior, Composer e MySQL/MariaDB. A extensão `pdo_mysql` precisa estar habilitada no PHP.

1. Instale as dependências do projeto:

   ```sh
   composer install
   ```

2. Crie seu arquivo local de configuração a partir do exemplo:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Edite `.env` com os dados da sua instalação MySQL/MariaDB. Importe o banco usando o arquivo `elabore_db.sql` que já está na raiz do repositório. Não compartilhe nem envie seu `.env`.

4. Inicie o servidor local pela raiz do projeto:

   ```sh
   php -S localhost:8000 -t public public/index.php
   ```

   A verificação da aplicação ficará disponível em `http://localhost:8000/health`.
