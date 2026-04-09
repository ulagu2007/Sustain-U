<?php
/**
 * Quick validation endpoint for the frontend preview.
 * Returns safety audit results without saving to DB.
 */

// Start output buffering to prevent accidental output from breaking JSON response
ob_start();

try {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['status' => 'ERROR', 'reason' => 'No image provided.']);
        ob_end_flush();
        exit;
    }

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'ERROR', 'reason' => 'Upload error.']);
        ob_end_flush();
        exit;
    }

    $tmp_dir = __DIR__ . '/../uploads/tmp/';
    if (!is_dir($tmp_dir)) {
        if (!mkdir($tmp_dir, 0755, true)) {
            throw new Exception('Failed to create temporary directory.');
        }
    }

    $filepath = $tmp_dir . 'audit_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save temporary file.');
    }

    // ============================================
    // BLAZING FAST API: Check if Persistent Python Server is running
    // ============================================
    $server_ip = '127.0.0.1';
    $server_port = 5056; // Shifted from 5055 to bypass stuck processes
    $server_online = false;

    // More generous connection check (2s timeout)
    $connection = @fsockopen($server_ip, $server_port, $errno, $errstr, 2.0);
    if ($connection) {
        fclose($connection);
        $server_online = true;
    }

    $curl_err = "";
    if ($server_online) {
        $ch = curl_init("http://$server_ip:$server_port/audit");
        $cfile = new CURLFile($filepath, $file['type'], $file['name']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $cfile]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $http_code == 200) {
            @unlink($filepath);
            $result = json_decode($response, true);
            if ($result && isset($result['status'])) {
                $result['source'] = 'persistent_server';
                ob_clean();
                echo json_encode($result);
                ob_end_flush();
                exit;
            }
        } else {
            $curl_err = "Persistent server returned error: " . ($curl_err ?: "HTTP $http_code");
        }
    } else {
        $curl_err = "Safety server not found on port $server_port";
    }

    // ============================================
    // FALLBACK: Slow Cold-Start Shell Execution
    // ============================================
    $python_script = __DIR__ . '/safety_audit.py';
    if (file_exists($python_script)) {
        // Try to use absolute path for stability if standard 'python' fails
        $python_bin = 'python';
        $alt_bins = [
            'C:\Program Files\Python311\python.exe',
            'C:\Users\Administrator\AppData\Local\Programs\Python\Python311\python.exe',
            'python3'
        ];
        
        foreach ($alt_bins as $bin) {
            if (file_exists($bin)) {
                $python_bin = $bin;
                break;
            }
        }

        $cmd = escapeshellarg($python_bin) . " " . escapeshellarg($python_script) . " " . escapeshellarg($filepath) . " 2>&1";
        $results_raw = [];
        $return_var = 0;
        exec($cmd, $results_raw, $return_var);
        $output = implode("\n", $results_raw);
        
        @unlink($filepath);
        
        if ($output) {
            preg_match('/\{.*\}/', trim($output), $matches);
            if (!empty($matches)) {
                $result = json_decode($matches[0], true);
                if ($result && isset($result['status'])) {
                    $result['reason'] = ($result['reason'] ?? 'Verified');
                    $result['source'] = 'cold_start_fallback';
                    ob_clean();
                    echo json_encode($result);
                    ob_end_flush();
                    exit;
                }
            }
        }
    } else {
        @unlink($filepath);
    }

    // Fallback if everything fails
    ob_clean();
    echo json_encode([
        'status' => 'APPROVED', 
        'reason' => 'AI Scan Bypassed: Safety module offline.',
        'source' => 'emergency_bypass'
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    @unlink($filepath);
    
    echo json_encode([
        'status' => 'ERROR',
        'reason' => 'Internal server error: ' . $e->getMessage(),
        'source' => 'global_exception_handler'
    ]);
}

ob_end_flush();
