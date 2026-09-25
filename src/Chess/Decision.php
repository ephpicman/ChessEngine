<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a player's decision during a chess game.
 *
 * A decision describes an action chosen by a player. The kind of
 * action is represented by {@see DecisionType}, while a
 * {@see Move} is provided when the decision involves making a move
 * on the chessboard.
 *
 * The supported decision types are:
 *
 * - {@see DecisionType::MOVE}
 *   The player makes a chess move.
 *
 * - {@see DecisionType::OFFER_DRAW}
 *   The player makes a chess move and offers a draw after that move.
 *
 * - {@see DecisionType::RESIGN}
 *   The player resigns the game.
 *
 * - {@see DecisionType::ACCEPT_DRAW}
 *   The player accepts a currently valid draw offer.
 *
 * A MOVE or OFFER_DRAW decision must contain a {@see Move}.
 * A RESIGN or ACCEPT_DRAW decision must not contain a Move.
 *
 * This class validates only the structural consistency between the
 * decision type and its associated data. It does not determine
 * whether the move is legal, whether a draw may be offered, whether
 * a draw offer is currently active, or whether resignation or draw
 * acceptance is permitted in the current game state.
 *
 * Those conditions depend on the current game state and belong to
 * higher-level game logic.
 *
 * In accordance with the FIDE Laws of Chess, a draw offer is made
 * after the player has made a move and before pressing the clock.
 * Therefore, an OFFER_DRAW decision is associated with the Move that
 * precedes the offer.
 *
 * A rejection of a draw offer is not represented by a separate
 * decision type. Continuing the game with a MOVE is sufficient to
 * represent the opponent's continuation after rejecting the offer.
 *
 * The class is immutable. Once a decision has been created, its type
 * and associated move cannot be changed.
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final readonly class Decision
{
    /**
     * Creates a new player decision.
     *
     * The presence of a Move must correspond to the decision type:
     *
     * - MOVE requires a Move.
     * - OFFER_DRAW requires a Move.
     * - RESIGN must not contain a Move.
     * - ACCEPT_DRAW must not contain a Move.
     *
     * @param DecisionType $type
     *        The type of decision.
     *
     * @param Move|null $move
     *        The move associated with the decision, when applicable.
     *
     * @throws \InvalidArgumentException
     *         If the decision type and Move are structurally
     *         inconsistent.
     */
    public function __construct(
        public DecisionType $type,
        public ?Move $move = null,
    ) {
        $requiresMove = match ($type) {
            DecisionType::MOVE,
            DecisionType::OFFER_DRAW => true,

            DecisionType::RESIGN,
            DecisionType::ACCEPT_DRAW => false,
        };

        if ($requiresMove && $move === null) {
            throw new \InvalidArgumentException(
                "Decision type '{$type->value}' requires a move."
            );
        }

        if (!$requiresMove && $move !== null) {
            throw new \InvalidArgumentException(
                "Decision type '{$type->value}' must not contain a move."
            );
        }
    }
}
