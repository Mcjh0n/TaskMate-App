<?php

if ($argc !== 2 || ! is_file($argv[1]) || ! is_readable($argv[1])) {
    fwrite(STDERR, "Usage: php scripts/sus-score.php <anonymous-responses.csv>\n");
    exit(2);
}

$handle = fopen($argv[1], 'rb');
$header = fgetcsv($handle);
$expected = array_map(fn (int $n): string => 'q'.$n, range(1, 10));
if ($header !== $expected) {
    fwrite(STDERR, "CSV header must be q1,q2,q3,q4,q5,q6,q7,q8,q9,q10\n");
    exit(2);
}
$scores = [];
$rowNumber = 1;
while (($row = fgetcsv($handle)) !== false) {
    $rowNumber++;
    if (count($row) !== 10) {
        fwrite(STDERR, "Invalid response row {$rowNumber}.\n");
        exit(2);
    }
    $score = 0;
    foreach ($row as $index => $value) {
        if (! ctype_digit((string) $value) || (int) $value < 1 || (int) $value > 5) {
            fwrite(STDERR, "Responses must be integers from 1 to 5 (row {$rowNumber}).\n");
            exit(2);
        }
        $response = (int) $value;
        $score += $index % 2 === 0 ? $response - 1 : 5 - $response;
    }
    $scores[] = $score * 2.5;
    printf("Response %d: %.1f\n", count($scores), end($scores));
}
fclose($handle);
if ($scores === []) {
    fwrite(STDERR, "CSV contains no responses.\n");
    exit(2);
}
printf("Mean: %.2f\n", array_sum($scores) / count($scores));
