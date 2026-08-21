<?php
// api/get_acceptance_criteria_suggestions.php

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Nicht angemeldet.');
    }

    $type = strtoupper(trim($_GET['type'] ?? ''));
    $query = trim($_GET['query'] ?? '');
    $limit = min(50, max(5, (int) ($_GET['limit'] ?? 24)));

    $tokens = preg_split(
        '/[^\pL\pN]+/u',
        mb_strtolower($query),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    $tokens = array_values(array_unique(array_filter(
        $tokens,
        static fn ($value) => mb_strlen($value) >= 3
    )));

    $scoreParts = [];
    $params = [
        ':type_score' => $type,
        ':type_filter' => $type
    ];

    foreach (array_slice($tokens, 0, 12) as $index => $token) {
        $criterionKey = ':criterion_' . $index;
        $keywordKey = ':keyword_' . $index;
        $categoryKey = ':category_' . $index;

        $scoreParts[] = "(
            criterion_text LIKE {$criterionKey}
            OR keywords LIKE {$keywordKey}
            OR category LIKE {$categoryKey}
        )";

        $searchValue = '%' . $token . '%';
        $params[$criterionKey] = $searchValue;
        $params[$keywordKey] = $searchValue;
        $params[$categoryKey] = $searchValue;
    }

    $textScore = $scoreParts
        ? '(' . implode(' + ', $scoreParts) . ')'
        : '0';

    $sql = "
        SELECT
            id,
            requirement_type,
            category,
            criterion_text,
            keywords,
            requirement_type AS source_type, 
            usage_count,
            (
                CASE
                    WHEN requirement_type = :type_score THEN 100
                    ELSE 0
                END
                + usage_count
                + {$textScore}
            ) AS score
        FROM acceptance_criteria_templates
        WHERE is_active = 1
          AND (
              requirement_type = :type_filter
              OR requirement_type = 'ALL'
          )
        ORDER BY
            score DESC,
            usage_count DESC,
            category ASC,
            id ASC
        LIMIT {$limit}
    ";

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    echo json_encode(
        [
            'success' => true,
            'suggestions' => $statement->fetchAll(PDO::FETCH_ASSOC)
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $error) {
    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'error' => $error->getMessage()
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}
