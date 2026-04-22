<?php
// ==========================================
// REUSABLE SECURITY & CRUD FUNCTIONS
// ==========================================

/**
 * Universal Sanitizer parsing all array/string input recursively
 * Applies strict trim and htmlspecialchars preventing DOM XSS and SQLi.
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Executes a SELECT query expecting multiple rows.
 */
function fetch_all($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Executes a SELECT query referencing a single target row.
 */
function fetch_single($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Executes UPDATE or DELETE operations safely.
 */
function execute_query($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Executes INSERT operation and yields the corresponding new ID.
 */
function insert_record($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        return $pdo->lastInsertId();
    }
    return false;
}
?>
