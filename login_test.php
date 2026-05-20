<?php
// Direct login test
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
    <div class='card'>
        <div class='card-header'>
            <h3>Login Test Results</h3>
        </div>
        <div class='card-body'>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h4>Testing Login for: $username</h4>";
    
    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'nikas_restaurant');
    
    if ($conn->connect_error) {
        echo "<div class='alert alert-danger'>Database Connection Failed: " . $conn->connect_error . "</div>";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            echo "<div class='alert alert-info'>
                    <p><strong>User Found:</strong> " . $user['full_name'] . "</p>
                    <p><strong>Stored Password Hash:</strong> " . substr($user['password'], 0, 30) . "...</p>
                    <p><strong>Hash Length:</strong> " . strlen($user['password']) . " characters</p>
                </div>";
            
            // Test password verification
            $verify = password_verify($password, $user['password']);
            
            echo "<div class='alert " . ($verify ? 'alert-success' : 'alert-danger') . "'>
                    <p><strong>Password Verification:</strong> " . ($verify ? 'SUCCESS' : 'FAILED') . "</p>
                </div>";
            
            if ($verify) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                echo "<div class='alert alert-success'>
                        <h4>✓ LOGIN SUCCESSFUL!</h4>
                        <p>Session variables set:</p>
                        <ul>
                            <li>User ID: " . $_SESSION['user_id'] . "</li>
                            <li>Username: " . $_SESSION['username'] . "</li>
                            <li>Full Name: " . $_SESSION['full_name'] . "</li>
                            <li>Role: " . $_SESSION['role'] . "</li>
                        </ul>
                        <a href='index.php' class='btn btn-success'>Go to Dashboard</a>
                    </div>";
            } else {
                // Try to fix password
                echo "<div class='alert alert-warning'>
                        <h4>Attempting to fix password...</h4>";
                
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $update->bind_param("ss", $hash, $username);
                
                if ($update->execute()) {
                    echo "<p>✓ Password updated with new hash</p>";
                    
                    // Try login again
                    if (password_verify($password, $hash)) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        
                        echo "<div class='alert alert-success'>
                                <h4>✓ LOGIN SUCCESSFUL AFTER FIX!</h4>
                                <a href='index.php' class='btn btn-success'>Go to Dashboard</a>
                            </div>";
                    }
                }
                echo "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>
                    <h4>✗ User Not Found</h4>
                    <p>Username '$username' does not exist in database.</p>
                    <p><a href='fix_login.php' class='btn btn-warning'>Create User</a></p>
                </div>";
        }
        
        $conn->close();
    }
}

echo "</div></div>
    <div class='mt-3'>
        <a href='login.php' class='btn btn-primary'>Back to Login Page</a>
        <a href='fix_login.php' class='btn btn-warning'>Run Fix Again</a>
    </div>
</body></html>";
?>