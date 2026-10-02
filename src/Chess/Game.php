<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

use Closure;
use LogicException;

/**
 * Orchestrates a chess game between two Player implementations.
 *
 * The game asks the player whose turn it is for a Decision, validates
 * moves, applies valid moves, records accepted decisions, and retries
 * invalid decisions without changing the game state.
 */
final class Game
{
    private DecisionHistory $history;
    private bool $drawOffered = false;

    public function __construct(
        private readonly Position $position,
        private readonly Player $whitePlayer,
        private readonly Player $blackPlayer,
        private readonly MoveValidator $validator = new MoveValidator(),
        private readonly MoveApplier $applier = new MoveApplier(),
        private readonly ?Closure $output = null,
    ) {
        $this->history = new DecisionHistory();
    }

    /**
     * Runs the game until resignation or an accepted draw.
     */
    public function play(): DecisionHistory
    {
        while (true) {
            $color = $this->history->turn();
            $player = $color === Color::WHITE
                ? $this->whitePlayer
                : $this->blackPlayer;

            $decision = $player->decide($this->history, $this->position);

            if ($decision->type === DecisionType::RESIGN) {
                $this->history->add($color, $decision);
                $this->write(sprintf('%s resigned.', $this->colorName($color)));
                break;
            }

            if ($decision->type === DecisionType::ACCEPT_DRAW) {
                if (!$this->drawOffered) {
                    $this->write('No draw offer is currently active.');
                    continue;
                }

                $this->history->add($color, $decision);
                $this->write('Draw accepted.');
                break;
            }

            if ($decision->move === null) {
                $this->write('Invalid decision: a move is required.');
                continue;
            }

            if (!$this->validator->validate(
                $this->position,
                $this->history,
                $decision->move,
            )) {
                $this->write('Illegal move.');
                continue;
            }

            $this->applier->apply($this->position, $decision->move);
            $this->history->add($color, $decision);

            $this->drawOffered = $decision->type === DecisionType::OFFER_DRAW;

            $this->write(sprintf(
                '%s played %s%s.',
                $this->colorName($color),
                $this->moveDescription($decision->move),
                $decision->type === DecisionType::OFFER_DRAW ? ' and offered a draw' : '',
            ));
        }

        return $this->history;
    }

    public function position(): Position
    {
        return $this->position;
    }

    public function history(): DecisionHistory
    {
        return $this->history;
    }

    private function write(string $message): void
    {
        if ($this->output !== null) {
            ($this->output)($message);
        }
    }

    private function colorName(Color $color): string
    {
        return $color === Color::WHITE ? 'White' : 'Black';
    }

    private function moveDescription(Move $move): string
    {
        foreach ($move->changes as $change) {
            if ($change->type === PositionChangeType::MOVE) {
                return $change->from->notation() . $change->to->notation();
            }
        }

        throw new LogicException('A move must contain a MOVE change.');
    }
}
