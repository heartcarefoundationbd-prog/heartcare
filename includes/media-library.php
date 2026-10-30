<?php
declare(strict_types=1);

const MEDIA_LIBRARY_MAX_BYTES = 26214400;

function media_library_allowed_types(): array
{
    return [
        'jpg' => ['kind' => 'image', 'mime' => ['image/jpeg']],
        'jpeg' => ['kind' => 'image', 'mime' => ['image/jpeg']],
        'png' => ['kind' => 'image', 'mime' => ['image/png']],
        'webp' => ['kind' => 'image', 'mime' => ['image/webp']],
        'gif' => ['kind' => 'image', 'mime' => ['image/gif']],
        'pdf' => ['kind' => 'document', 'mime' => ['application/pdf']],
        'doc' => ['kind' => 'document', 'mime' => ['application/msword', 'application/x-ole-storage']],
        'xls' => ['kind' => 'document', 'mime' => ['application/vnd.ms-excel', 'application/x-ole-storage']],
        'ppt' => ['kind' => 'document', 'mime' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage']],
        'docx' => ['kind' => 'document', 'mime' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']],
        'xlsx' => ['kind' => 'document', 'mime' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']],
        'pptx' => ['kind' => 'document', 'mime' => ['application/zip', 'application/vnd.openxmlformats-officedocument.presentationml.presentation']],
        'odt' => ['kind' => 'document', 'mime' => ['application/zip', 'application/vnd.oasis.opendocument.text']],
        'ods' => ['kind' => 'document', 'mime' => ['application/zip', 'application/vnd.oasis.opendocument.spreadsheet']],
        'odp' => ['kind' => 'document', 'mime' => ['application/zip', 'application/vnd.oasis.opendocument.presentation']],
        'rtf' => ['kind' => 'document', 'mime' => ['text/rtf', 'application/rtf', 'text/plain']],
        'txt' => ['kind' => 'document', 'mime' => ['text/plain']],
        'csv' => ['kind' => 'document', 'mime' => ['text/csv', 'text/plain', 'application/vnd.ms-excel']],
        'zip' => ['kind' => 'archive', 'mime' => ['application/zip', 'application/x-zip-compressed']],
        'tar' => ['kind' => 'archive', 'mime' => ['application/x-tar']],
        'gz' => ['kind' => 'archive', 'mime' => ['application/gzip', 'application/x-gzip']],
        'mp3' => ['kind' => 'audio', 'mime' => ['audio/mpeg']],
        'wav' => ['kind' => 'audio', 'mime' => ['audio/wav', 'audio/x-wav']],
        'ogg' => ['kind' => 'audio', 'mime' => ['audio/ogg', 'application/ogg']],
        'm4a' => ['kind' => 'audio', 'mime' => ['audio/mp4', 'audio/x-m4a', 'video/mp4']],
        'mp4' => ['kind' => 'video', 'mime' => ['video/mp4']],
        'mov' => ['kind' => 'video', 'mime' => ['video/quicktime', 'video/mp4']],
        'webm' => ['kind' => 'video', 'mime' => ['video/webm']],
        'ogv' => ['kind' => 'video', 'mime' => ['video/ogg', 'application/ogg']],
    ];
}

function media_library_current_admin_id(): ?int
{
    if (!function_exists('admin_user')) return null;
    $user = admin_user();
    return isset($user['id']) ? (int)$user['id'] : null;
}

function render_media_picker(string $name, string $kind, array $extensions = [], bool $multiple = false): void
{
    $value = $multiple ? (array)($_POST['media_selection'][$name] ?? []) : (string)($_POST['media_selection'][$name] ?? '');
    $selectionName = 'media_selection[' . $name . ']' . ($multiple ? '[]' : '');
    $extensionData = implode(',', array_map(static fn(string $extension): string => strtolower($extension), $extensions));
    echo '<div class="a-media-picker-field">';
    if ($multiple) {
        foreach ($value as $mediaId) {
            if (is_scalar($mediaId) && ctype_digit((string)$mediaId)) {
                echo '<input type="hidden" name="' . e($selectionName) . '" value="' . e((string)$mediaId) . '">';
            }
        }
    } else {
        echo '<input type="hidden" name="' . e($selectionName) . '" value="' . e(is_scalar($value) ? (string)$value : '') . '">';
    }
    echo '<button type="button" class="a-btn a-media-picker-open" data-media-picker-open'
        . ' data-kind="' . e($kind) . '" data-extensions="' . e($extensionData) . '"'
        . ' data-endpoint="' . e(admin_url('media.php')) . '"'
        . ' data-selection-name="' . e($name) . '" data-multiple="' . ($multiple ? '1' : '0') . '">'
        . ($multiple ? 'Add from Media Library' : 'Choose from Media Library') . '</button>';
    if (!$multiple && is_scalar($value) && (string)$value !== '') {
        $selected = media_library_find((int)$value);
        echo '<span class="a-media-picker-selection">' . e($selected['original_name'] ?? 'Selected media') . '</span>';
    }
    echo '</div>';
}

function media_library_detect_mime(string $path): ?string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if (is_string($mime) && $mime !== '') return strtolower($mime);
        }
    }

    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($path);
        if (is_string($mime) && $mime !== '') return strtolower($mime);
    }

    return null;
}

function media_library_magic_matches(string $path, string $extension): bool
{
    $handle = fopen($path, 'rb');
    if ($handle === false) return false;
    $head = (string)fread($handle, 16);
    $tail = '';
    if (in_array($extension, ['tar'], true) && fseek($handle, 257) === 0) {
        $tail = (string)fread($handle, 5);
    }
    fclose($handle);

    return match ($extension) {
        'jpg', 'jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
        'png' => str_starts_with($head, "\x89PNG\r\n\x1A\n"),
        'gif' => str_starts_with($head, 'GIF87a') || str_starts_with($head, 'GIF89a'),
        'webp' => strlen($head) >= 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP',
        'pdf' => str_starts_with($head, '%PDF-'),
        'doc', 'xls', 'ppt' => str_starts_with($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
        'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'zip' => str_starts_with($head, "PK\x03\x04") || str_starts_with($head, "PK\x05\x06"),
        'gz' => str_starts_with($head, "\x1F\x8B"),
        'tar' => $tail === 'ustar',
        'mp3' => str_starts_with($head, 'ID3') || (strlen($head) >= 2 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0),
        'wav' => strlen($head) >= 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WAVE',
        'ogg', 'ogv' => str_starts_with($head, 'OggS'),
        'mp4', 'mov', 'm4a' => strlen($head) >= 8 && substr($head, 4, 4) === 'ftyp',
        'webm' => str_starts_with($head, "\x1A\x45\xDF\xA3"),
        'txt', 'csv' => !str_contains($head, "\0") && (preg_match('//u', file_get_contents($path, false, null, 0, 8192) ?: '') === 1),
        'rtf' => str_starts_with($head, '{\\rtf'),
        default => false,
    };
}

function media_library_validate_file(string $path, string $extension): array
{
    $types = media_library_allowed_types();
    $extension = strtolower($extension);
    if (!isset($types[$extension])) {
        return ['ok' => false, 'error' => 'This file type is not allowed.'];
    }
    $size = filesize($path);
    if ($size === false || $size < 1 || $size > MEDIA_LIBRARY_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Files must be between 1 byte and 25 MB.'];
    }

    $expectedMimes = $types[$extension]['mime'];
    $mime = media_library_detect_mime($path);
    if ($mime !== null && !in_array($mime, $expectedMimes, true)) {
        return ['ok' => false, 'error' => 'The file contents do not match the selected file type.'];
    }
    if ($mime === null && !media_library_magic_matches($path, $extension)) {
        return ['ok' => false, 'error' => 'The file contents could not be verified safely.'];
    }

    if ($types[$extension]['kind'] === 'image') {
        $image = @getimagesize($path);
        $imageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!is_array($image) || !in_array($image['mime'] ?? '', $imageMimes, true) || !in_array($image['mime'], $expectedMimes, true)) {
            return ['ok' => false, 'error' => 'The uploaded file is not a valid supported image.'];
        }
        $mime = $image['mime'];
    } elseif (in_array($extension, ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'], true)) {
        if (!class_exists(ZipArchive::class)) {
            return ['ok' => false, 'error' => 'Archive validation is unavailable; this office file cannot be accepted safely.'];
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'error' => 'The office document is not a valid archive.'];
        }
        $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
        $hasManifest = $extension === 'odt' || $extension === 'ods' || $extension === 'odp'
            ? $zip->locateName('mimetype') !== false
            : $hasContentTypes;
        $hasMacro = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if ($zip->numFiles > 10000) {
                $zip->close();
                return ['ok' => false, 'error' => 'The office file contains too many entries.'];
            }
            $name = strtolower((string)$zip->getNameIndex($i));
            $stats = $zip->statIndex($i);
            if (is_array($stats) && (int)($stats['size'] ?? 0) > 104857600) {
                $zip->close();
                return ['ok' => false, 'error' => 'The office file expands beyond the permitted size.'];
            }
            if (str_ends_with($name, 'vbaproject.bin')) {
                $hasMacro = true;
                break;
            }
        }
        $zip->close();
        if (!$hasManifest || $hasMacro) {
            return ['ok' => false, 'error' => 'Invalid or macro-enabled office files are not allowed.'];
        }
    }

    return ['ok' => true, 'mime' => $mime ?? $expectedMimes[0], 'kind' => $types[$extension]['kind'], 'size' => $size, 'extension' => $extension];
}

function media_library_register(string $path, string $originalName, string $mime, string $kind, int $size, ?int $uploadedBy = null): int
{
    $stmt = db()->prepare(
        'INSERT INTO media_library (original_name, path, mime_type, media_kind, file_size, uploaded_by, created_at)
         VALUES (:original_name, :path, :mime_type, :media_kind, :file_size, :uploaded_by, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), original_name = VALUES(original_name),
             mime_type = VALUES(mime_type), media_kind = VALUES(media_kind), file_size = VALUES(file_size)'
    );
    $stmt->execute([
        ':original_name' => mb_substr(basename($originalName), 0, 255),
        ':path' => $path,
        ':mime_type' => $mime,
        ':media_kind' => $kind,
        ':file_size' => $size,
        ':uploaded_by' => $uploadedBy,
    ]);
    return (int)(db()->lastInsertId() ?: 0);
}

function media_library_register_path(string $relativePath, string $originalName = '', ?int $uploadedBy = null): ?int
{
    $relativePath = ltrim($relativePath, '/');
    $absolute = APP_ROOT . '/' . $relativePath;
    if (!is_file($absolute) || !media_library_inside_storage($absolute)) return null;
    $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
    if (!isset(media_library_allowed_types()[$extension])) return null;
    $validation = media_library_validate_file($absolute, $extension);
    if (!$validation['ok']) return null;
    return media_library_register(
        $relativePath,
        $originalName !== '' ? $originalName : basename($absolute),
        $validation['mime'],
        $validation['kind'],
        $validation['size'],
        $uploadedBy
    );
}

function media_library_upload(array $file, ?int $uploadedBy = null): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
            ? 'The file exceeds the server upload limit.'
            : 'The file could not be uploaded.'];
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'The upload could not be verified.'];
    }
    $originalName = basename((string)($file['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $validation = media_library_validate_file($tmp, $extension);
    if (!$validation['ok']) return $validation;

    $folder = in_array($validation['kind'], ['document', 'archive'], true) ? 'docs' : 'library';
    $directory = APP_ROOT . '/assets/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['ok' => false, 'error' => 'The media storage directory could not be created.'];
    }
    $basename = bin2hex(random_bytes(10)) . '.' . $extension;
    $absolute = $directory . '/' . $basename;
    if (!move_uploaded_file($tmp, $absolute)) {
        return ['ok' => false, 'error' => 'The uploaded file could not be stored.'];
    }

    $relative = 'assets/uploads/' . $folder . '/' . $basename;
    try {
        $id = media_library_register($relative, $originalName, $validation['mime'], $validation['kind'], $validation['size'], $uploadedBy);
    } catch (Throwable $exception) {
        if (is_file($absolute)) unlink($absolute);
        throw $exception;
    }

    return [
        'ok' => true,
        'item' => [
            'id' => $id,
            'name' => mb_substr($originalName, 0, 255),
            'path' => $relative,
            'mime' => $validation['mime'],
            'kind' => $validation['kind'],
            'size' => $validation['size'],
            'extension' => $extension,
        ],
    ];
}

function media_library_find(int $id): ?array
{
    if ($id < 1) return null;
    $stmt = db()->prepare('SELECT id, original_name, path, mime_type, media_kind, file_size FROM media_library WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($item) ? $item : null;
}

function media_library_is_path(string $path): bool
{
    $stmt = db()->prepare('SELECT 1 FROM media_library WHERE path = :path LIMIT 1');
    $stmt->execute([':path' => $path]);
    return (bool)$stmt->fetchColumn();
}

function media_library_resolve_selection(int $id, array $allowedExtensions = [], array $allowedKinds = [], int $maxBytes = 0): ?array
{
    $item = media_library_find($id);
    if (!$item) return null;
    $extension = strtolower(pathinfo((string)$item['path'], PATHINFO_EXTENSION));
    $absolute = APP_ROOT . '/' . ltrim((string)$item['path'], '/');
    if (!is_file($absolute) || !media_library_inside_storage($absolute)) return null;
    if ($allowedKinds && !in_array((string)$item['media_kind'], $allowedKinds, true)) return null;
    if ($allowedExtensions && !in_array($extension, $allowedExtensions, true)) return null;
    if ($maxBytes > 0 && (int)$item['file_size'] > $maxBytes) return null;
    return [
        'id' => (int)$item['id'],
        'path' => (string)$item['path'],
        'name' => (string)$item['original_name'],
        'mime' => (string)$item['mime_type'],
        'kind' => (string)$item['media_kind'],
        'size' => (int)$item['file_size'],
        'extension' => $extension,
    ];
}

function media_library_inside_storage(string $absolutePath): bool
{
    $real = realpath($absolutePath);
    $base = realpath(APP_ROOT . '/assets/uploads');
    return $real !== false && $base !== false && str_starts_with($real, $base . DIRECTORY_SEPARATOR);
}

function media_library_sync_existing_uploads(): void
{
    $state = db()->query("SELECT state_value FROM media_library_state WHERE state_key = 'legacy_sync' LIMIT 1")->fetchColumn();
    if ($state !== false && strtotime((string)$state) > time() - 300) return;

    $root = APP_ROOT . '/assets/uploads';
    if (is_dir($root)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        $allowed = media_library_allowed_types();
        foreach ($iterator as $entry) {
            if (!$entry->isFile() || $entry->isLink()) continue;
            $absolute = $entry->getPathname();
            if (str_contains($absolute, DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR) || !media_library_inside_storage($absolute)) continue;
            $extension = strtolower($entry->getExtension());
            if (!isset($allowed[$extension])) continue;
            $validation = media_library_validate_file($absolute, $extension);
            if (!$validation['ok']) continue;
            $relative = substr($absolute, strlen(APP_ROOT) + 1);
            media_library_register($relative, $entry->getFilename(), $validation['mime'], $validation['kind'], $validation['size']);
        }
    }
    db()->exec("INSERT INTO media_library_state (state_key, state_value) VALUES ('legacy_sync', NOW()) ON DUPLICATE KEY UPDATE state_value = VALUES(state_value)");
}

function media_library_extension_filter(string $extensions, array &$conditions, array &$params): void
{
    $extensions = array_values(array_filter(array_map(
        static fn(string $extension): string => strtolower(trim($extension)),
        explode(',', $extensions)
    ), static fn(string $extension): bool => isset(media_library_allowed_types()[$extension])));
    if (!$extensions) return;
    $placeholders = [];
    foreach ($extensions as $index => $extension) {
        $key = ':extension_' . $index;
        $placeholders[] = $key;
        $params[$key] = $extension;
    }
    $conditions[] = 'LOWER(SUBSTRING_INDEX(path, \'.\', -1)) IN (' . implode(',', $placeholders) . ')';
}

function media_library_list(string $query = '', string $kind = '', int $limit = 24, int $offset = 0, string $extensions = ''): array
{
    $conditions = [];
    $params = [];
    if ($query !== '') {
        $conditions[] = 'original_name LIKE :query';
        $params[':query'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
    }
    if (in_array($kind, ['image', 'document', 'archive', 'audio', 'video'], true)) {
        $conditions[] = 'media_kind = :kind';
        $params[':kind'] = $kind;
    }
    media_library_extension_filter($extensions, $conditions, $params);
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $limit = max(1, min(48, $limit));
    $offset = max(0, $offset);
    $stmt = db()->prepare(
        "SELECT id, original_name, path, mime_type, media_kind, file_size, created_at
         FROM media_library $where ORDER BY created_at DESC, id DESC LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function media_library_count(string $query = '', string $kind = '', string $extensions = ''): int
{
    $conditions = [];
    $params = [];
    if ($query !== '') {
        $conditions[] = 'original_name LIKE :query';
        $params[':query'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
    }
    if (in_array($kind, ['image', 'document', 'archive', 'audio', 'video'], true)) {
        $conditions[] = 'media_kind = :kind';
        $params[':kind'] = $kind;
    }
    media_library_extension_filter($extensions, $conditions, $params);
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $stmt = db()->prepare("SELECT COUNT(*) FROM media_library $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
