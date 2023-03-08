# Exit-Codes

1. Command cancelled by user
2. Skeema config file not found
3. Linter exited with warnings
4. Linter exited with errors
5. Skeema diff exited with warnings
6. Skeema diff exited with errors
7. Skeema push exited because at least one table could not be updated due to use of unsupported features, or if the dry-run option was used and differences were found
8. Skeema push exited with fatal error.
9. Deployment-Check found existing laravel migrations
10. Deployment-Check found existing laravel sql dump file
11. Deployment-Check found running gh-ost migrations
