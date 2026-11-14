# SUS scoring helper

`sus-format-example-only-not-results.csv` contains invented values only to illustrate the required `q1` through `q10` column format. **FORMAT EXAMPLE ONLY, NOT RESULTS.** Do not treat its numbers as participant responses or findings.

Run the standalone scorer with an anonymous CSV path:

```text
php scripts/sus-score.php path/to/anonymous-responses.csv
```

The script reads the CSV and calculates the standard SUS score without accessing the TaskMate database.
