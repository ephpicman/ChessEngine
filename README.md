# Chess Engine

A chess engine and decision system being developed from the ground up.

The long-term objective is to build a genuinely strong chess-playing system, with the eventual ambition of reaching a level capable of competing with very strong established engines.

## Project Direction

The project is being developed in stages:

1. Build a correct and reliable chess domain.
2. Establish a clean game and decision architecture.
3. Validate the rules and game flow through automated tests.
4. Introduce machine decision-making.
5. Build progressively stronger search and evaluation algorithms.
6. Benchmark engine strength and performance.
7. Iterate through controlled experiments rather than unmeasured tuning.

The current codebase is primarily a **correct chess rules and game-flow foundation**. The next major development phase is decision-making and engine strength.

## Current Implementation

### Chess domain

The project already has explicit domain objects for the main chess concepts, including:

- Board
- Position
- Square
- Piece
- Pieces
- Color
- PieceType
- File
- Rank
- Move
- PositionChange

The domain is represented explicitly rather than as a collection of procedural helpers.

### Position and board

The project separates the board from the complete game position.

An `InitialPosition` provides the standard starting position, while the position model provides the basis for normal games, deterministic test positions, and future search.

### Pieces

The domain explicitly represents:

- Pawn
- Knight
- Bishop
- Rook
- Queen
- King

Piece ownership is represented through `Color`.

### Moves

Moves are represented as domain objects.

Console notation is only an input representation. Examples:

```
e2e4
g1f3
e7e8q
```

Promotion supports:

```
q
r
b
n
```

### Move validation

`MoveValidator` determines whether a requested move is legal.

Validation is separated from:

- input parsing;
- game orchestration;
- move application;
- board rendering.

This means the same rules layer can eventually serve human players, bots, search, and automated tests.

### Move application

`MoveApplier` applies an accepted move to the current position.

The distinction is intentional:

```
Is the move legal?
        ↓
MoveValidator

Apply the move
        ↓
MoveApplier
```

This separation is important for future search, where many candidate moves must be applied to temporary positions.

## Player and Decision Architecture

### Player abstraction

The project contains a `Player` abstraction.

A player produces a `Decision` from the current game state.

The current implementation is:

- `HumanPlayer`

The architecture is intended to support future implementations such as:

- `HumanPlayer`
- `BotPlayer`
- `EnginePlayer`

The game loop does not need to know how a decision was produced.

### Human player

`HumanPlayer` currently provides console interaction.

It:

1. receives the current position;
2. renders the board when a renderer is available;
3. prompts the side to move;
4. reads console input;
5. parses the command;
6. creates the corresponding decision;
7. repeats when input is invalid.

Supported move examples:

```
e2e4
g1f3
b1c3
```

Promotion:

```
e7e8q
```

### Game commands

Resignation:

```
resign
```

Draw acceptance:

```
accept
accept draw
```

Draw offer attached to a move:

```
e2e4 draw
```

### Decision model

Player actions are represented through the `Decision` abstraction rather than being handled as arbitrary strings inside the game loop.

The associated `DecisionType` classifies actions such as:

- moves;
- resignation;
- draw acceptance;
- draw offers.

### Decision history

`DecisionHistory` stores game decisions and related state, including:

- decisions;
- colours;
- move count;
- last move;
- whether a player has moved;
- turn information.

This provides a central source for game-history information and gives the future engine a place to build on for features such as repetition detection, game records, analysis, and time usage.

## Game Orchestration

`Game` coordinates the game flow:

```
Player
  ↓
Decision
  ↓
Move validation
  ↓
Move application
  ↓
Position / history update
  ↓
Next player
```

The game continues until a terminal game decision is reached, such as resignation or accepted draw.

The important architectural boundary is that `Game` does not need to know how a player arrived at a decision.

## Console Interface

The console entry point is:

```
bin/chess.php
```

It creates the initial position, renderer, players, and game, then starts the game.

The console currently exists primarily for:

- exercising the domain;
- manually playing games;
- validating game flow;
- debugging rules;
- testing future players.

The UI is not the core architectural concern.

## Rendering

`ConsoleBoardRenderer` is responsible for presentation only.

It does not own:

- move validation;
- move decisions;
- move application;
- game state.

## Testing

The project contains automated unit and integration tests.

Integration coverage includes game-flow tests such as:

- `GameFlowTest`
- `ConsoleGameFlowTest`

The tests provide a safety net while the engine layer becomes more complex.

Existing tests should remain green as new decision-making functionality is introduced.

## Current Time-Control Status

Timed games are **not currently implemented**.

The current human-player input is synchronous and can wait indefinitely for console input.

There is currently no:

- chess clock;
- remaining time;
- increment;
- move elapsed-time tracking;
- timeout / flag fall;
- time-based game termination.

Time control is therefore a future game-level capability, not something currently provided by `HumanPlayer`.

## Current Engine Status

The project should not yet be described as a strong chess engine.

The current foundation is:

```
Chess Domain
      ↓
Position
      ↓
Move
      ↓
Validation
      ↓
Application
      ↓
Game
      ↓
Player abstraction
```

The next major layer is:

```
Bot / Engine
      ↓
Candidate moves
      ↓
Search
      ↓
Evaluation
      ↓
Decision
```

The existing architecture provides the boundaries required to introduce this layer without putting search logic into the game itself.

# Architecture Direction

The current architecture can be viewed as:

```
┌─────────────────────────────┐
│          Interface          │
│     Console / Future UI     │
└──────────────┬──────────────┘
               │
┌──────────────▼──────────────┐
│            Game             │
│      Game orchestration     │
└──────────────┬──────────────┘
               │
┌──────────────▼──────────────┐
│       Player / Decision     │
│ Human / Bot / Engine        │
└──────────────┬──────────────┘
               │
┌──────────────▼──────────────┐
│        Chess Domain         │
│ Position / Move / Pieces    │
│ Board / Rules / Validation  │
└─────────────────────────────┘
```

The future search subsystem should sit behind the player/decision boundary rather than leaking search concerns into the core game model.

# Engineering Principles

## Correctness before strength

The rules layer must remain reliable before search strength is aggressively developed.

## Separation of concerns

The following concerns should remain distinct:

- input;
- game orchestration;
- chess rules;
- move application;
- decision-making;
- search;
- evaluation;
- rendering.

## Measurement over intuition

Engine-strength changes should be measured.

A useful development cycle is:

1. establish a baseline;
2. make one isolated change;
3. benchmark;
4. compare;
5. check for regressions.

## No human-computation constraint

The engine should not be designed around human calculation limits.

Human-oriented heuristics are a secondary research output. The engine itself should use algorithms justified by strength, correctness, and measured performance.

# Upcoming Updates

The next phase moves the project from a validated chess-game foundation toward an actual decision-making engine.

## **1. Timed Games**

Add proper chess time controls.

This should be a game-level capability rather than a timeout bolted onto `HumanPlayer`.

The intended model should support:

- initial clock time;
- remaining time for each player;
- move start time;
- move elapsed time;
- increment;
- timeout / flag fall;
- game termination on time.

For example:

```
10+5
```

means:

```
10 minutes initial time
+
5 seconds increment after each move
```

The same timing infrastructure should work for human and bot players.

Eventually, engine search will also need to become time-aware so that the engine can decide how much of the available clock to spend on a move.

## **2. First Generation Bot**

Introduce the **first generation of the chess bot**.

The first bot is not intended to be the final engine. Its immediate purpose is to establish the complete machine-decision pipeline:

```
Position
   ↓
Candidate moves
   ↓
Search
   ↓
Evaluation
   ↓
Best move
   ↓
Decision
   ↓
Game
```

The first generation should establish a measurable baseline for future improvements.

Its goals are:

- a functioning machine player;
- a clean decision pipeline;
- a measurable search baseline;
- separation between game logic and engine logic;
- a foundation for progressively stronger algorithms.

# Future Engine Development

Once the first bot is operational, engine development can progressively move toward stronger search and evaluation.

## Search

Potential areas include:

- negamax / minimax;
- alpha-beta pruning;
- iterative deepening;
- transposition tables;
- move ordering;
- quiescence search;
- aspiration windows;
- principal variation search;
- killer moves;
- history heuristics;
- late move reductions;
- null-move pruning;
- search extensions.

## Evaluation

Potential areas include:

- material;
- piece-square information;
- mobility;
- pawn structure;
- king safety;
- passed pawns;
- bishop pair;
- rook activity;
- positional features;
- endgame-specific evaluation.

## Engine infrastructure

Eventually:

- Zobrist hashing;
- transposition-table management;
- repetition detection;
- search statistics;
- principal variation reporting;
- configurable search limits;
- time-aware search;
- engine-vs-engine matches;
- automated strength testing.

# Research and Benchmarking

As the engine develops, significant changes should be treated as experiments.

Each change should ideally answer:

```
What changed?
Why?
What hypothesis does it test?
How was it measured?
What happened to strength?
What happened to performance?
Did anything regress?
```

This keeps the engine development measurable and prevents the codebase from becoming a collection of untested heuristics.

# Current Milestone

The project has progressed beyond a raw chess-rules prototype.

The existing foundation provides:

- explicit chess domain modelling;
- board and position representation;
- piece modelling;
- move representation;
- move validation;
- move application;
- player abstraction;
- human console player;
- decision model;
- decision history;
- game orchestration;
- console rendering;
- automated tests;
- integration-level game-flow validation.

The two immediate milestones are:

1. **Timed Games**
2. **First Generation Bot**

These two features move the project from a playable chess-domain implementation toward an actual chess decision system.

# Long-Term Direction

```
Correct Chess Domain
        ↓
Stable Game Architecture
        ↓
Timed Game Infrastructure
        ↓
First Generation Bot
        ↓
Search Baseline
        ↓
Evaluation Baseline
        ↓
Benchmarking Infrastructure
        ↓
Search Improvements
        ↓
Evaluation Improvements
        ↓
Automated Engine Testing
        ↓
Progressive Strength Improvements
        ↓
High-Strength Chess Engine
```

The architecture should evolve according to measured engine requirements rather than premature abstraction.

The ultimate goal is a strong, testable, measurable chess decision system.
