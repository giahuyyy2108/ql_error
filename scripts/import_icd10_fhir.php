<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Chỉ được chạy script từ CLI.\n");
    exit(1);
}
if ($argc < 2 || !is_file($argv[1])) {
    fwrite(STDERR, "Cách dùng: php scripts/import_icd10_fhir.php <CodeSystem.json>\n");
    exit(1);
}

require dirname(__DIR__) . '/conf/config.php';
require dirname(__DIR__) . '/web_src/common/mysql.php';

$document = json_decode(file_get_contents($argv[1]), true);
if (!is_array($document) || ($document['resourceType'] ?? '') !== 'CodeSystem' || empty($document['concept'])) {
    fwrite(STDERR, "Tệp không phải FHIR CodeSystem ICD-10 hợp lệ.\n");
    exit(1);
}

$version = isset($document['version']) ? (string) $document['version'] : '2026-07-01';
$source = isset($document['url']) ? (string) $document['url'] : 'http://fhir.hl7.org.vn/core/CodeSystem/vn-icd10-cs';
$artifactSha256 = hash_file('sha256', $argv[1]);
$rows = array();

$flatten = function (array $concepts, $parentCode = null) use (&$flatten, &$rows, $version, $source) {
    foreach ($concepts as $concept) {
        $properties = array();
        foreach ($concept['property'] ?? array() as $property) {
            foreach ($property as $key => $value) {
                if (strpos($key, 'value') === 0) {
                    $properties[$property['code']] = $value;
                    break;
                }
            }
        }
        $english = null;
        foreach ($concept['designation'] ?? array() as $designation) {
            if (($designation['language'] ?? '') === 'en') {
                $english = $designation['value'] ?? null;
                break;
            }
        }
        $rows[] = array(
            'code' => trim((string) ($concept['code'] ?? '')),
            'display_vi' => trim((string) ($concept['display'] ?? '')),
            'display_en' => $english,
            'definition_en' => $concept['definition'] ?? null,
            'level' => $properties['level'] ?? null,
            'parent_code' => $parentCode,
            'chapter_id' => $properties['chapter-id'] ?? null,
            'chapter_code' => $properties['chapter-code'] ?? null,
            'section_id' => $properties['section-id'] ?? null,
            'type_code' => $properties['type-code'] ?? null,
            'is_leaf' => !empty($properties['leaf']) ? 1 : 0,
            'is_active' => !empty($properties['inactive']) ? 0 : 1,
            'coding_guidance' => $properties['coding-guidance'] ?? null,
            'coding_guidance_en' => $properties['coding-guidance-en'] ?? null,
            'properties_json' => json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'version' => $version,
            'source' => $source
        );
        if (!empty($concept['concept'])) {
            $flatten($concept['concept'], $concept['code']);
        }
    }
};
$flatten($document['concept']);

$codes = array_column($rows, 'code');
if (count($rows) < 15000 || count($codes) !== count(array_unique($codes)) || in_array('', $codes, true)) {
    fwrite(STDERR, "Dữ liệu không đạt kiểm tra số lượng, mã rỗng hoặc mã trùng.\n");
    exit(1);
}

$db = new db_mysql();
$db->connect();
global $connect;
$connect->begin_transaction();

try {
    $connect->query('SET FOREIGN_KEY_CHECKS = 0');
    $connect->query('DELETE FROM icd10');
    $statement = $connect->prepare(
        'INSERT INTO icd10
         (code, display_vi, display_en, definition_en, level, parent_code, chapter_id, chapter_code,
          section_id, type_code, is_leaf, is_active, coding_guidance, coding_guidance_en,
          properties_json, version, source)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($rows as $row) {
        $statement->bind_param(
            'ssssssssssiisssss',
            $row['code'], $row['display_vi'], $row['display_en'], $row['definition_en'], $row['level'],
            $row['parent_code'], $row['chapter_id'], $row['chapter_code'], $row['section_id'],
            $row['type_code'], $row['is_leaf'], $row['is_active'], $row['coding_guidance'],
            $row['coding_guidance_en'], $row['properties_json'], $row['version'], $row['source']
        );
        $statement->execute();
    }
    $statement->close();
    $orphanResult = $connect->query(
        'SELECT COUNT(*) AS total FROM icd10 child
         LEFT JOIN icd10 parent ON parent.code = child.parent_code
         WHERE child.parent_code IS NOT NULL AND parent.code IS NULL'
    )->fetch_assoc();
    if ((int) $orphanResult['total'] !== 0) {
        throw new RuntimeException('Dữ liệu có mã cha không tồn tại.');
    }
    $activeCodes = count(array_filter($rows, function ($row) { return $row['is_active'] === 1; }));
    $totalCodes = count($rows);
    $metaStatement = $connect->prepare(
        'INSERT INTO icd10_import_meta
         (version, source_url, artifact_sha256, total_codes, active_codes)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE source_url=VALUES(source_url), artifact_sha256=VALUES(artifact_sha256),
             total_codes=VALUES(total_codes), active_codes=VALUES(active_codes), imported_at=CURRENT_TIMESTAMP'
    );
    $metaStatement->bind_param('sssii', $version, $source, $artifactSha256, $totalCodes, $activeCodes);
    $metaStatement->execute();
    $metaStatement->close();
    $connect->commit();
    $connect->query('SET FOREIGN_KEY_CHECKS = 1');
    echo 'imported=' . count($rows) . PHP_EOL;
    echo 'version=' . $version . PHP_EOL;
} catch (Throwable $exception) {
    $connect->rollback();
    $connect->query('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
