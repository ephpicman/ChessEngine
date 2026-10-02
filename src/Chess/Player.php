<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

interface Player
{
    public function decide(
        DecisionHistory $decisionHistory,
        Position $position,
    ): Decision;
}
