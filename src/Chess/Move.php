<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a chess move.
 *
 * A move represents one complete chess move resulting from a player's
 * decision. A move consists of one or more atomic
 * {@see PositionChange} objects that describe how the current
 * {@see Position} is changed.
 *
 * A move is not limited to moving a single piece from one square to
 * another. A single move may contain multiple position changes.
 *
 * Examples:
 *
 * A normal move:
 *
 *     e2 → e4
 *
 * consists of one MOVE position change.
 *
 * A capture:
 *
 *     Pawn e4 → d5
 *     Piece d5 → removed
 *
 * consists of one MOVE and one REMOVE position change.
 *
 * Castling:
 *
 *     King e1 → g1
 *     Rook h1 → f1
 *
 * consists of two MOVE position changes.
 *
 * En passant:
 *
 *     Pawn e5 → d6
 *     Captured pawn d5 → removed
 *
 * consists of one MOVE and one REMOVE position change.
 *
 * Promotion:
 *
 *     Pawn e7 → e8
 *     Pawn → Queen
 *
 * consists of one MOVE and one CHANGE position change.
 *
 * The move describes the resulting changes to the position. It does
 * not determine whether those changes are legal according to the
 * rules of chess, and it does not apply them to a {@see Position}.
 * Move validation and execution belong to higher-level domain logic.
 *
 * A move is immutable. Once created, its position changes cannot be
 * modified.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final readonly class Move
{
    /**
     * @var array<int, PositionChange>
     */
    public array $changes;

    /**
     * Creates a new chess move.
     *
     * A move must contain at least one position change because a move
     * without a position change does not alter the chess position.
     *
     * @param PositionChange ...$changes
     *        The atomic changes that make up the move.
     *
     * @throws \InvalidArgumentException
     *         If no position changes are provided.
     */
    public function __construct(PositionChange ...$changes)
    {
        if ($changes === []) {
            throw new \InvalidArgumentException(
                'A move must contain at least one position change.'
            );
        }

        $this->changes = $changes;
    }
}
