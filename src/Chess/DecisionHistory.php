<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents the history of decisions made during a chess game.
 *
 * Each decision is associated with the color of the player who made it.
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class DecisionHistory
{
    /**
     * @var array<int, array{color: Color, decision: Decision}>
     */
    private array $decisions = [];

    public function add(Color $color, Decision $decision): void
    {
        $this->decisions[] = [
            'color' => $color,
            'decision' => $decision,
        ];
    }

    /**
     * @return array<int, array{color: Color, decision: Decision}>
     */
    public function all(): array
    {
        return $this->decisions;
    }

    public function count(): int
    {
        return count($this->decisions);
    }

    /**
     * Returns the number of moves made in the game.
     *
     * @return int
     */
    public function moveCount(): int
    {
        $count = 0;

        foreach ($this->decisions as $entry) {
            if ($entry['decision']->move !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns the last move made in the game.
     *
     * @return Move|null
     */
    public function lastMove(): ?Move
    {
        for ($index = count($this->decisions) - 1; $index >= 0; $index--) {
            $move = $this->decisions[$index]['decision']->move;

            if ($move !== null) {
                return $move;
            }
        }

        return null;
    }

    /**
     * Determines whether the specified piece has moved previously.
     *
     * Piece identity is determined by the Piece object itself.
     *
     * @param Piece $piece
     *
     * @return bool
     */
    public function hasMoved(Piece $piece): bool
    {
        foreach ($this->decisions as $entry) {
            $move = $entry['decision']->move;

            if ($move === null) {
                continue;
            }

            foreach ($move->changes as $change) {
                if (
                    $change->type === PositionChangeType::MOVE
                    && $change->piece === $piece
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the color whose turn it is.
     *
     * Chess starts with White.
     */
    public function turn(): Color
    {
        return $this->moveCount() % 2 === 0
            ? Color::WHITE
            : Color::BLACK;
    }
}
