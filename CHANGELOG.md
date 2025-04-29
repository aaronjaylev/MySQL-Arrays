Changelog
All notable changes to the MySQLArrays PHP library are documented in this file.
The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.
[2.0.0] - 2025-04-29
Added

PDO Exclusivity: Fully transitioned to PDO for all database operations, removing dependency on deprecated mysql_* functions.
Transaction Support: Added beginTransaction, commit, and rollBack methods for atomic operations.
Prepared Statement Caching: Implemented caching of PDO prepared statements to improve performance for repeated queries.
ENUM Value Caching: Added caching for ENUM values in getEnumValues to reduce database queries.
Custom Exception: Introduced MySQLArraysException for consistent, secure error handling.
Type Hints and Return Types: Added PHP 8.3-compatible type hints and return type declarations for all methods.
PHPDoc Blocks: Included comprehensive PHPDoc documentation for all public methods.

Changed

Method Renames: Renamed methods for clarity and PSR-12 compliance:
RunQuery → executeQuery
MakeValuesArray → buildValuesArray
MakeWhereQuery, MakeOrderQuery, MakeFieldNames → makeWhereQuery, makeOrderQuery, makeFieldNames (camelCase for private methods)


Upsert Logic: Replaced AutoInsertUpdate with upsertRow, using MySQL’s ON DUPLICATE KEY UPDATE for efficiency.
Error Handling: Replaced die() with MySQLArraysException for all error conditions.
Array Syntax: Adopted short array syntax ([]) throughout the codebase.
Security Enhancements:
Removed support for raw SQL fragments in whereArray to prevent injection.
Added identifier escaping with PDO::quoteIdentifier for table and column names.
Improved input validation for query parameters and arrays.



Removed

Deprecated Functions: Eliminated all mysql_* functions (e.g., mysql_fetch_assoc, mysql_num_rows) and related methods:
Removed enum_select (replaced by getEnumValues).
Consolidated NextId and NextAutoNumber into getNextId.


Raw SQL Support: Disallowed raw SQL in whereArray for security reasons.

Fixed

Compatibility: Ensured full compatibility with PHP 8.0+ by removing deprecated features and leveraging modern PHP constructs.
Performance: Optimized queries (e.g., numRows now uses COUNT with PDO) and reduced redundant database calls.
Error Messages: Improved error reporting with detailed, secure messages via MySQLArraysException.

Breaking Changes

Removed mysql_* function support, requiring PDO and PHP 8.0+.
Renamed methods (RunQuery, AutoInsertUpdate, etc.) may break existing code.
Disallowed raw SQL in whereArray, requiring associative arrays for conditions.
Changed method signatures with type hints, potentially affecting loosely-typed code.
Replaced AutoInsertUpdate with upsertRow, requiring a unique key for operation.

Notes

Users upgrading from earlier versions must update their code to use PDO and adapt to renamed methods.
Ensure tables have unique or primary keys for upsertRow to function correctly.
Test thoroughly when integrating with existing applications due to breaking changes.

