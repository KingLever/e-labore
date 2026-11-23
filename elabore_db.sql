-- E-Labora: esquema completo do banco (MariaDB / MySQL).
-- ATENÇÃO: este arquivo APAGA e recria todas as tabelas (inclusive as contas de teste).
-- Como usar: phpMyAdmin > banco "elabore_db" > Importar > escolher este arquivo.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS validacoes, comprovantes, entregas, atividade_turmas, atividades, turma_alunos, turmas, usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- Contas de professores e alunos.
CREATE TABLE usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('professor','aluno') NOT NULL DEFAULT 'aluno',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Turmas de cada professor (ex.: ADS, 2026/1, 1º período).
CREATE TABLE turmas (
  id INT NOT NULL AUTO_INCREMENT,
  professor_id INT NOT NULL,
  curso VARCHAR(100) NOT NULL,
  ano VARCHAR(10) NOT NULL,
  periodo TINYINT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY turma_unica (professor_id, curso, ano, periodo),
  CONSTRAINT turmas_professor_fk FOREIGN KEY (professor_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vínculo conta (aluno) <-> turma.
CREATE TABLE turma_alunos (
  turma_id INT NOT NULL,
  aluno_id INT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (turma_id, aluno_id),
  KEY aluno_id (aluno_id),
  CONSTRAINT turma_alunos_turma_fk FOREIGN KEY (turma_id) REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT turma_alunos_aluno_fk FOREIGN KEY (aluno_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atividades criadas pelo professor.
CREATE TABLE atividades (
  id INT NOT NULL AUTO_INCREMENT,
  professor_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  descricao TEXT DEFAULT NULL,
  ativa TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY professor_id (professor_id),
  CONSTRAINT atividades_professor_fk FOREIGN KEY (professor_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Em quais turmas cada atividade aparece.
CREATE TABLE atividade_turmas (
  atividade_id INT NOT NULL,
  turma_id INT NOT NULL,
  PRIMARY KEY (atividade_id, turma_id),
  KEY turma_id (turma_id),
  CONSTRAINT atividade_turmas_atividade_fk FOREIGN KEY (atividade_id) REFERENCES atividades (id) ON DELETE CASCADE,
  CONSTRAINT atividade_turmas_turma_fk FOREIGN KEY (turma_id) REFERENCES turmas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entregas (registros de horas) que o aluno envia em cada atividade. Pode haver várias por atividade.
CREATE TABLE entregas (
  id INT NOT NULL AUTO_INCREMENT,
  atividade_id INT NOT NULL,
  aluno_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT DEFAULT NULL,
  data DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  status ENUM('pendente','em_analise','aprovada','recusada') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY atividade_id (atividade_id),
  KEY aluno_id (aluno_id),
  CONSTRAINT entregas_atividade_fk FOREIGN KEY (atividade_id) REFERENCES atividades (id) ON DELETE CASCADE,
  CONSTRAINT entregas_aluno_fk FOREIGN KEY (aluno_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Arquivos anexados a cada entrega (o arquivo fica em storage/uploads).
CREATE TABLE comprovantes (
  id INT NOT NULL AUTO_INCREMENT,
  entrega_id INT NOT NULL,
  nome_arquivo VARCHAR(255) NOT NULL,
  caminho_arquivo VARCHAR(255) NOT NULL,
  tipo VARCHAR(100) DEFAULT NULL,
  tamanho INT UNSIGNED NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY entrega_id (entrega_id),
  CONSTRAINT comprovantes_entrega_fk FOREIGN KEY (entrega_id) REFERENCES entregas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservada para a aprovação das entregas pelo professor (ainda sem tela).
CREATE TABLE validacoes (
  id INT NOT NULL AUTO_INCREMENT,
  entrega_id INT NOT NULL,
  professor_id INT NOT NULL,
  decisao ENUM('aprovada','recusada') NOT NULL,
  observacao TEXT DEFAULT NULL,
  data_validacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY entrega_id (entrega_id),
  KEY professor_id (professor_id),
  CONSTRAINT validacoes_entrega_fk FOREIGN KEY (entrega_id) REFERENCES entregas (id) ON DELETE CASCADE,
  CONSTRAINT validacoes_professor_fk FOREIGN KEY (professor_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para criar um professor: cadastre a conta normalmente em /cadastro e depois rode:
--   UPDATE usuarios SET perfil = 'professor' WHERE email = 'email_do_professor@gmail.com';
