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
        try {
            $query = "UPDATE content_blocks SET content = :content WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":id", $block_id);

            $result = $stmt->execute();
            
            if ($result) {
                // Get the page ID for this block
                $query = "SELECT page_id FROM content_blocks WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":id", $block_id);
                $stmt->execute();
                
                if ($page_id = $stmt->fetchColumn()) {
                    // Update the page's last_modified timestamp
                    $query = "UPDATE pages SET last_modified = CURRENT_TIMESTAMP WHERE id = :id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":id", $page_id);
                    $stmt->execute();
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating content block: " . $e->getMessage());
            return false;
        }
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
                     ON DUPLICATE KEY UPDATE content = VALUES(content)";
            
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
        if (empty($blocks)) {
            return $html_content;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);
        
        foreach ($blocks as $block) {
            $element = $xpath->query("//*[@id='{$block['selector']}']")->item(0);
            
            if ($element) {
                if ($block['type'] === 'image') {
                    if ($element->tagName === 'img') {
                        $element->setAttribute('src', $block['content']);
                    }
                } else {
                    // For text content, we need to handle HTML content properly
                    if ($block['content']) {
                        // Create a temporary document for the content
                        $temp = new DOMDocument();
                        @$temp->loadHTML('<div>' . $block['content'] . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        
                        // Clear existing content
                        while ($element->hasChildNodes()) {
                            $element->removeChild($element->firstChild);
                        }
                        
                        // Import and append new content
                        foreach ($temp->getElementsByTagName('div')->item(0)->childNodes as $node) {
                            $imported = $dom->importNode($node, true);
                            $element->appendChild($imported);
                        }
                    }
                }
            }
        }
        
        // Clean up the output
        $html = $dom->saveHTML();
        libxml_clear_errors();
        
        // Remove DOCTYPE and HTML/BODY tags if they were added
        $html = preg_replace(
            [
                '/^<!DOCTYPE.*?>\n/',
                '/<html><body>/',
                '/<\/body><\/html>/'
            ],
            '',
            $html
        );
        
        return $html;
    }
}
?>
