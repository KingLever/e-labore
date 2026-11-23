<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Response;

final class ProfessorController
{
    /** @return array<string, mixed> */
    public function home(): array
    {
        Auth::exigir('professor');

        return Response::view('professor/home', ['titulo' => 'Início']);
    }
}
