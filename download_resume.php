<?php
// downloadResume.php
include 'db.php';

if (isset($_GET['id'])) {
    $resumeId = $_GET['id'];
    
    // Fetch the resume file from the database
    $sql = "SELECT attachment_id, file_name, OCTET_LENGTH(resume_text) AS file_size, resume_text 
            FROM attachment 
            WHERE attachment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $resumeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Set headers for file download
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $row['file_name'] . "\"");
        header("Content-Length: " . $row['file_size']);
        
        // Output file contents
        echo $row['resume_text'];
    } else {
        echo "Resume not found.";
    }
} else {
    echo "Invalid request.";
}
?>