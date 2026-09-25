<?php

interface Player
{
    public function decide(
        DecisionHistory $decisionHistory,
        Position $position,
    ): Decision;
}
