<?php

// Mock global constants or classes if needed, or just include the class
require_once __DIR__ . '/../file_validator.php';

class ValidationTest {
    private $validator;
    private $testFile;

    public function __construct() {
        $this->validator = new FileValidator();
        $this->testFile = __DIR__ . '/test_sample.txt';
    }

    public function run() {
        echo "Running Validation Tests...\n";
        
        $this->testValidPdf();
        $this->testInvalidExtension();
        $this->testSizeLimit();
        // $this->testMimeMismatch(); // Hard to mock finfo without a real file of that type
        
        echo "\nTests Completed.\n";
    }

    private function testValidPdf() {
        // We need a dummy PDF or simulate one. 
        // For unit testing finfo, we really need a real file with magic numbers.
        // Let's create a minimal PDF file.
        $content = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
        $tmp = sys_get_temp_dir() . '/test.pdf';
        file_put_contents($tmp, $content);

        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($content)
        ];

        $res = $this->validator->validate($file);
        $this->assert($res['valid'], "Valid PDF should be accepted", $res['message']);
        
        @unlink($tmp);
    }

    private function testInvalidExtension() {
        $file = [
            'name' => 'test.exe',
            'type' => 'application/octet-stream',
            'tmp_name' => 'dummy',
            'error' => UPLOAD_ERR_OK,
            'size' => 100
        ];
        // We don't even need a real file here because extension check happens before finfo usually? 
        // Actually, my implementation checks size, then extension, then mime.
        // So tmp_name must exist for finfo if it reaches that stage.
        // But invalid extension should fail before mime check.
        
        $res = $this->validator->validate($file);
        $this->assert(!$res['valid'], "Invalid extension should be rejected");
        $this->assert(strpos($res['message'], 'Invalid file format') !== false, "Message should be about format");
    }

    private function testSizeLimit() {
        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => 'dummy',
            'error' => UPLOAD_ERR_OK,
            'size' => 1048576 + 1 // 1MB + 1 byte
        ];

        $res = $this->validator->validate($file);
        $this->assert(!$res['valid'], "File > 1MB should be rejected");
        $this->assert(strpos($res['message'], 'too large') !== false, "Message should be about size");
    }

    private function assert($condition, $message, $extra = '') {
        if ($condition) {
            echo "[PASS] $message\n";
        } else {
            echo "[FAIL] $message. $extra\n";
        }
    }
}

// Run
$test = new ValidationTest();
$test->run();

?>
