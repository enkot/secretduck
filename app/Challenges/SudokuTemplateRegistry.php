<?php

namespace App\Challenges;

use Illuminate\Validation\ValidationException;

final class SudokuTemplateRegistry
{
    /** @return array{key: string, label: string, difficulty: string, givens: array<int, int|null>, solution: array<int, int>} */
    public function get(string $key): array
    {
        $template = collect($this->all())->firstWhere('key', $key);

        if ($template === null) {
            throw ValidationException::withMessages(['configuration.template' => 'Choose a valid Sudoku template.']);
        }

        return $template;
    }

    /** @return array<int, array{key: string, label: string, difficulty: string, givens: array<int, int|null>, solution: array<int, int>}> */
    public function all(): array
    {
        return [
            $this->template('garden-path', 'Garden path', 'gentle', [1, null, 3, null, null, 4, null, 2, 2, null, 4, null, null, 3, null, 1], [1, 2, 3, 4, 3, 4, 1, 2, 2, 1, 4, 3, 4, 3, 2, 1]),
            $this->template('first-dance', 'First dance', 'gentle', [null, 2, null, 4, 3, null, 1, null, null, 1, null, 3, 4, null, 2, null], [1, 2, 3, 4, 3, 4, 1, 2, 2, 1, 4, 3, 4, 3, 2, 1]),
            $this->template('confetti', 'Confetti', 'gentle', [2, null, null, 3, null, 3, 2, null, null, 2, 3, null, 3, null, null, 2], [2, 1, 4, 3, 4, 3, 2, 1, 1, 2, 3, 4, 3, 4, 1, 2]),
            $this->template('champagne', 'Champagne', 'gentle', [null, 1, 4, null, 4, null, null, 1, 1, null, null, 4, null, 4, 1, null], [2, 1, 4, 3, 4, 3, 2, 1, 1, 2, 3, 4, 3, 4, 1, 2]),
            $this->template('moonlight', 'Moonlight', 'gentle', [4, null, 2, null, null, 2, null, 4, 2, null, 4, null, null, 4, null, 2], [4, 3, 2, 1, 1, 2, 3, 4, 2, 1, 4, 3, 3, 4, 1, 2]),
            $this->template('starlight', 'Starlight', 'gentle', [null, 3, null, 1, 1, null, 3, null, null, 1, null, 3, 3, null, 1, null], [4, 3, 2, 1, 1, 2, 3, 4, 2, 1, 4, 3, 3, 4, 1, 2]),
            $this->template('ribbon', 'Ribbon', 'bright', [1, null, null, 4, null, 4, 1, null, null, 1, 4, null, 4, null, null, 1], [1, 2, 3, 4, 3, 4, 1, 2, 2, 1, 4, 3, 4, 3, 2, 1]),
            $this->template('keepsake', 'Keepsake', 'bright', [null, 2, 3, null, 3, null, null, 2, 2, null, null, 3, null, 3, 2, null], [1, 2, 3, 4, 3, 4, 1, 2, 2, 1, 4, 3, 4, 3, 2, 1]),
            $this->template('sparkler', 'Sparkler', 'bright', [2, null, 4, null, null, 3, null, 1, 1, null, 3, null, null, 4, null, 2], [2, 1, 4, 3, 4, 3, 2, 1, 1, 2, 3, 4, 3, 4, 1, 2]),
            $this->template('bouquet', 'Bouquet', 'bright', [null, 1, null, 3, 4, null, 2, null, null, 2, null, 4, 3, null, 1, null], [2, 1, 4, 3, 4, 3, 2, 1, 1, 2, 3, 4, 3, 4, 1, 2]),
            $this->template('afterglow', 'Afterglow', 'bright', [4, null, null, 1, null, 2, 3, null, null, 1, 4, null, 3, null, null, 2], [4, 3, 2, 1, 1, 2, 3, 4, 2, 1, 4, 3, 3, 4, 1, 2]),
            $this->template('vows', 'Vows', 'bright', [null, 3, 2, null, 1, null, null, 4, 2, null, null, 3, null, 4, 1, null], [4, 3, 2, 1, 1, 2, 3, 4, 2, 1, 4, 3, 3, 4, 1, 2]),
        ];
    }

    /** @param array<int, int|null> $givens
     * @param  array<int, int>  $solution
     * @return array{key: string, label: string, difficulty: string, givens: array<int, int|null>, solution: array<int, int>}
     */
    private function template(string $key, string $label, string $difficulty, array $givens, array $solution): array
    {
        return compact('key', 'label', 'difficulty', 'givens', 'solution');
    }
}
