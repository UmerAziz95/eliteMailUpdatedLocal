<style>
    .chat-header a {
        text-decoration: none;
        color: white;
    }

    .copyright {
        font-size: 12px;
        text-align: center;
        padding-bottom: 10px;
    }

    .copyright a {
        text-decoration: none;
        color: #343c41;
    }

    #chatbot-toggle-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        animation: chatbot .4s linear infinite alternate-reverse;
        border: none;
        background-color: transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 1001;
        /* Ensure the button is above the chatbot popup */
    }

    .chatbot-popup {
        display: none;
        position: fixed;
        bottom: 90px;
        right: 20px;
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        width: 350px;
        max-width: 90%;
        z-index: 1000;
    }

    .chat-header {
        background-color: var(--second-primary);
        color: #fff;
        padding: 15px 20px;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #close-btn {
        background-color: transparent;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
    }

    .chat-box {
        max-height: 350px;
        overflow-y: auto;
        padding: 15px 20px;
    }

    .chat-input {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        border-top: 1px solid #ddd;
    }

    #user-input {
        font-family: "Poppins";
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd !important;
        color: #000 !important;
        border-radius: 12px;
        outline: none;
    }

    #send-btn {
        font-family: "Poppins", sans-serif;
        padding: 10px 20px;
        border: none;
        background-color: var(--second-primary);
        color: #fff;
        border-radius: 12px;
        margin-left: 10px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    #send-btn:hover {
        background-color: #0074cc;
    }

    .user-message {
        background-color: #f3f3f3;
        color: #333;
        padding: 14px;
        border-radius: 15px;
        margin-bottom: 15px;
        margin-top: 15px;
        margin-left: 10px;
        /* Push user message to the left */
        position: relative;
        display: flex;
        align-items: center;
        flex-direction: row-reverse;
        /* Move user message to the right */
    }

    .user-message::before {
        content: "\1F468";
        /* Man emoji */
        position: absolute;
        bottom: -17px;
        right: -20px;
        margin-bottom: 7px;
        font-size: 20px;
        background-color: var(--second-primary);
        color: #fff;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
    }

    .bot-message {
        background-color: var(--second-primary);
        color: #fff;
        padding: 14px;
        border-radius: 15px;
        margin-bottom: 10px;
        margin-top: 15px;
        align-self: flex-start;
        /* Move bot message to the left */
        margin-right: 10px;
        /* Push bot message to the right */
        position: relative;
        display: flex;
        align-items: center;
        flex-direction: column;
        /* Adjust for button placement */
    }

    .bot-message::before {
        content: "\1F916";
        /* Robot emoji */
        position: absolute;
        bottom: -17px;
        left: -14px;
        margin-bottom: 4px;
        font-size: 20px;
        background-color: var(--second-primary);
        color: #fff;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
    }

    .button-container {
        display: flex;
        justify-content: space-around;
        margin-top: 10px;
    }

    .button-container button {
        padding: 10px 50px;
        border: none;
        background-color: var(--second-primary);
        color: #fff;
        border-radius: 10px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .button-container button:hover {
        background-color: #0074cc;
    }

    @keyframes chatbot {
        to {
            scale: 1
        }

        from {
            scale: .9
        }
    }
</style>


<button id="chatbot-toggle-btn">
    <img src="{{ asset('assets/logo/chatbot.png') }}" width="70" alt="">
</button>

<div class="chatbot-popup" id="chatbot-popup">
    <div class="chat-header">
        <span>Chatbot | <a href="#" target="_blank"> Project Inbox</a></span>
        <button id="close-btn">&times;</button>
    </div>
    <div class="chat-box" id="chat-box"></div>
    <div class="chat-input">
        <input type="text" id="user-input" placeholder="Type a message...">
        <button id="send-btn">Send</button>
    </div>
    <div class="copyright">
        <a href="#" target="_blank"> Made By Codeing Avengers © 2024</a>
    </div>
</div>

<script>
    const responses = {
        hello: "Hi there! How can I assist you today?",
        "how are you": "I'm just a bot, but I'm here to help you!",
        "need help": "How I can help you today?",
        bye: "Goodbye! Have a great day!",
        default: "I'm sorry, I didn't understand that. Want to connect with expert?",
        expert: "Great! Please wait a moment while we connect you with an expert.",
        no: "Okay, if you change your mind just let me know!"
    };

    document
        .getElementById("chatbot-toggle-btn")
        .addEventListener("click", toggleChatbot);
    document.getElementById("close-btn").addEventListener("click", toggleChatbot);
    document.getElementById("send-btn").addEventListener("click", sendMessage);
    document
        .getElementById("user-input")
        .addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                sendMessage();
            }
        });

    function toggleChatbot() {
        const chatbotPopup = document.getElementById("chatbot-popup");
        chatbotPopup.style.display =
            chatbotPopup.style.display === "none" ? "block" : "none";
    }

    function sendMessage() {
        const userInput = document.getElementById("user-input").value.trim();
        if (userInput !== "") {
            appendMessage("user", userInput);
            respondToUser(userInput.toLowerCase());
            document.getElementById("user-input").value = "";
        }
    }

    function respondToUser(userInput) {
        const response = responses[userInput] || responses["default"];
        setTimeout(function() {
            appendMessage("bot", response);
        }, 500);
    }

    function appendMessage(sender, message) {
        const chatBox = document.getElementById("chat-box");
        const messageElement = document.createElement("div");
        messageElement.classList.add(
            sender === "user" ? "user-message" : "bot-message"
        );
        messageElement.innerHTML = message;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
        if (sender === "bot" && message === responses["default"]) {
            const buttonYes = document.createElement("button");
            buttonYes.textContent = "✔ Yes";
            buttonYes.onclick = function() {
                appendMessage("bot", responses["expert"]);
            };
            const buttonNo = document.createElement("button");
            buttonNo.textContent = "✖ No";
            buttonNo.onclick = function() {
                appendMessage("bot", responses["no"]);
            };
            const buttonContainer = document.createElement("div");
            buttonContainer.classList.add("button-container");
            buttonContainer.appendChild(buttonYes);
            buttonContainer.appendChild(buttonNo);
            chatBox.appendChild(buttonContainer);
        }
    }
</script>
