<?php

declare(strict_types=1);

use ELabore\Controllers\AcompanhamentoController;
use ELabore\Controllers\AlunoController;
use ELabore\Controllers\ArquivoController;
use ELabore\Controllers\AtividadeController;
use ELabore\Controllers\AuthController;
use ELabore\Controllers\ContaController;
use ELabore\Controllers\HealthController;
use ELabore\Controllers\ProfessorController;
use ELabore\Controllers\TurmaController;

$auth = new AuthController();
$professor = new ProfessorController();
$turmas = new TurmaController();
$atividades = new AtividadeController();
$contas = new ContaController();
$acompanhamento = new AcompanhamentoController();
$aluno = new AlunoController();
$arquivos = new ArquivoController();

return [
    'GET /health' => [new HealthController(), 'show'],

    // Acesso
    'GET /' => [$auth, 'home'],
    'GET /login' => [$auth, 'showLogin'],
    'POST /login' => [$auth, 'login'],
    'GET /cadastro' => [$auth, 'showRegister'],
    'POST /cadastro' => [$auth, 'register'],
    'POST /logout' => [$auth, 'logout'],

    // Professor
    'GET /professor' => [$professor, 'home'],

    'GET /professor/turmas' => [$turmas, 'index'],
    'POST /professor/turmas' => [$turmas, 'store'],
    'GET /professor/turmas/{id}' => [$turmas, 'show'],
    'POST /professor/turmas/{id}/editar' => [$turmas, 'update'],
    'POST /professor/turmas/{id}/excluir' => [$turmas, 'destroy'],
    'POST /professor/turmas/{id}/alunos' => [$turmas, 'addAluno'],
    'POST /professor/turmas/{id}/alunos/{aluno}/editar' => [$turmas, 'updateAluno'],
    'POST /professor/turmas/{id}/alunos/{aluno}/remover' => [$turmas, 'removeAluno'],

    'GET /professor/atividades' => [$atividades, 'index'],
    'GET /professor/atividades/nova' => [$atividades, 'create'],
    'POST /professor/atividades' => [$atividades, 'store'],
    'GET /professor/atividades/{id}/editar' => [$atividades, 'edit'],
    'POST /professor/atividades/{id}/editar' => [$atividades, 'update'],
    'POST /professor/atividades/{id}/status' => [$atividades, 'toggle'],
    'POST /professor/atividades/{id}/excluir' => [$atividades, 'destroy'],

    'GET /professor/contas' => [$contas, 'index'],
    'POST /professor/contas/{id}/vincular' => [$contas, 'vincular'],
    'POST /professor/contas/{id}/desvincular' => [$contas, 'desvincular'],

    'GET /professor/entregas' => [$acompanhamento, 'index'],
    'GET /professor/entregas/{id}' => [$acompanhamento, 'show'],
    'POST /professor/entregas/{id}/validar/{entrega}' => [$acompanhamento, 'validar'],

    // Aluno
    'GET /aluno' => [$aluno, 'home'],
    'GET /aluno/entregas' => [$aluno, 'entregas'],
    'GET /aluno/atividades/{id}' => [$aluno, 'atividade'],
    'GET /aluno/atividades/{id}/entregas/nova' => [$aluno, 'novaEntrega'],
    'POST /aluno/atividades/{id}/entregas' => [$aluno, 'storeEntrega'],

    // Arquivos enviados
    'GET /arquivos/{id}' => [$arquivos, 'download'],
];
