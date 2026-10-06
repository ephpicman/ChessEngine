# Position Descriptor

`PositionDescriptor` extracts deterministic, normalised facts from a chess position for use by the decision system. It does not choose moves or evaluate a position as good or bad.

All descriptor values are normalised to `[0, 1]`.

## Current features

### Material score

Measures the material currently placed on the board for each colour.

Default values:

- Queen: 9
- Rook: 5
- Bishop: 3
- Knight: 3
- Pawn: 1
- King: 0

The default normalisation maximum is `103`.

### Occupied-square score

Measures how much of the board is currently occupied by pieces of a colour:

```text
occupied squares / 64
```

Each occupied square counts once. Piece value is irrelevant to this feature.

For the initial position each colour has 16 occupied squares:

```text
16 / 64 = 0.25
```

### Legal-destination score

Measures how much of the board is currently available as a legal destination for at least one piece of a colour:

```text
unique legal destination squares / 64
```

The metric counts **destination squares, not moves**. If several legal moves end on the same square, that square contributes only once.

For the standard initial position each colour has 20 unique legal destination squares:

```text
20 / 64 = 0.3125
```

Legal destinations are generated through `LegalMoveGenerator`, so the metric uses the same legality rules as the rest of the chess system.

## History dependency

Most descriptor features depend only on the current position. Legal-destination calculation is different because chess legality contains history-dependent cases, notably castling and en-passant.

`PositionDescriptor` therefore accepts an optional `DecisionHistory`:

- when analysing a real game position, pass the game's history for exact legal destinations;
- when no history is supplied, an empty history is used.

This does not turn the descriptor into decision logic. The descriptor still reports a measurable property; it simply uses the information required to determine that property correctly.

## Snapshot behaviour

All features are calculated during construction. Changing the supplied `Position` afterwards does not change values already stored in the descriptor.

A new `PositionDescriptor` must be created when a new hypothetical or real position needs to be described.
