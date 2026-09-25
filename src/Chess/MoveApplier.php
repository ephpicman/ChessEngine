<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Applies a move to a chess position.
 *
 * The applier is responsible only for applying the position changes
 * described by a {@see Move} to a {@see Position}.
 *
 * It does not determine whether the move is legal according to the
 * rules of chess. Chess move legality belongs to {@see MoveValidator}.
 *
 * A move is expected to have already been validated before it is
 * passed to this class.
 *
 * The applier performs only the checks required to safely apply the
 * described position changes to the current position.
 *
 * Supported position changes include:
 *
 * - MOVE   — moves a piece from one square to another.
 * - REMOVE — removes a piece from the position.
 * - CHANGE — replaces one piece with another piece.
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class MoveApplier
{
    /**
     * Applies a move to the given position.
     *
     * The move must already have passed chess-rule validation.
     *
     * @param Position $position
     *        The position to modify.
     *
     * @param Move $move
     *        The move to apply.
     *
     * @throws \LogicException
     *         If the position is incompatible with one of the
     *         position changes described by the move.
     */
    public function apply(
        Position $position,
        Move $move,
    ): void {
        foreach ($move->changes as $change) {
            match ($change->type) {
                PositionChangeType::MOVE =>
                    $this->applyMove($position, $change),

                PositionChangeType::REMOVE =>
                    $this->applyRemove($position, $change),

                PositionChangeType::CHANGE =>
                    $this->applyChange($position, $change),
            };
        }
    }

    /**
     * Applies a MOVE position change.
     */
    private function applyMove(
        Position $position,
        PositionChange $change,
    ): void {
        if ($change->from === null || $change->to === null) {
            throw new \LogicException(
                'A MOVE position change requires both from and to squares.'
            );
        }

        $piece = $position->getPieceAt($change->from);

        if ($piece !== $change->piece) {
            throw new \LogicException(
                "Piece '{$change->piece->id}' is not located on "
                . "{$change->from->notation()}."
            );
        }

        if ($position->isOccupied($change->to)) {
            throw new \LogicException(
                "Square {$change->to->notation()} is already occupied."
            );
        }

        $position->remove($change->from);
        $position->place($change->to, $change->piece);
    }

    /**
     * Applies a REMOVE position change.
     */
    private function applyRemove(
        Position $position,
        PositionChange $change,
    ): void {
        $square = $position->getSquareOf($change->piece);

        if ($square === null) {
            throw new \LogicException(
                "Piece '{$change->piece->id}' is not present in the position."
            );
        }

        $position->remove($square);
    }

    /**
     * Applies a CHANGE position change.
     */
    private function applyChange(
        Position $position,
        PositionChange $change,
    ): void {
        if ($change->replacement === null) {
            throw new \LogicException(
                'A CHANGE position change requires a replacement piece.'
            );
        }

        $square = $position->getSquareOf($change->piece);

        if ($square === null) {
            throw new \LogicException(
                "Piece '{$change->piece->id}' is not present in the position."
            );
        }

        $position->remove($square);
        $position->place($square, $change->replacement);
    }
}
