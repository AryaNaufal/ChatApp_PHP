<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['contact'])) {
    header('Location: login.php');
    exit();
}

$contact_id = $_GET['contact'];
$user_id = $_SESSION['user_id'];

// Fetch the contact's username
$stmt = $koneksi->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $contact_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($contact_username);
$stmt->fetch();
$stmt->close();

// Ensure the contact exists
if (!$contact_username) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Chat with <?php echo htmlspecialchars($contact_username); ?></title>
    <style>
        * {
            box-sizing: border-box;
            /* Ensure padding and border are included in the element's total width and height */
            /* border: solid 1px red; */
        }

        #chat-box {
            width: 100%;
            /* Make sure chat-box takes full width of its container */
            height: 500px;
            border: 1px solid #ccc;
            overflow-y: scroll;
            padding: 10px;
            background-color: #e5ddd5;
            /* Light background color for better visibility */
            display: flex;
            flex-direction: column;
            gap: 10px;
            /* Add spacing between messages */
            /* -ms-overflow-style: none;
            scrollbar-width: none; */
        }

        @media screen and (min-width: 425px) {
            .chat-container {
                height: calc(100vh - 150px);
            }

            #chat-box {
                height: 80%;
            }
        }

        #chat-box::-webkit-scrollbar {
            /* display: none; */
            width: 10px;
        }

        #chat-box::-webkit-scrollbar-track {
            background-color: transparent;
        }

        #chat-box::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 5px;
        }

        #message {
            width: calc(100% - 22px);
            /* Adjust width for padding and border */
            height: 100px;
            margin: 10px 0;
        }

        .message {
            display: flex;
            flex-direction: column;
            margin: 5px 0;
            max-width: 90%;
            height: 300px;
            /* Adjust based on desired message width */
            padding: 10px;
            border-radius: 10px;
            position: relative;
            word-wrap: break-word;
        }

        .message.received {
            background-color: #dcf8c6;
            /* Green background for sent messages */
            align-self: flex-end;
            /* Align to the right */
            text-align: right;
            height: max-content;
        }

        .message.sent {
            background-color: #ffffff;
            /* White background for received messages */
            align-self: flex-start;
            /* Align to the left */
            text-align: left;
            height: max-content;
        }

        .message .username {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .message .text {
            font-weight: 400;
            font-size: 20px;
            /* Adjust text weight */
            margin-bottom: 15px;
        }

        .timestamp {
            font-size: 13px;
            color: #999;
            /* position: absolute; */
            bottom: 5px;
            right: 10px;
        }

        .message.received .timestamp {
            left: 10px;
            right: auto;
        }

        .back-button {
            border: none;
            border-radius: 10px;
            margin: 20px;
            padding: 0 20px;
            background-color: #eb2d3a;
        }

        .back-button a {
            text-decoration: none;
            color: white;
        }
    </style>

</head>

<body>
    <div style="display: flex; gap: 20px;">
        <button class="back-button"><a href="index.php">Back</a></button>
        <h1><?php echo htmlspecialchars($contact_username); ?></h1>
    </div>
    <div class="chat-container">
        <div id="chat-box"></div>
        <textarea id="message" placeholder="Type your message..." style="resize:none"></textarea>
        <button id="send-message">Send Message</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var chatBox = document.getElementById('chat-box');
            var messageInput = document.getElementById('message');
            var sendMessageButton = document.getElementById('send-message');

            var contactId = new URLSearchParams(window.location.search).get('contact');
            var isAtBottom = true; // Flag to check if the chat box is scrolled to the bottom

            function addMessageToChat(username, message, isSent, timestamp) {
                var messageElement = document.createElement('div');
                messageElement.className = 'message ' + (isSent ? 'sent' : 'received');

                var messageContent = '<div class="text">' + htmlspecialchars(message) + '</div>';
                messageContent += '<div class="timestamp">' + htmlspecialchars(new Date(timestamp).toLocaleTimeString()) + '</div>';
                messageElement.innerHTML = messageContent;

                chatBox.appendChild(messageElement);

                if (isAtBottom) {
                    chatBox.scrollTop = chatBox.scrollHeight; // Scroll to bottom if already at bottom
                }
            }

            // Function to escape HTML characters
            function htmlspecialchars(str) {
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function sendMessage() {
                var message = messageInput.value;

                if (!message) {
                    alert('Message cannot be empty!');
                    return;
                }

                fetch('chat_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            contact_id: contactId,
                            message: message
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            messageInput.value = '';
                            fetchMessages(); // Fetch messages to see the new message
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while sending the message.');
                    });

            }

            function fetchMessages() {
                fetch('chat_handler.php?contact_id=' + encodeURIComponent(contactId))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(messages => {
                        chatBox.innerHTML = '';
                        messages.forEach(msg => {
                            // Replace with your actual username comparison logic
                            var isSent = msg.username === <?= json_encode($contact_username) ?>;
                            addMessageToChat(msg.username, msg.message, isSent, msg.timestamp);
                        });

                        // Adjust scroll position based on whether we were at the bottom
                        if (isAtBottom) {
                            chatBox.scrollTop = chatBox.scrollHeight;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching messages.');
                    });
            }

            chatBox.addEventListener('scroll', function() {
                // Check if we're at the bottom of the chat box
                isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 1;
            });

            sendMessageButton.addEventListener('click', sendMessage);

            setInterval(fetchMessages, 3000); // Poll for new messages every 3 seconds
            fetchMessages(); // Fetch messages when the page loads
        });
    </script>

</body>

</html>