<?php
require_once __DIR__ . '/../config/database.php';

class Page {
    private $conn;
    private $table_name = "pages";

    public $id;
    public $filename;
    public $title;
    public $modified_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            // Check if page already exists
            $check_query = "SELECT id FROM " . $this->table_name . " WHERE filename = :filename";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":filename", $this->filename);
            $check_stmt->execute();
            
            if ($row = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
                // Page exists, update it
                $this->id = $row['id'];
                return $this->update();
            }

            // Page doesn't exist, create new
            $query = "INSERT INTO " . $this->table_name . " SET filename=:filename, title=:title, modified_by=:modified_by";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":filename", $this->filename);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":modified_by", $this->modified_by);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error in Page::create(): " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET title=:title, modified_by=:modified_by 
                 WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":modified_by", $this->modified_by);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT p.*, u.username as modified_by_user 
                 FROM " . $this->table_name . " p 
                 LEFT JOIN users u ON p.modified_by = u.id 
                 ORDER BY p.last_modified DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getContentBlocks($page_id) {
        $query = "SELECT * FROM content_blocks WHERE page_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$page_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateContentBlock($block_id, $content) {
        $query = "UPDATE content_blocks SET content = :content WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":content", $content);
        $stmt->bindParam(":id", $block_id);

        return $stmt->execute();
    }

    public function scanAndCreateBlocks($page_id, $html_content) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Scan for editable elements
        $xpath = new DOMXPath($dom);
        $editableElements = $xpath->query("//*[@data-editable]");
        
        foreach ($editableElements as $element) {
            $type = $element->getAttribute('data-editable');
            $id = $element->getAttribute('id');
            
            if (!$id || !in_array($type, ['text', 'image'])) {
                continue;
            }

            // Get content based on type
            if ($type === 'image') {
                $content = $element->getAttribute('src');
            } else {
                $content = $element->textContent;
            }
            
            // Insert or update content block
            $query = "INSERT INTO content_blocks (page_id, selector, content, type) 
                     VALUES (:page_id, :selector, :content, :type)
                     ON DUPLICATE KEY UPDATE content = :content";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':page_id' => $page_id,
                ':selector' => $id,
                ':content' => $content,
                ':type' => $type
            ]);
        }
        
        libxml_clear_errors();
    }

    public function applyContentBlocks($html_content, $blocks) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        foreach ($blocks as $block) {
            $xpath = new DOMXPath($dom);
            $element = $xpath->query("//*[@id='{$block['selector']}']")->item(0);
            
            if ($element) {
                if ($block['type'] === 'image') {
                    $element->setAttribute('src', $block['content']);
                } else {
                    $element->textContent = $block['content'];
                }
            }
        }
        
        $html = $dom->saveHTML();
        libxml_clear_errors();
        return $html;
    }
}
?>
