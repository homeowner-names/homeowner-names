<?php

namespace App\Support;

class NameParser
{
    /**
     * A lookup table used to normalize title variations
     * into a consistent, properly cased form.
     *
     * @var array<string, string>
     */
    protected array $titleNormalize = [
        'mr'     => 'Mr',
        'mister' => 'Mr',
        'mrs'    => 'Mrs',
        'ms'     => 'Ms',
        'dr'     => 'Dr',
        'prof'   => 'Prof',
    ];

    /**
     * Normalize a title string to a consistent, UK accepted form
     *
     * @param string $t - unprocessed title string from the CSV line
     * @return string - normalized, properly cased title
     */
    private function normTitle(string $t): string
    {
        $k = strtolower(trim($t));
        return $this->titleNormalize[$k] ?? trim($t);
    }

    /**
     * Determine whether a given token represents a single-letter initial.
     *
     * @param string $token - a single word or part of a name
     * @return bool - true if the token is an initial
     *
     * regex explanation:
     *   ^         - start of string
     *   [A-Za-z]  - a single alphabetic character
     *   \.?       - optionally followed by a period (the backslash escapes the dot)
     *   $         - end of string
     *
     */
    private function isInitial(string $token): bool
    {
        return (bool) preg_match('/^[A-Za-z]\\.?$/', $token);
    }

    /**
     * Split a combined homeowner name string into individual person segments,
     * this handles rows where multiple people are listed together
     *
     * @param string $name - raw name string from the CSV row
     * @return array<int, string> - array with one record per person
     */
    public function splitIntoSegments(string $name): array
    {
        // sequence of whitespaces into single whitespace
        $s = trim(preg_replace('/\s+/', ' ', $name));

        // title followed by and/& followed by title, then words
        $patternA = '/^(Mr|Mister|Mrs|Ms|Dr|Prof)\s+(?:and|&)\s+(Mr|Mister|Mrs|Ms|Dr|Prof)\s+(.+)$/i';
        if (preg_match($patternA, $s, $m)) {
            $t1 = $this->normTitle($m[1]);
            $t2 = $this->normTitle($m[2]);
            $tail = trim($m[3]);

            // array of the words after the titles
            $tailTokens = preg_split('/\s+/', $tail);

            if (count($tailTokens) >= 2) {
                $first = $tailTokens[0];
                // takes everything after the first element (name), join/implode the remained into surname
                $last = implode(' ', array_slice($tailTokens, 1));

                return ["{$t1} {$first} {$last}", "{$t2} {$last}"];
            }

            // only shared surname
            $last = $tailTokens[0];
            return ["{$t1} {$last}", "{$t2} {$last}"];
        }

        // title after "and" or "&" — "Mr Tom Staff and Mr John Doe"
        $parts = preg_split('/\s+(?:and|&)\s+(?=(Mr|Mister|Mrs|Ms|Dr|Prof)\b)/i', $s);

        if ($parts && count($parts) > 1) {
            $ok = true;

            foreach ($parts as $p) {
                // ensure each part starts with a recognized title
                if (!preg_match('/^(Mr|Mister|Mrs|Ms|Dr|Prof)\b/i', $p)) {
                    $ok = false;
                    break;
                }
            }

            // return both parts only if both segments are valid titled names
            if ($ok) {
                return $parts;
            }
        }

        return [$s];
    }

    /**
     * Parse a single person's name segment into components
     * title, first name (optional), initial (optional), surname
     *
     * @param  string  $segment - single name string "Mrs Jane McMaster"
     * @return array<string, string|null>
     *
     * @throws \InvalidArgumentException
     */
    public function parseSingleSegment(string $segment): array
    {
        // sequence of whitespaces into single whitespace, split string into array
        $s = trim(preg_replace('/\s+/', ' ', $segment));

        // ensure the name starts with a recognized title
        if (!preg_match('/^(Mr|Mister|Mrs|Ms|Dr|Prof)\s+(.*)$/i', $s, $m)) {
            throw new \InvalidArgumentException("No title found in segment: {$segment}");
        }

        $rawTitle = $m[1];
        $title = $this->normTitle($rawTitle);

        $rest = trim($m[2]);
        // split string on whitespace into further tokens
        $tokens = preg_split('/\s+/', $rest);

        $first = null; $initial = null; $last = null;

        if (count($tokens) === 1) {
            $last = $tokens[0];
        } elseif (count($tokens) === 2) {
            if ($this->isInitial($tokens[0])) {
                // "F." to "F"
                $initial = strtoupper($tokens[0][0]);
                $last = $tokens[1];
            } else {
                $first = $tokens[0];
                $last = $tokens[1];
            }
        } else {
            if ($this->isInitial($tokens[0])) {
                $initial = strtoupper($tokens[0][0]);
                $last = implode(' ', array_slice($tokens, 1));
            } else {
                $first = $tokens[0];
                $last = implode(' ', array_slice($tokens, 1));
            }
        }

        return [
            'title' => $title,
            'first_name' => $first,
            'initial' => $initial,
            'last_name' => $last,
        ];
    }
}
