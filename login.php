<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $koneksi->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $password_hash);
    if ($stmt->num_rows === 1) {
        $stmt->fetch();
        if (password_verify($password, $password_hash)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            echo "Login successful";
            header("Location: index.php");
            exit();
        } else {
            echo "Invalid credentials";
        }
    } else {
        echo "Invalid credentials";
    }
}

?>

<div class="form-container">
    <h2>Login</h2>
    <form action="login.php" method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>