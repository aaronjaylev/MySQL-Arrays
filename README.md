MySQLArrays PHP Library
Overview
MySQLArrays is a modern PHP library designed to simplify MySQL database interactions using PDO. It allows developers to construct queries using associative arrays, enhancing readability, maintainability, and security. The library supports common database operations like selecting, inserting, updating, and deleting rows, as well as advanced features like transactions and ENUM value retrieval. Built with PHP 8.3 compatibility, it leverages type hints, prepared statement caching, and robust error handling.
Key Features

Associative Array Queries: Build queries using arrays for conditions, fields, and ordering, reducing string concatenation errors.
PDO-Based: Uses PHP's PDO extension for secure, efficient database access with prepared statements.
Security: Escapes identifiers, validates inputs, and avoids raw SQL to prevent injection.
Performance: Caches prepared statements and ENUM values for faster execution.
Transactions: Supports atomic operations with beginTransaction, commit, and rollBack.
ENUM Support: Easily retrieve and generate HTML options for ENUM columns.
Type Safety: Includes type hints and return type declarations for better IDE support and fewer runtime errors.
Error Handling: Throws custom MySQLArraysException for clear, secure error reporting.

Installation

Requirements:

PHP 8.0 or higher
PDO MySQL extension (pdo_mysql)
MySQL or MariaDB database


Add to Your Project:

Copy MySQLArrays.php to your project directory.
Include the file using require_once:require_once 'MySQLArrays.php';




Optional (Composer):

If using Composer, place MySQLArrays.php in a directory (e.g., src/) and autoload it:{
    "autoload": {
        "psr-4": {
            "MySQLArrays\\": "src/"
        }
    }
}


Run composer dump-autoload.



Usage
Initialize the Library
Create an instance of MySQLArrays by passing your database credentials:
use MySQLArrays\MySQLArrays;

try {
    $db = new MySQLArrays(
        db_host: 'localhost',
        db_name: 'mydb',
        db_user: 'user',
        db_pass: 'password'
    );
} catch (MySQLArraysException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

Example 1: Select Rows
Retrieve rows from a table with conditions, specific fields, and ordering:
try {
    $results = $db->selectRows(
        tableName: 'users',
        whereArray: ['status' => 'active'],
        fieldNames: ['id', 'name', 'email'],
        orderArray: ['name' => 'ASC']
    );
    while ($row = $results->fetch()) {
        echo "{$row['name']} <{$row['email']}>\n";
    }
} catch (MySQLArraysException $e) {
    echo 'Query failed: ' . $e->getMessage();
}

Example 2: Insert a Row
Insert a new row and retrieve the inserted ID:
try {
    $userId = $db->insertRow('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => 'active'
    ]);
    echo "Inserted user ID: $userId\n";
} catch (MySQLArraysException $e) {
    echo 'Insert failed: ' . $e->getMessage();
}

Example 3: Update Rows
Update rows based on conditions:
try {
    $db->updateRows(
        tableName: 'users',
        valuesArray: ['status' => 'inactive', 'last_updated' => 'NOW()'],
        whereArray: ['id' => 1]
    );
    echo "User updated successfully\n";
} catch (MySQLArraysException $e) {
    echo 'Update failed: ' . $e->getMessage();
}

Example 4: Upsert (Insert or Update)
Insert a row or update it if it exists (requires a unique key):
try {
    $userId = $db->upsertRow(
        tableName: 'users',
        valuesArray: ['name' => 'Jane Doe', 'status' => 'active'],
        whereArray: ['email' => 'jane@example.com']
    );
    echo "Upserted user ID: $userId\n";
} catch (MySQLArraysException $e) {
    echo 'Upsert failed: ' . $e->getMessage();
}

Example 5: Transactions
Perform multiple operations atomically:
try {
    $db /

System: I notice the README.md content appears to be cut off in the middle of the transaction example. I'll complete the README with a corrected and full version, ensuring all examples are included and the content is polished. Below is the complete `README.md` for the `MySQLArrays` PHP library, with an overview, installation instructions, and comprehensive usage examples, including the transaction example that was truncated. The content is wrapped in the required `<xaiArtifact>` tag with a new UUID, as this is a distinct artifact from the previous one.

<xaiArtifact artifact_id="eafc0f08-2067-46ec-8d6a-118903d58927" artifact_version_id="7a286bcc-5cce-4c0f-9c26-93663f57dd3b" title="README.md" contentType="text/markdown">
# MySQLArrays PHP Library

## Overview

`MySQLArrays` is a modern PHP library designed to simplify MySQL database interactions using PDO. It enables developers to construct queries using associative arrays, improving code readability, maintainability, and security. The library supports common database operations such as selecting, inserting, updating, and deleting rows, along with advanced features like transactions, ENUM value retrieval, and prepared statement caching. Built for PHP 8.3 compatibility, it leverages type hints, robust error handling, and performance optimizations.

### Key Features
- **Associative Array Queries**: Build queries with arrays for conditions, fields, and ordering, reducing errors from string concatenation.
- **PDO-Based**: Uses PHP's PDO extension for secure and efficient database access with prepared statements.
- **Security**: Escapes identifiers, validates inputs, and prevents SQL injection by avoiding raw SQL fragments.
- **Performance**: Caches prepared statements and ENUM values to optimize query execution.
- **Transactions**: Supports atomic operations with `beginTransaction`, `commit`, and `rollBack` methods.
- **ENUM Support**: Easily retrieve ENUM column values and generate HTML `<option>` tags.
- **Type Safety**: Includes type hints and return type declarations for better IDE support and fewer runtime errors.
- **Error Handling**: Throws custom `MySQLArraysException` for clear, secure error reporting.

## Installation

### Requirements
- PHP 8.0 or higher
- PDO MySQL extension (`pdo_mysql`)
- MySQL or MariaDB database

### Steps
1. **Copy the Library**:
   - Download `MySQLArrays.php` and place it in your project directory (e.g., `src/`).
   - Include the file using `require_once`:
     ```php
     require_once 'src/MySQLArrays.php';
     ```

2. **Optional (Composer Autoloading)**:
   - If using Composer, add the library to your project by defining an autoload rule in `composer.json`:
     ```json
     {
         "autoload": {
             "psr-4": {
                 "MySQLArrays\\": "src/"
             }
         }
     }
     ```
   - Run `composer dump-autoload` to generate the autoloader.
   - Use the namespace in your code:
     ```php
     use MySQLArrays\MySQLArrays;
     ```

3. **Verify PDO**:
   - Ensure the `pdo_mysql` extension is enabled in your PHP configuration (`php.ini`):
     ```ini
     extension=pdo_mysql
     ```

## Usage

### Initialize the Library
Create an instance of `MySQLArrays` by passing your database credentials:

```php
use MySQLArrays\MySQLArrays;

try {
    $db = new MySQLArrays(
        db_host: 'localhost',
        db_name: 'mydb',
        db_user: 'user',
        db_pass: 'password'
    );
} catch (MySQLArraysException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

Example 1: Select Rows
Retrieve rows from a table with specific conditions, fields, and ordering:
try {
    $results = $db->selectRows(
        tableName: 'users',
        whereArray: ['status' => 'active'],
        fieldNames: ['id', 'name', 'email'],
        orderArray: ['name' => 'ASC']
    );
    while ($row = $results->fetch()) {
        echo "{$row['name']} <{$row['email']}>\n";
    }
} catch (MySQLArraysException $e) {
    echo 'Query failed: ' . $e->getMessage();
}

Example 2: Insert a Row
Insert a new row and retrieve the inserted ID:
try {
    $userId = $db->insertRow('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => 'active'
    ]);
    echo "Inserted user ID: $userId\n";
} catch (MySQLArraysException $e) {
    echo 'Insert failed: ' . $e->getMessage();
}

Example 3: Update Rows
Update rows based on specific conditions:
try {
    $db->updateRows(
        tableName: 'users',
        valuesArray: ['status' => 'inactive', 'last_updated' => 'NOW()'],
        whereArray: ['id' => 1]
    );
    echo "User updated successfully\n";
} catch (MySQLArraysException $e) {
    echo 'Update failed: ' . $e->getMessage();
}

Example 4: Upsert (Insert or Update)
Insert a row or update it if it exists, based on a unique key (e.g., email):
try {
    $userId = $db->upsertRow(
        tableName: 'users',
        valuesArray: ['name' => 'Jane Doe', 'status' => 'active'],
        whereArray: ['email' => 'jane@example.com']
    );
    echo "Upserted user ID: $userId\n";
} catch (MySQLArraysException $e) {
    echo 'Upsert failed: ' . $e->getMessage();
}

Note: Ensure a unique or primary key exists on the whereArray columns (e.g., email) for upsertRow to work.
Example 5: Transactions
Perform multiple operations atomically using transactions:
try {
    $db->beginTransaction();
    $userId = $db->insertRow('users', [
        'name' => 'Alice Smith',
        'email' => 'alice@example.com',
        'status' => 'active'
    ]);
    $db->insertRow('logs', [
        'user_id' => $userId,
        'action' => 'User created',
        'created_at' => 'NOW()'
    ]);
    $db->commit();
    echo "Transaction completed: User and log added\n";
} catch (MySQLArraysException $e) {
    $db->rollBack();
    echo 'Transaction failed: ' . $e->getMessage();
}

Example 6: Retrieve ENUM Values
Generate HTML <option> tags for an ENUM column:
try {
    $options = $db->makeOptions('users', 'status', 'active');
    echo "<select name='status'>\n$options</select>\n";
} catch (MySQLArraysException $e) {
    echo 'Failed to generate options: ' . $e->getMessage();
}

Output (assuming status ENUM has values active, inactive, pending):
<select name='status'>
<option selected>active</option>
<option>inactive</option>
<option>pending</option>
</select>

Example 7: Count Rows
Count rows matching specific conditions:
try {
    $count = $db->numRows('users', field: '*', whereArray: ['status' => 'active']);
    echo "Active users: $count\n";
} catch (MySQLArraysException $e) {
    echo 'Count failed: ' . $e->getMessage();
}

Example 8: Get Next Auto-Increment ID
Retrieve the next auto-increment ID for a table:
try {
    $nextId = $db->getNextId('users');
    echo "Next user ID: $nextId\n";
} catch (MySQLArraysException $e) {
    echo 'Failed to get next ID: ' . $e->getMessage();
}

Best Practices

Error Handling: Always wrap database operations in try/catch blocks to handle MySQLArraysException.
Security: Avoid passing user input directly to methods like selectRows or executeQuery. Sanitize inputs or use prepared statement parameters.
Unique Keys: For upsertRow, ensure the table has a unique or primary key on the columns specified in whereArray.
Performance: Leverage the library’s caching for prepared statements and ENUM values in high-traffic applications.
Testing: Write unit tests using PHPUnit to verify query behavior, especially for custom queries or edge cases.

Troubleshooting

Connection Errors: Verify database credentials and ensure pdo_mysql is enabled (php -m | grep pdo_mysql).
SQL Errors: Check error messages in MySQLArraysException for details. Enable PDO’s ERRMODE_EXCEPTION for debugging.
ENUM Issues: Ensure the column is defined as ENUM in the database schema.
Upsert Failures: Confirm the table has a unique index (e.g., CREATE UNIQUE INDEX idx_email ON users(email);).

Contributing
Contributions are welcome! Please submit issues or pull requests to the repository (link TBD). Follow PSR-12 coding standards and include unit tests for new features.
License
Licensed under the Apache License 2.0. See the MySQLArrays.php file for details.
Author

Aaron Jay Lev aaronjaylev@gmail.com
Copyright © 2013, 2025 Aaron Jay Lev

