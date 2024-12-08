<?php
namespace WPSB\Core;

class Encryption {
    private $cipher = 'aes-256-cbc';
    private $key;
    private $iv;

    public function __construct() {
        $this->key = $this->get_encryption_key();
        $this->iv = $this->get_encryption_iv();
    }

    public function encrypt_directory($dir) {
        $zip_file = $dir . '.zip';
        $encrypted_file = $dir . '.encrypted';

        // Create ZIP archive
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();

        // Encrypt ZIP file
        $data = file_get_contents($zip_file);
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            0,
            $this->iv
        );

        file_put_contents($encrypted_file, base64_encode($encrypted));

        // Cleanup
        unlink($zip_file);

        return $encrypted_file;
    }

    public function decrypt_backup($encrypted_file, $output_dir) {
        $encrypted_data = base64_decode(file_get_contents($encrypted_file));
        
        $decrypted = openssl_decrypt(
            $encrypted_data,
            $this->cipher,
            $this->key,
            0,
            $this->iv
        );

        $temp_zip = tempnam(sys_get_temp_dir(), 'backup_');
        file_put_contents($temp_zip, $decrypted);

        $zip = new \ZipArchive();
        if ($zip->open($temp_zip) === TRUE) {
            $zip->extractTo($output_dir);
            $zip->close();
        } else {
            throw new \Exception('Failed to extract backup archive');
        }

        unlink($temp_zip);
    }

    private function get_encryption_key() {
        $key = get_option('wpsb_encryption_key');
        if (!$key) {
            $key = bin2hex(random_bytes(32));
            update_option('wpsb_encryption_key', $key);
        }
        return hex2bin($key);
    }

    private function get_encryption_iv() {
        $iv = get_option('wpsb_encryption_iv');
        if (!$iv) {
            $iv = bin2hex(random_bytes(openssl_cipher_iv_length($this->cipher)));
            update_option('wpsb_encryption_iv', $iv);
        }
        return hex2bin($iv);
    }
}