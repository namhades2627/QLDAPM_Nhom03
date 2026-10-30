<?php

/** Kiểm tra ảnh thật, đặt tên ngẫu nhiên và chỉ lưu trong thư mục ảnh tĩnh. */
class AvatarUpload
{
    public static function url(?string $path): string
    {
        return self::isManaged($path) && is_file(__DIR__ . '/../../public/' . $path)
            ? '../public/' . $path : '';
    }

    private static function isManaged(?string $path): bool
    {
        return $path !== null && preg_match('~^uploads/avatars/[a-f0-9]{32}\.(jpg|png|gif|webp)$~D', $path) === 1;
    }

    public static function delete(?string $path): void
    {
        if (self::isManaged($path)) {
            $file = __DIR__ . '/../../public/' . $path;
            if (is_file($file) && !unlink($file)) error_log('OnTrip: cannot remove old avatar.');
        }
    }

    /** Trả về đường dẫn tương đối; null nếu không chọn ảnh mới. */
    public static function save(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Không tải được ảnh. Ảnh tối đa 5 MB; vui lòng chọn lại.');
        $temp = $file['tmp_name'] ?? '';
        if (!is_string($temp) || !is_uploaded_file($temp) || filesize($temp) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Ảnh tải lên không hợp lệ hoặc vượt quá 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $size = @getimagesize($temp);
        if (!isset($types[$mime]) || !$size || ($size['mime'] ?? '') !== $mime) {
            throw new InvalidArgumentException('Vui lòng chọn ảnh JPG, PNG, GIF hoặc WEBP hợp lệ.');
        }
        if ($size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 12000000) {
            throw new InvalidArgumentException('Ảnh quá lớn. Vui lòng chọn ảnh tối đa 4096 × 4096 và 12 triệu điểm ảnh.');
        }
        $image = extension_loaded('gd') ? @imagecreatefromstring(file_get_contents($temp)) : null;
        if ($image === false) throw new InvalidArgumentException('Không đọc được ảnh này. Vui lòng chọn ảnh khác.');
        $directory = __DIR__ . '/../../public/uploads/avatars';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Không tạo được thư mục ảnh đại diện.');
        }
        $path = 'uploads/avatars/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
        $target = __DIR__ . '/../../public/' . $path;
        try {
            // Có GD thì mã hóa lại; nếu không, ảnh được phục vụ bằng handler tĩnh trong .htaccess.
            if ($image === null) $saved = move_uploaded_file($temp, $target);
            elseif ($mime === 'image/jpeg') $saved = imagejpeg($image, $target, 85);
            elseif ($mime === 'image/png') $saved = imagepng($image, $target, 6);
            elseif ($mime === 'image/gif') $saved = imagegif($image, $target);
            else $saved = imagewebp($image, $target, 85);
            if (!$saved) throw new RuntimeException('Không ghi được ảnh đại diện.');
        } catch (Throwable $exception) {
            self::delete($path);
            throw $exception;
        } finally {
            if ($image !== null) imagedestroy($image);
        }
        return $path;
    }
}
