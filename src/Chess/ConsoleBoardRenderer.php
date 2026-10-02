<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Renders a Position as a simple console board.
 */
final class ConsoleBoardRenderer
{
    public function render(Position $position): string
    {
        $lines = [];

        for ($rank = 8; $rank >= 1; $rank--) {
            $cells = [];

            for ($file = 0; $file < 8; $file++) {
                $square = $position->getBoard()->getSquare(
                    (($rank - 1) * 8) + $file
                );
                $piece = $position->getPieceAt($square);

                $cells[] = $piece === null
                    ? '.'
                    : $this->symbol($piece);
            }

            $lines[] = $rank . ' ' . implode(' ', $cells);
        }

        $lines[] = '  a b c d e f g h';

        return implode(PHP_EOL, $lines);
    }

    private function symbol(Piece $piece): string
    {
        $symbol = $piece->type->value;

        return $piece->color === Color::WHITE
            ? strtoupper($symbol)
            : $symbol;
    }
}
