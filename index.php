<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch contacts from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT DISTINCT contact_id, (SELECT username FROM users WHERE id = contact_id) AS contact_name FROM contacts WHERE user_id = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$contacts = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact List</title>
    <style>
        .contact-list {
            width: 300px;
            margin: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            list-style-type: none;
        }

        .contact-list li {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .contact-list li:hover {
            background-color: #f5f5f5;
        }

        .contact-list li a {
            text-decoration: none;
            color: black;
        }

        .logout-button {
            margin: 20px;
        }
    </style>
</head>

<body>
    <h1>hi, <?= $_SESSION['username'] ?> 👋</h1>
    <div>
        <input type="text" id="contact-username" placeholder="Add Contact">
        <button id="add-contact">Add</button>
    </div>
    <h1>Contact List</h1>
    <ul class="contact-list">
        <?php foreach ($contacts as $contact): ?>
            <li><a href="chat.php?contact=<?php echo urlencode($contact['contact_id']); ?>"><?php echo htmlspecialchars($contact['contact_name']); ?></a></li>
        <?php endforeach; ?>
    </ul>

    <button class="logout-button"><a href="logout.php">Logout</a></button>
    <script>
        var addContactButton = document.getElementById('add-contact');
        var contactUsernameInput = document.getElementById('contact-username');

        addContactButton.addEventListener('click', function() {
            var contactUsername = contactUsernameInput.value;

            if (!contactUsername) {
                alert('Contact username cannot be empty!');
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_contact.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert(xhr.responseText);
                }
            };
            xhr.onerror = function() {
                alert('Network error.');
            };
            xhr.send('contact_username=' + encodeURIComponent(contactUsername));
        });
    </script>
</body>

</html>